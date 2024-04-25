<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceEmployee;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EmployeeSyncCloudToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $camera_sdk_url = "";

    protected $signature = 'employee-sync-cloud-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'employee-sync-cloud-to-local';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $this->processJobs();
            sleep(10);
        }
    }

    public function processJobs()
    {


        $command = $this->signature;
        $user = null;

        $email      = $this->ask('Enter email address', "demo@gmail.com");
        $password   = $this->ask('Enter password', 'demo123');

        $user = User::with("company:id,expiry")->where("email", $email)->first(["password", "company_id", "is_master"]);

        $errorMessage = null;

        if (!$user || !Hash::check($password, $user->password)) {
            $errorMessage = 'The provided credentials are incorrect.';
        } else if ($user->company_id > 0 && $user->company->expiry < now()) {
            $errorMessage = 'Subscription has been expired.';
        } else if (!$user->is_master) {
            $errorMessage = 'Login access is disabled. Please contact your admin.';
        }

        if ($errorMessage) {
            $this->error($errorMessage);
            return;
        }

        $payload = [];

        try {
            $persons = Employee::where("company_id", $user?->company_id ?? 2)->get(["full_name", "system_user_id", "profile_picture"])->toArray();
            $Devices = Device::where('device_category_name', "CAMERA")->get(["device_id", "camera_sdk_url", "branch_id", "company_id"]);


            if (!count($persons)) {
                $this->info("No Employee found");
                return;
            }

            if (!count($Devices)) {
                $this->info("No Device found");
                return;
            }


            foreach ($persons as  $person) {
                foreach ($Devices as  $value) {
                    $found = DeviceEmployee::where("employee_id", $person['system_user_id'])->where("device_id", $value['device_id'])->first();

                    if ($found) {
                        continue;
                    }
                    $sessionResponse = $this->getActiveSessionId($value['camera_sdk_url']);

                    if ($sessionResponse['status']) {
                        $sessionId = $sessionResponse['message'];

                        $pic = env("BASE_URL") . "/media/employee/profile_picture/" . $person["profile_picture_raw"];
                        $imageData = file_get_contents($pic);
                        $md5string = base64_encode($imageData);

                        $postData = '<RegisterImage>
                    <FaceItem>
                    <Name>' . $person['system_user_id'] . '</Name> 
                    <CardType>0</CardType>
                    <CardNum>' . $person['system_user_id'] . '</CardNum> 
                    <Gender>Male</Gender>
                    <Overwrite>0</Overwrite>
                    <ImageContent>' . $md5string . '</ImageContent>
                    </FaceItem>
                    </RegisterImage>';
                        $response = (new Controller)->curlPost($value['camera_sdk_url'] . '/ISAPI/FaceDetection/RegisterImage?ID=' . $sessionId, $postData);

                        $xml = simplexml_load_string($response);

                        if (!$xml) {
                            $result = ["statusCode" => 500, "message" => "Empty Response", "user_id" => $person['system_user_id'], "device_id" => $value["device_id"]];
                            $this->info(json_encode($result));
                            continue;
                        }

                        $statusString = (string) $xml->StatusString ?? null;
                        if ($xml->StatusCode == 200) {
                            $result = ["statusCode" => 200, "message" => $statusString, "user_id" => $person['system_user_id'], "device_id" => $value["device_id"]];
                            $payload[] = [
                                "employee_id" => $person["system_user_id"],
                                "device_id" => $value["device_id"],
                            ];

                            $this->info(json_encode($result));
                        } else {
                            $result = ["statusCode" => 500, "message" => $statusString, "user_id" => $person['system_user_id'], "device_id" => $value["device_id"]];
                            $this->info(json_encode($result));
                        }
                    } else {
                        $result = ["statusCode" => 500, "message" => $sessionResponse['message'], "user_id" => $person['system_user_id'], "device_id" => $value["device_id"]];
                        $this->info(json_encode($result));
                    }
                }

                DeviceEmployee::insert($payload);
                $this->info(json_encode($payload));
                $payload = [];
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage() . " at $command command.");
        }
    }

    public function getActiveSessionId($baseUrlIP)
    {
        $url = $baseUrlIP . '/ISAPI/Security/Login';

        $post_data = ' ';

        $response = (new Controller)->curlPost($url, $post_data);

        $xml = simplexml_load_string($response);
        if ($xml == '') {
            return ["message" => "SessionID is not generated.", "status" => false];
        }
        $sessionId = (string) $xml->SessionId;

        if ($sessionId == '') {
            return ["message" => "SessionID is not geenrated.", "status" => false];
        } else if (env("CAMERA_SDK_LOGIN_USERNAME") == '') {
            return ["message" => "SDK Username is Empty ", "status" => false];
        } else if (env("CAMERA_SDK_LOGIN_PASSWORD")  == '') {
            return   ["message" => "SDK Password is Empty", "status" => false];
        }

        $md5string = md5($sessionId . ':' . "admin" . ':' . "Admin@123" . ':IPCAM');
        if ($md5string != '') {
            $post_data = '<UserCheck>
                <Username>' . "admin" . '</Username>
                <Password>' . $md5string . '</Password>
                <SessionId>' . $sessionId . '</SessionId>
            </UserCheck>';

            $response = (new Controller)->curlPost($url, $post_data);

            $xml = simplexml_load_string($response);

            if ($xml) {
                $StatusCode = (string) $xml->StatusCode;
                if ($StatusCode == 200) {
                    return   ["message" => $sessionId, "status" => true];
                } else {
                    return   ["message" => "SessionID activation is failed", "status" => false];
                }
            }
        } else {
            return   ["message" => "Invalid MD5 String", "status" => false];
        }
    }
}
