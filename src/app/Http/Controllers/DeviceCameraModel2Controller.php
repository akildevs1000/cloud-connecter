<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class DeviceCameraModel2Controller extends Controller
{
    public  $camera_sdk_url = '';
    public  $sxdmToken = '7VOarATI4IfbqFWLF38VdWoAbHUYlpAY';
    public  $sxdmSn = 'M014200892105001731';


    public function __construct($camera_sdk_url)
    {
        $this->camera_sdk_url = $camera_sdk_url;
    }








    public function openDoor($device)
    {
        $this->sxdmSn = $device->device_id;
        $json = '{
            "tips": {
                "text": "welcome",
                "person_type": "staff"
            }
        }';
        $response = $this->postCURL('/api/devices/io', $json);
    }
    public function closeDoor($device)
    {

        //reset the always open door settings and then close the door automatically after 1 sec 
        $this->sxdmSn = $device->device_id;
        $json = '{             
                "door_open_stat": "none"                 
            
        }';
        $response = $this->putCURL('/api/devices/door', $json);
        $this->sxdmSn = $device->device_id;
        $json = '{
            "tips": {
                "text": "welcome",
                "person_type": "staff"
            }
        }';
        $response = $this->postCURL('/api/devices/io', $json);
    }

    public function openDoorAlways($device)
    {
        $this->sxdmSn = $device->device_id;
        $json = '{             
                "door_open_stat": "open"                 
            
        }';
        $response = $this->putCURL('/api/devices/door', $json);
    }
    public function updateSettings($request)
    {
        $this->sxdmSn = $request->deviceSettings['device_id'];
        $json = '{             
                "voice_volume": ' . round($request->deviceSettings['voice_volume']) . '              
            
        }';
        $response = $this->putCURL('/api/devices/profile', $json);
    }
    public function getSettings($device)
    {
        $row = [];
        try {
            $this->sxdmSn = $device->device_id;
            $status = $this->getCURL('/api/devices/status');
            $profile = $this->getCURL('/api/devices/profile');
            $time = $this->getCURL('/api/devices/time');
            $door = $this->getCURL('/api/devices/door');
            $network = $this->getCURL('/api/devices/network');
            $server = $this->getCURL('/api/devices/server');

            $row['model_spec'] = $status['model_spec'];
            $row['voice_volume'] = $profile['voice_volume'];
            $row['local_time'] = $time['local_time'];
            $row['door_open_stat'] = $door['door_open_stat'];
            $row['wifi_ip'] = $network['wifi']['ip'];
            $row['lan_ip'] = $network['lan']['ip'];
            $row['ipaddr'] = $server['ipaddr'];



            $inputDateString = $row['local_time'];
            $inputDateTime = new DateTime($inputDateString);
            $row['local_time'] = $inputDateTime->format("Y-m-d H:i P");
        } catch (\Exception $e) {
        }

        return  $row;
    }

    public function pushUserToCameraDevice($name,  $system_user_id, $base65Image)
    {

        try {


            $sessionId = $this->getActiveSessionId();
            if ($sessionId != '') {

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->camera_sdk_url . '/api/persons/item',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
                    "recognition_type": "staff",
                    "is_admin": false,
                    "person_name": "' . $name . '",
                    "id": ' . $system_user_id . ',
                    "password": "123456",
                    "card_number": ' . $system_user_id . ',
                    "person_code":' . $system_user_id . ',
                    "visit_begin_time": "' . date('Y-m-d 00:00:00') . '",
                    "visit_end_time": "' .  date('Y-m-d 00:00:00', strtotime(date("Y-m-d 23:00:00") . " + 365 day")) . '",
                    "phone_num":"18686868686",
                    "group_list": [
                      1
                    ],
                    "feature_version":"8903",
                    "face_list": [
                      {
                        "idx": 3,
                        "data": "' . $base65Image . '"
                      }
                    ]
                  }',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Cookie: sessionID=' . $sessionId,
                        'sxdmToken: ' . $this->sxdmToken, //get from Device manufacturer
                        'sxdmSn:  ' . $this->sxdmSn //get from Device serial number
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                //return $response;

                $this->devLog("camera-megeye-info", "Successfully Added ID:" . $system_user_id . ", Name :  " . $name);
            } else {

                $this->devLog("camera-megeye-error", "Unable to Generate session");
            }
        } catch (\Throwable $th) {
            //throw $th;
            $this->devLog("camera-megeye-error", "Exception - Unable to Generate session" . $th);
        }
    }
    public function updateTimeZone($device)

    {
        $this->sxdmSn = $device->device_id;

        $utc_time_zone  = $device->utc_time_zone;
        if ($utc_time_zone != '') {

            $timezone = new DateTimeZone($utc_time_zone);
            $utcOffset = $timezone->getOffset(new DateTime());
            $offsetHours = $utcOffset / 3600;
            $offsetMinutes = abs(($utcOffset % 3600) / 60);
            $utcOffsetString = sprintf('GMT%+03d:%02d:00', $offsetHours, $offsetMinutes);

            //
            $dateObj = new DateTime("now", $timezone);
            $output_time_zone = new DateTimeZone($utc_time_zone);

            $dateObj->setTimezone($output_time_zone);
            $output_format = 'Y-m-d\TH:i:sP'; // "2024-01-26T11:59:00+04:00"
            $currentTime = $dateObj->format($output_format);
        }


        $json = '{
            "local_time": "' . $currentTime . '",
            "ntp": {
                "mode": false,
                "time_zone": "' . $utcOffsetString . '",
                "server_port": 123,
                "sync_interval": 60,
                "server_address": "cn.pool.ntp.org"
            }
        }';


        return   $response = $this->putCURL('/api/devices/time', $json);
    }
    public function getCameraDeviceLiveStatus($company_id)
    {
        $online_devices_count = 0;
        $devices = Device::where('company_id', $company_id)->where('model_number', "MEGVII");

        $devices->clone()->update(["status_id" => 2]);

        foreach ($devices->get() as $device) {

            $this->sxdmSn = $device->device_id;
            $this->camera_sdk_url = $device->camera_sdk_url;


            $response = $this->getCURL('/api/devices/status');

            if (isset($response["serial_no"])) {

                Device::where("device_id", $response["serial_no"])->update(["status_id" => 1, "last_live_datetime" => date("Y-m-d H:i:s")]);
                $online_devices_count++;
            }
        }

        return  $online_devices_count;
    }

    public function getCURL($serviceCall)
    {
        $sessionId = $this->getActiveSessionId();


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->camera_sdk_url . $serviceCall,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: sessionID=' . $sessionId,
                'sxdmToken: ' . $this->sxdmToken, //get from Device manufacturer
                'sxdmSn:  ' . $this->sxdmSn //get from Device serial number
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return  $response = json_decode($response, true);
    }
    public function putCURL($serviceCall, $post_json)
    {


        $sessionId = $this->getActiveSessionId();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->camera_sdk_url . $serviceCall,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS =>  $post_json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: sessionID=' . $sessionId,
                'sxdmToken: ' . $this->sxdmToken, //get from Device manufacturer
                'sxdmSn:  ' . $this->sxdmSn //get from Device serial number
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return  $response = json_decode($response, true);
    }
    public function postCURL($serviceCall, $post_json)
    {


        $sessionId = $this->getActiveSessionId();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->camera_sdk_url . $serviceCall,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>  $post_json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: sessionID=' . $sessionId,
                'sxdmToken: ' . $this->sxdmToken, //get from Device manufacturer
                'sxdmSn:  ' . $this->sxdmSn //get from Device serial number
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return  $response = json_decode($response, true);
    }

    public function getActiveSessionId()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->camera_sdk_url . '/api/auth/login/challenge?username=admin',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'sxdmToken: ' . $this->sxdmToken, //get from Device manufacturer
                'sxdmSn:  ' . $this->sxdmSn //get from Device serial number
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);
        if (isset($response["session_id"])) {
            return $response["session_id"];
        } else {
            return '';
        }
    }
}
