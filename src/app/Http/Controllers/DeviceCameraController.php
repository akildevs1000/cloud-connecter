<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class DeviceCameraController extends Controller
{
    public  $camera_sdk_url = '';

    public function __construct($camera_sdk_url)
    {
        $this->camera_sdk_url = $camera_sdk_url;
    }
    public function updateCameraDeviceLiveStatus($devices)
    {
        $online_devices_count = 0;
        $devicesInfo = null;
        $sessionResponse = $this->getActiveSessionId();
        if ($sessionResponse['status']) {
            $sessionId = $sessionResponse['message'];
            $devicesInfo = $this->curlPost('/ISAPI/DeviceInfo?ID=' . $sessionId, ' ');
        }
        $DeviceIDs = [];

        foreach ($devices as $device) {
            $xml = simplexml_load_string($devicesInfo);

            if ($xml) {
                $DeviceID = (string) $xml->DeviceID;
                if ($DeviceID && $DeviceID != '') {
                    $DeviceIDs[] = $DeviceID;
                    $online_devices_count++;
                }
            }
        }

        Device::whereIn("device_id", $DeviceIDs)->update([
            "status_id" => 1,
            "last_live_datetime" => date("Y-m-d H:i:s"),
            "camera_sdk_url" => $this->camera_sdk_url
        ]);

        return  $online_devices_count;
    }

    public function pushUserToCameraDeviceV1($sessionResponse, $name,  $system_user_id, $base65Image)
    {
        $gender  = 'Male';
        if ($sessionResponse['status']) {
            $sessionId = $sessionResponse['message'];


            $postData = '<RegisterImage>
            <FaceItem>
            <Name>' . $name . '</Name> 
            <CardType>0</CardType>
            <CardNum>' . $system_user_id . '</CardNum> 
            <Gender>Male</Gender>
            <Overwrite>0</Overwrite>
            <ImageContent>' . $base65Image . '</ImageContent>
            </FaceItem>
            </RegisterImage>';
            $response = $this->curlPost('/ISAPI/FaceDetection/RegisterImage?ID=' . $sessionId, $postData);

            $xml = simplexml_load_string($response);
            return json_decode(json_encode($xml), true);
        } else {
            return $sessionResponse['message'];
        }
    }

    public function pushUserToCameraDevice($name,  $system_user_id, $base65Image)
    {
        $gender  = 'Male';
        $sessionResponse = $this->getActiveSessionId();
        if ($sessionResponse['status']) {
            $sessionId = $sessionResponse['message'];


            $postData = '<RegisterImage>
            <FaceItem>
            <Name>' . $name . '</Name> 
            <CardType>0</CardType>
            <CardNum>' . $system_user_id . '</CardNum> 
            <Gender>Male</Gender>
            <Overwrite>0</Overwrite>
            <ImageContent>' . $base65Image . '</ImageContent>
            </FaceItem>
            </RegisterImage>';
            $response = $this->curlPost('/ISAPI/FaceDetection/RegisterImage?ID=' . $sessionId, $postData);

            $xml = simplexml_load_string($response);

            if ($xml->StatusCode == 200) {
                return ["statusCode" => 200, "message" => $xml->StatusString];
            } else {
                return ["statusCode" => 500, "message" => $xml->StatusString , "user_id" => $system_user_id];
            }
        } else {
            return ["statusCode" => 500, "message" => $sessionResponse['message']];
        }
    }
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

        if ($this->camera_sdk_url != '') {
            $url = $this->camera_sdk_url .   $url;
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
            return $response;
        }
    }
}
