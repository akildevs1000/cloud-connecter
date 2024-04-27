<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeviceCameraController;
use Illuminate\Console\Command;

use App\Models\Customer;
use App\Models\Device;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class uploadCustomersToCamera extends Command
{
    protected $signature = 'customer-sync-local-to-cloud';

    protected $description = 'customer-sync-local-to-cloud';

    public function handle()
    {
        $devices = Device::where('device_category_name', "CAMERA")->get(["device_id", "camera_sdk_url", "branch_id", "company_id"]);
        $imageDirectory = public_path('camera-unregsitered-faces-logs');
        $files = array_slice(glob($imageDirectory . '/*'), 0, 5);

        if (!is_dir($imageDirectory) || !count($files)) {
            Log::info($this->signature . ": Directory not found");
            $this->info($this->signature . ": Directory not found");
            return;
        }

        $customers = [];

        $UserID = 0;
        $customer_name = null;
        $filename = null;
        foreach ($files as $file) {

            $fileCount = glob($file . '/*');


            if (count($fileCount) == 0) {
                File::deleteDirectory(($file));
            } else {
                $UserID = rand(1000, 9999);
                $customer_name =  "customer-" . $UserID;
                $file = glob($file . '/*')[0];

                $imageData = file_get_contents($file);
                $md5string = base64_encode($imageData);
                $filename = "$customer_name.jpg";
            }

            foreach ($devices as $device) {
                $response = (new DeviceCameraController($device['camera_sdk_url']))->pushUserToCameraDevice($customer_name,  $UserID, $md5string);

                if ($response["statusCode"] == 200) {
                    $customers[$UserID] = [
                        'full_name' => $customer_name,
                        'first_name' => $customer_name,
                        'last_name' => $customer_name,
                        'system_user_id' => $UserID,
                        'profile_picture' => $filename,
                        'type' => 'normal',
                        'date' => date("Y-m-d"),
                        'status' => 'whitelisted',
                        'branch_id' => $device['branch_id'],
                        'company_id' => $device['company_id'],
                    ];

                    try {
                        Http::withoutVerifying()->post("https://analyticsbackend.xtremeguard.org/api/image-upload", [
                            "imageName" => $filename,
                            "profile_picture" => $md5string,
                        ]);
                        Customer::create($customers[$UserID]);
                        File::deleteDirectory(dirname($file));
                    } catch (\Exception $e) {
                        $this->info($this->signature . ": " . $e->getMessage());
                        Log::error($this->signature . ": " . $e->getMessage());
                    }
                } else {
                    File::deleteDirectory(dirname($file));
                    $response['action'] = "Deleting file => " . $file;
                    $this->info($this->signature . ": " . json_encode($response));
                    Log::info($this->signature . ": " . json_encode($response));
                }
            }
        }

        $this->info($this->signature . ": " . json_encode($customers));
        Log::info($this->signature . ": " . json_encode($customers));
    }
}
