<?php

namespace App\Http\Controllers;

use App\Jobs\TimezonePhotoUploadJob;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Timezone;
use App\Models\TimezoneDefaultJson;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SDKController extends Controller
{


    protected $SDKResponseArray;

    public function __construct()
    {
        $this->SDKResponseArray = [];
        $this->SDKResponseArray['设备未连接到服务器或者未注册'] = 'The device is not connected to the server or not registered';
        $this->SDKResponseArray['查询成功"翻译成英语是'] = 'Query successful';
        $this->SDKResponseArray['没有找到编号为'] = 'The device is not connected to the server or visitor id not registered';
        $this->SDKResponseArray['设备未连接到服务器或者未注册'] = 'The personnel information with ID number  is was not found';

        $this->SDKResponseArray['100'] = 'Timeout or The device is not connected to the server. Try again';
        $this->SDKResponseArray['102'] = 'offline or not connected to this server';
        $this->SDKResponseArray['200'] = 'Successful';
    }
    public function processTimeGroup(Request $request, $id)
    {
        // (new TimezoneController)->storeTimezoneDefaultJson();

        $timezones = Timezone::where('company_id', $request->company_id)
            ->select('timezone_id', 'json')
            ->get();

        $timezoneIDArray = $timezones->pluck('timezone_id');


        $jsonArray = $timezones->pluck('json')->toArray();

        $TimezoneDefaultJson = TimezoneDefaultJson::query();
        $TimezoneDefaultJson->whereNotIn("index", $timezoneIDArray);
        $defaultArray = $TimezoneDefaultJson->get(["index", "dayTimeList"])->toArray();

        $data = array_merge($defaultArray, $jsonArray);
        //ksort($data);

        asort($data);

        $url = env('SDK_URL') . "/" . "{$id}/WriteTimeGroup";

        $sdkResponse = $this->processSDKRequestBulk($url, $data);

        return $sdkResponse;
    }

    public function renderEmptyTimeFrame()
    {
        $arr = [];

        for ($i = 0; $i <= 6; $i++) {
            $arr[] = [
                "dayWeek" => $i,
                "timeSegmentList" => [
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                ],
            ];
        }
        return $arr;
    }

    public function uploadCustomersToCamera()
    {
        $devices = Device::where('device_category_name', "CAMERA")->get(["device_id", "camera_sdk_url", "branch_id", "company_id"]);
        $imageDirectory = public_path('camera-unregsitered-faces-logs');
        $files = glob($imageDirectory . '/*');

        if (!is_dir($imageDirectory) || !count($files)) {
            return "Directory not found";
        }

        $customers = [];


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
                }
            }

            if (array_key_exists($UserID, $customers)) {
                try {
                    Http::withoutVerifying()->post("https://analyticsbackend.xtremeguard.org/api/image-upload", [
                        "imageName" => $filename,
                        "profile_picture" => $md5string,
                    ]);
                    Customer::create($customers[$UserID]);
                    File::deleteDirectory(dirname($file));
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 500);
                }
            }
        }

        return $customers;
    }


    public function PersonAddRangePhotos(Request $request)
    {



        $url = env('SDK_URL') . "/Person/AddRange";
        try {
            $cameraResponse1 = $this->filterCameraModel1Devices($request);
            $cameraResponse2 = $this->filterCameraModel2Devices($request);
        } catch (\Exception $e) {
        }
        $deviceResponse = $this->processSDKRequestJob($url, $request->all());

        Log::channel("camerasdk")->error(json_encode(["cameraResponse2" => $cameraResponse2, "cameraResponse1" => $cameraResponse1, "deviceResponse" => $deviceResponse]));

        return ["cameraResponse" => $cameraResponse1, "cameraResponse2" => $cameraResponse2, "deviceResponse" => $deviceResponse];
    }
    // public function PersonAddRange(Request $request)
    // {
    //     $url = env('SDK_URL') . "/Person/AddRange";

    //     return $this->processSDKRequestBulk($url, $request->all());
    // }

    public function filterCameraModel1Devices($request)
    {

        $snList = $request->snList;
        //$Devices = Device::where('device_category_name', "CAMERA")->get()->all();
        $Devices = Device::where('model_number', "CAMERA1")->get()->all();



        $filteredCameraArray = array_filter($Devices, function ($item) use ($snList) {
            return in_array($item['device_id'], $snList);
        });
        $message = [];
        foreach ($filteredCameraArray as  $value) {

            foreach ($request->personList as  $persons) {
                if (isset($persons['faceImage'])) {

                    $personProfilePic = $persons['faceImage'];
                    if ($personProfilePic != '') {
                        $imageData = file_get_contents($personProfilePic);
                        $md5string = base64_encode($imageData);;
                        $message[] = (new DeviceCameraController($value['camera_sdk_url']))->pushUserToCameraDevice($persons['name'],  $persons['userCode'], $md5string);
                    }
                }
            }
        }

        return  $message;
    }
    public function filterCameraModel2Devices($request)
    {
        $snList = $request->snList;
        //$Devices = Device::where('device_category_name', "CAMERA")->get()->all();
        $Devices = Device::where('model_number', "MEGVII")->get()->all();



        $filteredCameraArray = array_filter($Devices, function ($item) use ($snList) {
            return in_array($item['device_id'], $snList);
        });
        $message = [];
        foreach ($filteredCameraArray as  $value) {

            foreach ($request->personList as  $persons) {
                if (isset($persons['profile_picture_raw'])) {

                    //$personProfilePic = $persons['faceImage'];
                    $personProfilePic = public_path('media/employee/profile_picture/' . $persons['profile_picture_raw']);
                    if ($personProfilePic != '') {
                        //$imageData = file_get_contents($personProfilePic);
                        $imageData = file_get_contents($personProfilePic);
                        $md5string = base64_encode($imageData);;
                        $message[] = (new DeviceCameraModel2Controller($value['camera_sdk_url']))->pushUserToCameraDevice($persons['name'],  $persons['userCode'], $md5string);
                    }
                }
            }
        }

        return  $message;
    }



    public function GetAllDevicesHealth()
    {
        $url = env('SDK_URL') . "/getDevices";

        return $this->processSDKRequestBulk($url, null);
    }
    public function PersonAddRangeWithData($data)
    {
        $url = env('SDK_URL') . "/Person/AddRange";

        return $this->processSDKRequestBulk($url, $data);
    }
    public function processSDKRequestPersonAddJobJson($url, $json)
    {
        $url = env('SDK_URL') . "/Person/AddRange";
        $return = TimezonePhotoUploadJob::dispatch($json, $url);
    }
    public function processSDKRequestJobDeletePersonJson($device_id, $json)
    {
        $url = env('SDK_URL') . "/" . $device_id . "/DeletePerson";
        $return = TimezonePhotoUploadJob::dispatch($json, $url);
    }
    public function processSDKRequestSettingsUpdateTime($device_id, $time)
    {
        $url = env('SDK_URL') . "/" . $device_id . "/SetWorkParam";

        $data = [
            'time' => $time
        ];
        $return = TimezonePhotoUploadJob::dispatch($data, $url);
    }
    public function processSDKRequestSettingsUpdate($device_id, $data)
    {
        $url = env('SDK_URL') . "/" . $device_id . "/SetWorkParam";


        $return = TimezonePhotoUploadJob::dispatch($data, $url);
        return $data;
    }
    public function processSDKRequestCloseAlarm($device_id, $data)
    {
        $url = env('SDK_URL') . "/" . $device_id . "/CloseAlarm";


        $return = TimezonePhotoUploadJob::dispatch($data, $url);
        return $data;
    }

    public function processSDKRequestJobAll($json, $url)
    {
        $return = TimezonePhotoUploadJob::dispatch($json, $url);
    }
    public function processSDKRequestJob($url, $data)
    {

        $personList = $data['personList'];
        $snList = $data['snList'];
        $returnFinalMessage = [];
        $devicePersonsArray = [];

        $sdk_url = env("SDK_URL");
        // if (env("APP_ENV") == "production") {
        //     $sdk_url = env("SDK_PRODUCTION_COMM_URL");
        // } else {
        //     $sdk_url = env("SDK_STAGING_COMM_URL");
        // }

        if ($sdk_url == '') {
            return false;
        }
        $sdk_url = $sdk_url . '/Person/AddRange';
        foreach ($snList as $key => $device) {

            $returnMsg = '';

            foreach ($personList as $keyPerson => $valuePerson) {
                # code...
                $newArray = [
                    "personList" => [$valuePerson],
                    "snList" => [$device],
                ];
                // // $newArray[] = $newArray;
                // $return = TimezonePhotoUploadJob::dispatch($newArray, $sdk_url);

                // $url = env('SDK_URL') . "/Person/AddRange";
                // $return = TimezonePhotoUploadJob::dispatch($json, $url);

                // $returnContent[] = $newArray;
                $return = (new SDKController)->processSDKRequestPersonAddJobJson('', $newArray);
            }
        }
        $returnFinalMessage = $this->mergeDevicePersonslist($returnFinalMessage);
        $returnContent = [
            "data" => $returnFinalMessage, "status" => 200,
            "message" => "",
            "transactionType" => 0
        ];
        return $returnContent;
    }
    public function mergeDevicePersonslist($data)
    {
        $mergedData = [];

        foreach ($data as $item) {
            $sn = $item['sn'];
            $userList = $item['userList'];

            if (array_key_exists($sn, $mergedData)) {
                if (!empty($userList)) {
                    $mergedData[$sn] = array_merge($mergedData[$sn], $userList);
                }
            } else {
                $mergedData[$sn] = $item;
            }
        }

        $mergedList = [];

        foreach ($mergedData as $sn => $userList) {
            $mergedList[] = [
                "sn" => $sn,
                "state" => $userList['state'],
                "message" => $userList['message'],
                "userList" => $userList['userList'],
            ];
        }
        return $mergedList;
    }

    public function getDeviseSettingsDetails($device_id)
    {

        if ($device_id != '') {


            $url = env('SDK_URL') . "/" . "{$device_id}/GetWorkParam";
            $data =   null;


            // return [$url, $data];
            try {
                $return = Http::timeout(60 * 60 * 5)->withoutVerifying()->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, $data);

                $return = json_decode($return, true);
                if (array_key_exists($return['status'], $this->SDKResponseArray)) {
                    $return['message'] =  $this->SDKResponseArray[$return['status']];
                }

                return json_encode($return);
            } catch (\Exception $e) {
                return [
                    "status" => 102,
                    "message" => $e->getMessage(),
                ];
            }
        } else {
            return [
                "status" => 102,
                "message" => "Invalid Details",
            ];
        }
        // You can log the error or perform any other necessary actions here

    }
    public function getPersonDetails($device_id, $user_code)
    {

        // $device_id = $request->device_id;
        // $user_code = $request->user_code;
        if ($device_id != '' && $user_code != '') {


            $url = env('SDK_URL') . "/" . "{$device_id}/GetPersonDetail";
            $data =   ["usercode" => $user_code];


            // return [$url, $data];
            try {
                $return = Http::timeout(3600)->withoutVerifying()->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, $data);

                $return = json_decode($return, true);
                if (array_key_exists($return['status'], $this->SDKResponseArray)) {
                    $return['message'] =  $this->SDKResponseArray[$return['status']];
                }

                return json_encode($return);
            } catch (\Exception $e) {
                return [
                    "status" => 102,
                    "message" => $e->getMessage(),
                ];
            }
        } else {
            return [
                "status" => 102,
                "message" => "Invalid Details",
            ];
        }
        // You can log the error or perform any other necessary actions here

    }
    public function processSDKRequestBulk($url, $data)
    {

        try {
            return Http::timeout(3600)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $data);
        } catch (\Exception $e) {
            return [
                "status" => 102,
                "message" => $e->getMessage(),
            ];
            // You can log the error or perform any other necessary actions here
        }

        // $data = '{
        //     "personList": [
        //       {
        //         "name": "ARAVIN",
        //         "userCode": 1001,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686213736.jpg"
        //       },
        //       {
        //         "name": "francis",
        //         "userCode": 1006,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330253.jpg"
        //       },
        //       {
        //         "name": "kumar",
        //         "userCode": 1005,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330320.jpg"
        //       },
        //       {
        //         "name": "NIJAM",
        //         "userCode": 670,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1688228907.jpg"
        //       },
        //       {
        //         "name": "saran",
        //         "userCode": 1002,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686579375.jpg"
        //       },
        //       {
        //         "name": "sowmi",
        //         "userCode": 1003,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330142.jpg"
        //       },
        //       {
        //         "name": "syed",
        //         "userCode": 1004,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686329973.jpg"
        //       },
        //       {
        //         "name": "venu",
        //         "userCode": 1007,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686578674.jpg"
        //       }
        //     ],
        //     "snList": [
        //       "OX-8862021010076","OX-11111111"
        //     ]
        //   }';
        // $emailJobs = new TimezonePhotoUploadJob();
        // $this->dispatch($emailJobs);

        // $data = json_decode($data, true);
        // $return = TimezonePhotoUploadJob::dispatch($data);
        // // echo exec("php artisan backup:run --only-db");

        // return json_encode($return, true);
    }
    public function getDevicesCountForTimezone(Request $request)
    {


        return Device::where('company_id', $request->company_id)->pluck('device_id');
    }

    public function handleCommand($id, $command)
    {
        // http://139.59.69.241:5000/CheckDeviceHealth/$device_id"
        try {
            return Http::timeout(3600)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("http://139.59.69.241:5000/$id/$command");
        } catch (\Exception $e) {
            return [
                "status" => 102,
                "message" => $e->getMessage(),
            ];
        }
    }

    public function setUserExpiry(Request $request, $id)
    {
        // Employee::where([
        //     "company_id" => $id,
        //     "system_user_id" => $request->userCode
        // ])->update(["lockDevice" => $request->lockDevice]);

        $data = [
            'personList' => [
                [
                    'name' => $request->name,
                    'userCode' => $request->userCode,
                    'timeGroup' => 1,
                    'expiry' => $request->lockDevice ? '2023-01-01 00:00:00' : '2089-01-01 00:00:00'
                ]
            ],
            'snList' =>  Device::where('company_id', $id)->pluck('device_id') ?? []
        ];

        try {
            $response = Http::timeout(3600)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://sdk.mytime2cloud.com/Person/AddRange", $data);

            return $response->json();
        } catch (\Exception $e) {
            return [
                "status" => 102,
                "message" => $e->getMessage(),
            ];
        }
    }
}
