<?php

namespace App\Http\Controllers;

use App\Jobs\TimezonePhotoUploadJob;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Timezone;
use App\Models\TimezoneDefaultJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SDKControllerV1 extends Controller
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


    public function PersonAddRangePhotos(Request $request)
    {
        $url = env('SDK_URL') . "/Person/AddRange";

        try {
            $deviceResponse = $this->processSDKRequestJob($url, $request->all());
            $cameraResponse1 = $this->filterCameraModel1Devices($request);
            // $cameraResponse2 = $this->filterCameraModel2Devices($request);
            Customer::where("company_id", $request->company_id)->whereIn("system_user_id", $cameraResponse1)->update(["camera1" => 1]);
            return ["cameraResponse" => $cameraResponse1, "deviceResponse" => $deviceResponse];
        } catch (\Exception $e) {
            return $this->response($e->getMessage(), null, false, 500);
        }
    }

    public function filterCameraModel1Devices($request)
    {
        $Devices = Device::where('model_number', "CAMERA1")->whereIn("device_id", $request->snList)->get(["device_id", "camera_sdk_url", "branch_id", "company_id"]);

        $message = [];
        foreach ($Devices as  $value) {
            $sessionResponse = $this->getActiveSessionId($value['camera_sdk_url']);

            foreach ($request->personList as  $persons) {
                if (isset($persons['faceImage'])) {
                    $personProfilePic = $persons['faceImage'];
                    if ($personProfilePic != '') {
                        $imageData = file_get_contents($personProfilePic);
                        $md5string = base64_encode($imageData);

                        $DCController = new DeviceCameraController($value['camera_sdk_url']);

                        $childResponse = $DCController->pushUserToCameraDeviceV1($sessionResponse, $persons['name'],  $persons['userCode'], $md5string);

                        $message[] = $childResponse;
                    }
                }
            }
        }

        return  $message;
    }

    // public function getActiveSessionId($camera_sdk_url)
    // {
    //     try {
    //         $endpoint = $camera_sdk_url . '/ISAPI/Security/Login';

    //         $post_data = ' ';

    //         $response = $this->curlPost($endpoint, $post_data);

    //         $xml = simplexml_load_string($response);

    //         if (!$xml) {
    //             throw new \Exception("Server Error. Address: " . $camera_sdk_url);
    //         }

    //         $sessionId = (string) $xml->SessionId;

    //         if (env("CAMERA_SDK_LOGIN_USERNAME") == '') {
    //             throw new \Exception("SDK Username is Empty.");
    //         }

    //         if (env("CAMERA_SDK_LOGIN_PASSWORD") == '') {
    //             throw new \Exception("SDK Password is Empty.");
    //         }

    //         $md5string = md5($sessionId . ':' . "admin" . ':' . "Admin@123" . ':IPCAM');

    //         if ($md5string == '') {
    //             throw new \Exception("Invalid MD5 String.");
    //         }

    //         $post_data = '<UserCheck>
    //             <Username>' . env("CAMERA_SDK_LOGIN_USERNAME") . '</Username>
    //             <Password>' . $md5string . '</Password>
    //             <SessionId>' . $sessionId . '</SessionId>
    //         </UserCheck>';
    //         $response = $this->curlPost($endpoint, $post_data);

    //         $xml = simplexml_load_string($response);
    //         $StatusCode = (string) $xml->StatusCode;

    //         if ($StatusCode == 200) {
    //             return ["message" => $sessionId, "status" => true];
    //         } else {
    //             Log::channel("camerasdk")->error("SessionID activation is failed");
    //             throw new \Exception("SessionID activation is failed.");
    //         }
    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }


    public function getActiveSessionId()
    {
        $post_data = ' ';

        $response = $this->curlPost('/ISAPI/Security/Login', $post_data);

        $xml = simplexml_load_string($response);
        if ($xml == '') {
            return ["message" => "SessionID is not generated.", "status" => false];
        }
        $sessionId = (string) $xml->SessionId;


        //activate the sessionid

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
                <Username>' . env("CAMERA_SDK_LOGIN_USERNAME") . '</Username>
                <Password>' . $md5string . '</Password>
                <SessionId>' . $sessionId . '</SessionId>
            </UserCheck>';
            $response = $this->curlPost('/ISAPI/Security/Login', $post_data);

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

    public function curlPost($url, $post_data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/plain'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        Log::channel("camerasdk")->info('CURL ' . $url . '-');
        return $response;
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
        return TimezonePhotoUploadJob::dispatch($json, $url);
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
    public function processSDKRequestJob($sdk_url, $data)
    {

        $personList = $data['personList'];
        $snList = $data['snList'];
        $returnFinalMessage = [];


        foreach ($snList as $device) {

            foreach ($personList as $valuePerson) {
                $newArray = [
                    "personList" => [$valuePerson],
                    "snList" => [$device],
                ];

                TimezonePhotoUploadJob::dispatch($newArray, $sdk_url);
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
