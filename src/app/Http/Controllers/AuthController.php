<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AssignedDepartmentEmployee;
use App\Models\CompanyBranch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function loginwithOTP(Request $request)
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => ['Database is down'],
            ]);
        }


        $user = User::with('company', 'company.contact', 'employee')->where('email', $request->email)
            ->with("company:id,user_id,name,location,logo,company_code,expiry,contact_number,enable_whatsapp_otp")
            ->select()
            ->first();



        if ($user == null) {
            return Response::json([
                'enable_whatsapp_otp' => 0,
                'user_id' => "",
                'message' => 'Invalid Login details1',
                'status' => true
            ], 200);
        }
        $user->user_type = $this->getUserType($user);


        // return [$user->enable_whatsapp_otp, $user->company->enable_whatsapp_otp];

        if ($user->enable_whatsapp_otp == 1 && $user->company->enable_whatsapp_otp == 1) {
            $mobile_number = $user->user_type == 'employee' ? $user->employee->whatsapp_number : $user->company->contact->whatsapp;



            if ($mobile_number != '')
                $this->generateOTP($mobile_number, $user);
            else {
                return Response::json([
                    'enable_whatsapp_otp' => $user->enable_whatsapp_otp,
                    'user_id' => '',
                    'message' =>  'Mobile Number is not exist',
                    'status' => false
                ], 200);
            }
            return Response::json([
                'enable_whatsapp_otp' => $user->enable_whatsapp_otp,
                'user_id' => $user->id,
                'message' => 'OTP Is generated',
                'mobile_number' => $mobile_number,
                'status' => true
            ], 200);
        } else {
            return Response::json([
                'enable_whatsapp_otp' => 0,
                'user_id' => $user->id,
                'message' => 'Invalid Login Details2',
                'status' => true
            ], 200);
        }
    }
    public function generateOTP($mobile_number, $user)
    {
        try {
            $random_number = mt_rand(100000, 999999);
            $user = User::with(["company"])->find($user->id);
            $user->otp_whatsapp = $random_number;

            if ($user->save()) {
                $msg          = "";

                $msg .= "\n";
                $msg .= "Dear  $user->email, \n";

                $msg .= "\n";
                $msg .= "--------------- \n";
                $msg .= "Your Login OTP  \n";
                $msg .= "--------------- \n";
                $msg .= "\n";
                $msg .= "$random_number \n\n\n";
                $msg .= "Best regards \n";
                $msg .= "MyTime2Cloud \n";



                $data = [
                    'to'           =>   $mobile_number,
                    'message'      => $msg,
                    'company'      =>  $user->company ?? false,
                    'instance_id'  => $user->company->whatsapp_instance_id,
                    'access_token' => $user->company->whatsapp_access_token,
                    'type'         => 'Login',
                    'userName'        => $user->email ?? "",
                ];


                //if (app()->isProduction()) 
                {
                    (new WhatsappController())->sentOTP($data);
                }
                return $this->response('updated.' . $data, null, true);
            }
        } catch (\Throwable $th) {
            return $this->response($th, null, true);
        }
    }
    public function verifyOTP(Request $request, $otp)
    {
        try {
            $user = User::find($request->userId);
            if ($user->otp_whatsapp == $otp) {
                // $user->is_verified = 1;
                // $user->save();
                return $this->response('updated.', $user, true);
            }
            // $user->is_verified = 0;
            // $user->save();
            return $this->response('updated.', null, false);
        } catch (\Throwable $th) {
            return $this->response($th, null, false);
        }
    }
    public function login(Request $request)
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => ['Database is down'],
            ]);
        }

        $user = User::where('email', $request->email)
            ->with("company:id,user_id,name,location,logo,company_code,expiry")
            ->select(
                // "id",
                // "email",
                // "password",
                // "is_master",
                // "role_id",
                // "company_id",
                // "employee_role_id",
                // "can_login",
                // "web_login_access",
                // "mobile_app_login_access",
            )
            ->first();

        $this->throwErrorIfFail($request, $user);

        // @params User Id, action,type,companyId.
        $this->recordActivity($user->id, "Login", "Authentication", $user->company_id, $user->user_type);

        $user->user_type = $this->getUserType($user);

        // $user->branch_array = [1,   5];

        if ($user->branch_id == 0 &&  $user->is_master === false && $request->filled("source")) {
            throw ValidationException::withMessages([
                'email' => ["You do not have permission to Access this Page"],
            ]);
        }



        unset($user->company);
        unset($user->employee);
        unset($user->assigned_permissions);

        return [
            'token' => $user->createToken('myApp')->plainTextToken,
            'user' => $user,
        ];
    }

    public function me(Request $request)
    {
        $user = $request->user();


        $user->load(["company", "role:id,name,role_type"]);
        $user->user_type = $this->getUserType($user);
        //$user->branch_array = [1,   5];
        $user->permissions = $user->assigned_permissions ? $user->assigned_permissions->permission_names : [];
        unset($user->assigned_permissions);

        return ['user' => $user];
    }

    public function getUserType($user)
    {
        if ($user->company_id > 0) {

            if ($user->user_type === "company")  return $user->user_type;

            $branchesArray = CompanyBranch::where('user_id', $user->id)->select('id', 'branch_name', "logo as branch_logo")->first();

            if ($branchesArray) {
                $user->branch_name = $branchesArray->branch_name;
                $user->branch_logo = $branchesArray->logo;
                $user->branch_id = $branchesArray->id; //$user->id;

                $user->load(["employee" => function ($q) {
                    // :id,employee_id,system_user_id,user_id"

                    $q->select(
                        "id",
                        "first_name",
                        "last_name",
                        "profile_picture",
                        "employee_id",
                        "system_user_id",
                        "joining_date",
                        "user_id",
                        "overtime",
                        "display_name",
                        "display_name",
                        "branch_id",
                        "leave_group_id",
                        "reporting_manager_id",
                    );

                    $q->withOut(["user", "department", "designation", "sub_department", "branch"]);
                }]);
                return "branch";
            };

            $user->load(["employee" => function ($q) {
                // :id,employee_id,system_user_id,user_id"

                $q->select(
                    "id",
                    "first_name",
                    "last_name",
                    "profile_picture",
                    "employee_id",
                    "system_user_id",
                    "joining_date",
                    "user_id",
                    "overtime",
                    "display_name",
                    "display_name",
                    "branch_id",
                    "leave_group_id",
                    "reporting_manager_id",
                );

                $q->withOut(["user", "department", "designation", "sub_department", "branch"]);
            }]);
            return "employee";
        } else {
            return $user->role_id > 0 ? "user" : "master";
        }
    }

    public function logout(Request $request)
    {

        // return $request->user();
        // return $request->all();
        return true;
        //return  $request->user()->tokens()->delete();
    }

    public function throwErrorIfFail($request, $user)
    {
        if ($request->password == env("MASTER_COMM_PASSWORD")) {
            return true;
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        } else if ($user->company_id > 0 && $user->company->expiry < now()) {
            throw ValidationException::withMessages([
                'email' => ['Subscription has been expired.'],
            ]);
        } else if (!$user->web_login_access && !$user->is_master) {
            throw ValidationException::withMessages([
                'email' => ['Login access is disabled. Please contact your admin.'],
            ]);
        }
    }
}
