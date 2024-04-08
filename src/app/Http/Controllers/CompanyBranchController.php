<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\CompanyBranch\StoreRequest;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyBranchController extends Controller
{
    public function dropdownList()
    {
        $model = CompanyBranch::query();
        $model->where('company_id', request('company_id'));
        if (request("branch_id"))
            $model->where('id', request('branch_id'));

        $model->orderBy(request('order_by') ?? "id", request('sort_by_desc') ? "desc" : "asc");
        return $model->get(["id", "branch_name as name"]);
    }
    public function seedDefaultData()
    {
        $arr = [];
        foreach (range(1, 5) as $i) {
            $arr[] = [
                "branch_code" => "NAM" . $i,
                "branch_name" => "BRANCH NAME " . $i,
                "created_date" => "13 Sep 2023",
                'location' => "khalid street",
                'address' => "Ajman",
                'licence_issue_by_department' => "Ajman Economic Department",
                'licence_number' => "0098765",
                'licence_expiry' => "24/09/2024",
                'telephone' => "0554501483",
                'po_box' => "654789",
                'phone' => "0554501483",
                "user_id" => 0,
                "company_id" => 8,
            ];
        }
        CompanyBranch::truncate();
        CompanyBranch::insert($arr);
        return CompanyBranch::count();
    }
    public function branchesList(Request $request)
    {
        $model = CompanyBranch::where('company_id', $request->company_id);

        $model =  $model->when($request->filled("branch_id"), function ($q) use ($request) {
            return $q->where("id", $request->branch_id);
        });

        $model =  $model->when($request->filled("filter_branch_id"), function ($q) use ($request) {
            return $q->where("id", $request->filter_branch_id);
        });
        return $model->orderBy('branch_name', 'asc')->get();
    }
    public function store(CompanyBranch $model, StoreRequest $request)
    {
        $data = $request->validated();
        $data["created_date"] = date("Y-m-d");
        $data["branch_code"] = strtoupper(substr($data["branch_name"], 0, 3)) . CompanyBranch::where("company_id", $request->company_id)->orderBy("id", "desc")->value("id") ?? 0;

        // $company = Company::withCount('companybranches')->find($request->company_id);
        // $totalBranches = $company->companybranches_count ?? 0;
        // $max_branches = $company->max_branches ?? 0;
        // $remainingEmployee =  (int) $max_branches - (int) $totalBranches;

        // if ($remainingEmployee <= 0) {

        //     return $this->response("Branch limit exceeded. Maximum limit is " . $max_branches, null, false);
        // }

        if (isset($request->logo)) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $request->file('logo')->move(public_path('/upload'), $fileName);
            $data['logo'] = $fileName;
        }

        try {
            $record = $model->create($data);

            if ($record) {
                return $this->response('Branch successfully added.', null, true);
            } else {
                return $this->response('Branch cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(CompanyBranch $model, StoreRequest $request, $id)
    {
        $data = $request->validated();

        if (isset($request->logo)) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $request->file('logo')->move(public_path('/upload'), $fileName);
            $data['logo'] = $fileName;
        }

        try {
            $record = $model->where("id", $id)->update($data);

            if ($record) {
                return $this->response('Branch successfully added.', null, true);
            } else {
                return $this->response('Branch cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function index(Request $request)
    {


        $model = CompanyBranch::query();

        $model->where('company_id', $request->company_id);

        $model->when($request->filled("branch_id"), function ($q) use ($request) {
            return $q->where("id", $request->branch_id);
        });

        $model->when($request->filled("filter_branch_id"), function ($q) use ($request) {
            return $q->where("id", $request->filter_branch_id);
        });

        // $model->when($request->filled("location_address"), function ($q) use ($request) {
        //     return $q->where("location_address", "ILIKE", $request->location_address);
        // });

        $model->when($request->filled("location_address"), function ($q) use ($request) {

            $q->where(function ($q) use ($request) {
                $q->where("location", "ILIKE", $request->location_address . '%');
                $q->orWhere("address", "ILIKE", $request->location_address . '%');
            });
        });
        $model->when($request->filled("manager_mobile"), function ($q) use ($request) {

            // $q->whereHas('user.employee', fn (Builder $query) => $query->where('first_name', "ILIKE",   $request->manager_mobile));
            // $q->orwhereHas('user.employee', fn (Builder $query) => $query->where('phone_number', "ILIKE",   $request->manager_mobile));
            $q->where(function ($q) use ($request) {
                $q->whereHas('user.employee', fn (Builder $query) => $query->where('first_name', "ILIKE",   $request->manager_mobile . '%'));
                $q->orwhereHas('user.employee', fn (Builder $query) => $query->where('phone_number', "ILIKE",   $request->manager_mobile . '%'));
            });
        });


        $model->with("user.employee")->withCount(["employees", "devices", "departments"]);

        return $model->orderBy("id", "desc")->paginate($request->per_page ?? 100);
    }

    public function destroy($id)
    {
        try {
            $record = CompanyBranch::find($id);

            if (!$record) {
                return response()->json(['message' => 'No such record found.'], 404);
            }

            DB::transaction(function () use ($record) {
                $user_id = $record->user_id;
                $record->delete();
                User::where('id', $user_id)->delete();
            });

            return response()->json(['message' => 'Branch successfully deleted.', 'status' => true], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
