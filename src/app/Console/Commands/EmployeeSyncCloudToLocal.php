<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeviceCameraController;
use App\Http\Controllers\SDKControllerV1;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EmployeeSyncCloudToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
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

        try {

            $persons = Employee::where("company_id", 2)->get(["full_name", "system_user_id", "profile_picture"])->toArray();

            $Devices = Device::where('device_category_name', "CAMERA")->get(["device_id", "camera_sdk_url", "branch_id", "company_id"]);

            $responses = [];


            foreach ($Devices as  $value) {

                foreach ($persons as  $person) {

                    $pic = "https://analyticsbackend.xtremeguard.org/media/employee/profile_picture/" . $person["profile_picture_raw"];
                    $imageData = file_get_contents($pic);
                    $md5string = base64_encode($imageData);
                    $response = (new DeviceCameraController($value['camera_sdk_url']))->pushUserToCameraDevice($person['full_name'],  $person['system_user_id'], $md5string);
                    echo json_encode($response) . "\n";
                }
            }
        } catch (\Exception $e) {
            $this->info($e->getMessage() . " at $command command.");
        }
    }
}
