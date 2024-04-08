<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class CameraController extends Controller
{
    public function readLog(Request $request)
    {

        Log::channel('camera_log')->info(json_encode($request->all()));
    }

    public function readXml(Request $request)
    {
        $file = "log2023-10-12 15-29-41.txt";
        if ($request->file) {
            $file = $request->file;
        }
        $path = storage_path('app/cameraxml/' . $file);

        if (File::get($path)) {


            $content = File::get($path);
            $contentarr = explode('<?xml', $content);


            if ($contentarr[1]) {
                $content = "<?xml" . $contentarr[1];



                $xmlObject = simplexml_load_string($content);

                $jsonFormatData = json_encode($xmlObject);
                $result = json_decode($jsonFormatData, true);
                return view('camera.cameradata', ["data" => $result]);
                // dd($result['Face_0']['Snapshot']);
            }
        }
    }
}
