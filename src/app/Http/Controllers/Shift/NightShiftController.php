<?php

namespace App\Http\Controllers\Shift;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Http\Controllers\Controller;
use App\Models\Employee;

class NightShiftController extends Controller
{
    public function renderData(Request $request)
    {
        // Extract start and end dates from the JSON data
        $startDateString = $request->dates[0];
        //$endDateString = $request->dates[1];
        if (isset($request->dates[1])) {
            $endDateString = $request->dates[1];
        } else {
            $endDateString = $request->dates[0];
        }
        $company_id = $request->company_ids[0];
        $employee_ids = $request->employee_ids;

        // Convert start and end dates to DateTime objects
        $startDate = new \DateTime($startDateString);
        $endDate = new \DateTime($endDateString);
        $currentDate = new \DateTime();

        $response = [];

        // while ($startDate <= $currentDate && $startDate <= $endDate) {
        while ($startDate <= $endDate) {
            //$response[] = $this->render($company_id, $startDate->format("Y-m-d"), 4, $employee_ids, true);
            $response[] = $this->render($company_id, $startDate->format("Y-m-d"), 4, $employee_ids, $request->filled("auto_render") ? false : true);

            $startDate->modify('+1 day');
        }

        return $response;
    }

    public function renderRequest(Request $request)
    {
        return $this->render($request->company_id ?? 0, $request->date ?? date("Y-m-d"), $request->shift_type_id, $request->UserIds, true);
    }

    public function render($id, $date, $shift_type_id, $UserIds = [], $custom_render = false)
    {
        $params = [
            "company_id" => $id,
            "date" => $date,
            "shift_type_id" => $shift_type_id,
            "custom_render" => $custom_render,
            "UserIds" => $UserIds,
        ];

        if (!$custom_render) {
            //$params["UserIds"] = (new AttendanceLog)->getEmployeeIdsForNewLogsToRender($params);
            $params["UserIds"] = (new AttendanceLog)->getEmployeeIdsForNewLogsNightToRender($params);
        }

        $logsEmployees =  (new AttendanceLog)->getLogsForRender($params);

        //update atendance table with shift ID if shift with employee not found 
        if (count($logsEmployees) == 0) {
            $employees = (new Employee())->GetEmployeeWithShiftDetails($params);
            foreach ($employees as $key => $value) {
                if ($value->schedule->shift && $value->schedule->shift["id"] > 0) {
                    $data1 = [
                        "shift_id" => $value->schedule->shift["id"],
                        "shift_type_id" => $value->schedule->shift["shift_type_id"]
                    ];
                    $model1 = Attendance::query();
                    $model1->whereIn("employee_id", $UserIds);
                    $model1->where("date", $params["date"]);
                    $model1->where("company_id", $params["company_id"]);
                    $model1->update($data1);
                }
            }
        }

        $items = [];
        $keys = [];
        $message = "";
        foreach ($logsEmployees as $key => $logs) {

            $logs = $logs->toArray() ?? [];

            $firstLog = collect($logs)->filter(function ($record) {
                return isset($record["device"]["function"]) && ($record["device"]["function"] != "Out");
            })->first();


            $schedule = $firstLog["schedule"] ?? false;

            $shift = $schedule["shift"] ?? false;

            if (!$schedule) {
                $keys[] = $key;
                $message .= ".  No schedule is mapped with combination  System User Id: $key   and Date : " . $params["date"] . " ";
                continue;
            }
            if (!$firstLog["schedule"]["shift_type_id"]) {
                $keys[] = $key;
                $message .= "$key : None f=of the  Master shift configured on  date:" . $params["date"];
                continue;
            }

            $beginningIn =  $params['date'] . ' ' . $shift["beginning_in"];
            $beginningOut = $params['date'] . ' ' . $shift["beginning_out"];


            if ($firstLog['LogTime'] < $beginningIn || $firstLog['LogTime'] > $beginningOut) {
                $keys[] = $key;
                $message .= "{$key} LogTime({$firstLog["LogTime"]}) is out of range ({$beginningIn} to {$beginningOut})";
                $message .= " Device: {$firstLog["DeviceID"]}";
                continue;
            }

            $item = [
                "roster_id" => 0,
                "total_hrs" => "---",
                "in" => $firstLog["time"] ?? "---",
                "out" =>  "---",
                "ot" => "---",
                "device_id_in" =>  $firstLog["DeviceID"] ?? "---",
                "device_id_out" => "---",
                "date" => $params["date"],
                "company_id" => $params["company_id"],
                "employee_id" => $key,
                "shift_id" => $firstLog["schedule"]["shift_id"] ?? 0,
                "shift_type_id" => $firstLog["schedule"]["shift_type_id"] ?? 0,
                "status" => "M",
                "late_coming" => "---",
                "early_going" => "---",
            ];

            if ($shift && $item["shift_type_id"] == 4) {
                $item["late_coming"] =  $this->calculatedLateComing($item["in"], $shift["on_duty_time"], $shift["late_time"]);

                if ($item["late_coming"] != "---") {
                    $item["status"] = "LC";
                }
            }

            $lastLog = $this->getLogsForOutOnly(
                $item["company_id"],
                $key,
                $item["date"],
                $shift,
                $custom_render
            );

            if ($lastLog) {

                $function = $lastLog["device"]["function"];

                if (!isset($function) && ($function !== "In")) {
                    $keys[] = $key;
                    $message .= " $key : Wrong Punch Out(" . $lastLog["LogTime"] . ") from (" . $lastLog["DeviceID"] . ")";
                    continue;
                }


                $lastLogSchedule = $lastLog["schedule"] ?? false;
                $lastLogShift = $lastLogSchedule["shift"] ?? false;


                $endingIn =  date("Y-m-d", strtotime($params['date'] . " +1 day")) . " " . $lastLogShift["ending_in"];
                $endingOut =  date("Y-m-d", strtotime($params['date'] . " +1 day")) . " " . $lastLogShift["ending_out"];


                if ($lastLog['LogTime'] < $endingIn || $lastLog['LogTime'] > $endingOut) {
                    $keys[] = $key;
                    $message .= "{$key} LogTime({$lastLog["LogTime"]}) is out of range ({$endingIn} to {$endingOut})";
                    $message .= " Device: {$lastLog["DeviceID"]}";
                    continue;
                }



                $item["status"] = "P";
                $item["device_id_out"] = $lastLog["DeviceID"] ?? "---";
                $item["out"] = $lastLog["time"] ?? "---";

                if ($item["out"] !== "---") {
                    $item["total_hrs"] = $this->calculatedHours($item["in"], $item["out"]);
                }

                if ($schedule["isOverTime"] ?? false) {
                    $item["ot"] = $this->calculatedOT($item["total_hrs"], $lastLogShift["working_hours"], $lastLogShift["overtime_interval"]);
                }

                if ($item["shift_type_id"] == 4) {


                    if ($shift["halfday"] == date("l")) {

                        $time2 = $lastLogShift["on_duty_time"];
                        $time1 = $lastLogShift["halfday_working_hours"];
                        $lastLogShift["off_duty_time"] = gmdate("H:i", (strtotime($time1) - strtotime('00:00')) + strtotime($time2) - strtotime('00:00'));
                    }

                    $item["early_going"] = $this->calculatedEarlyGoing($item["out"], $lastLogShift["off_duty_time"], $lastLogShift["early_time"]);

                    if ($item["early_going"] != "---") {
                        $item["status"] = "EG";
                    }
                }
            }

            $items[] = $item;
        }

        if (!count($logsEmployees)) {
            $message = '[' . $date . " " . date("H:i:s") . '] Night Shift: No data found.';
            $this->devLog("render-manual-log", $message);
            return $message;
        }

        // if (count($keys)) {
        //     $message = '[' . $date . " " . date("H:i:s") . '] Night Shift: ' . $message;
        //     $this->devLog("render-manual-log", $message);
        //     return $message;
        // }

        try {
            $UserIds = array_column($items, "employee_id");
            $model = Attendance::query();
            $model->where("company_id", $id);
            $model->whereIn("employee_id", $UserIds);
            $model->where("date", $date);
            $model->delete();
            $model->insert($items);

            //if (!$custom_render) 
            {
                // AttendanceLog::where("company_id", $id)->whereIn("UserID", $UserIds)->update(["checked" => true, "checked_datetime" => date('Y-m-d H:i:s')]);
                AttendanceLog::where("company_id", $id)->whereIn("UserID", $UserIds)
                    ->where("LogTime", ">=", $date . ' 00:00:00')
                    ->where("LogTime", "<=", $date . ' 23:59:00')
                    ->update(["checked" => true, "checked_datetime" => date('Y-m-d H:i:s')]);
            }
            $message = "[" . $date . " " . date("H:i:s") .  "] Night Shift. Affected Ids: " . json_encode($UserIds) . " " . $message;
        } catch (\Throwable $e) {
            $message = "[" . $date . " " . date("H:i:s") .  "] Night Shift. " . $e->getMessage();
        }

        $this->devLog("render-manual-log", $message);
        return ($message);
    }


    public function getLogsForOutOnly($company_id, $UserId, $date, $shift, $custom_render)
    {
        $model = AttendanceLog::query();
        $model->when(!$custom_render, fn ($q) => $q->where("checked", false));
        $model->where("company_id", $company_id);
        $model->where("UserID", $UserId);
        $model->where("LogTime", ">=", date("Y-m-d", strtotime($date . " +1 day")) . " " . $shift["ending_in"]);
        $model->where("LogTime", "<=", date("Y-m-d", strtotime($date . " +1 day")) . " " . $shift["ending_out"]);
        $model->distinct("LogTime", "UserID", "company_id");
        $model->orderBy("LogTime", "desc");
        $model->whereHas("device", fn ($q) => $q->whereIn("function", ["Out", "all"]));
        $model->with(["schedule", "device"]);
        return $model->first();
    }

    public function calculatedHours($in, $out)
    {

        $diff = abs(((strtotime($in)) - (strtotime($out) + 86400)));
        $h = floor($diff / 3600);
        $m = floor(($diff % 3600) / 60);
        return (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
    }
}
