<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeviceCameraController;
use App\Http\Controllers\SDKControllerV1;
use App\Models\Customer;
use App\Models\CustomerSync;
use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CustomerSyncCloudToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer-sync-cloud-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'customer-sync-cloud-to-local';

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
        try {
            $first = CustomerSync::first();

            if (!$first) {
                $this->info("No Record found at $command command.");
                Log::error("No Record found at $command command.");
                return;
            }

            $company_id = $first->company_id;
            $request = $first->payload;

            $Devices = Device::where('model_number', "CAMERA1")->whereIn("device_id", $request["snList"])->get(["device_id", "camera_sdk_url", "branch_id", "company_id"]);

            $userIds = [];
            foreach ($Devices as  $value) {
                $sessionResponse = (new SDKControllerV1)->getActiveSessionId($value['camera_sdk_url'] = "192.168.2.27");

                foreach ($request['personList'] as  $persons) {
                    if (isset($persons['faceImage'])) {
                        $personProfilePic = $persons['faceImage'];
                        if ($personProfilePic != '') {
                            $imageData = file_get_contents($personProfilePic);
                            $md5string = base64_encode($imageData);

                            $DCController = new DeviceCameraController($value['camera_sdk_url']);

                            $childResponse = $DCController->pushUserToCameraDeviceV1($sessionResponse, $persons['name'],  $persons['userCode'], $md5string);

                            if ($childResponse['StatusCode'] == 200) {
                                $userIds[] = $persons['userCode'];
                            } else {
                                Log::error("User with " . $persons['userCode'] . " id for company id ( " . $company_id . " ) cannot upload at $command command\n.");
                                $this->info("User with " . $persons['userCode'] . " id for company id ( " . $company_id . " ) cannot upload at $command command.");
                            }
                        }
                    }
                }
            }

            if (count($userIds)) {
                Customer::where("company_id", $company_id)->whereIn("system_user_id", $userIds)->update(["camera1" => 1]);
                Log::info("Customer with these " . json_encode($userIds) . " ids for company id (" . $company_id . ") has been uploaded");
                $this->info("Customer with these " . json_encode($userIds) . " ids for company id (" . $company_id . ") has been uploaded");
            }
            $first->delete();
        } catch (\Exception $e) {
            Log::error($e->getMessage() . " at $command command.");
            $this->info($e->getMessage() . " at $command command.");
        }
    }
}
