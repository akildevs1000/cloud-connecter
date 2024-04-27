<?php

namespace App\Http\Controllers\Shift;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ScheduleEmployee;
use Illuminate\Support\Facades\Log as Logger;

class ShiftRenderController extends Controller
{
    public function renderRequest(Request $request)
    {
        return $this->render($request->company_id ?? 0, $request->date ?? date("Y-m-d"));
    }
    public function getCompanyIds(Request $request)
    {
        $date = date("Y-m-d");
        $companies = Company::where("shift_type", 1)->pluck("id");
        foreach ($companies as $company) {
            $this->render($company->id, $date);
        }
    }

    public function render($id, $date)
    {
        $params = ["company_id" => $id, "date" => $date];

        $employees =  (new Employee)->attendanceEmployee($params);

        $logs =  (new AttendanceLog)->getLogs($params);

        $items = [];
        $multiItems = [];

        $isMulti = false;

        foreach ($employees as $row) {

            $params["logs"] = $logs[$row->system_user_id] ?? [];

            if (!$params["logs"]) {
                continue;
            }

            $params["system_user_id"] = $row->system_user_id;
            $params["isOverTime"] = $row->schedule->isOverTime;
            $params["shift"] = $row->schedule->shift ?? false;

            if ($row->schedule->shift_type_id == 1) {
                $items[] = $this->processFilo($params);
            }
            if ($row->schedule->shift_type_id == 2) {
                $isMulti = true;
                $multiItems[] = $this->processMulti($params);
            }
            if ($row->schedule->shift_type_id == 6) {
                $items[] = $this->processSingle($params);
            }
        }

        if ($isMulti) {
            AttendanceLog::whereIn("UserID", $multiItems)->where("company_id", $id)->update(["checked" => true]);
            $message =  "Log(s) has been render. Affected Ids: " . json_encode($multiItems);
            return $this->getMeta("All Shift", $message);
        }


        if (!count($items)) {
            $message = $this->getMeta("All Shift", "No Data Found.");
            return $message;
        }
        // Logger::channel("custom")->info(json_encode($items));


        try {
            $model = Attendance::query();
            $model->where("company_id", $id);
            $model->whereIn("employee_id", array_column($items, "employee_id"));
            $model->where("date", $date);
            $model->delete();
            $model->insert($items);
            AttendanceLog::whereIn("UserID", array_column($items, "employee_id"))->where("company_id", $id)->update(["checked" => true]);
            $message =  "Log(s) has been render. Affected Ids: " . json_encode(array_column($items, "employee_id"));
            return $this->getMeta("All Shift", $message);
        } catch (\Throwable $e) {
            return $e->getMessage();
            return $this->getMeta("All Shift", $e->getMessage());
        }
    }

    public function processFilo($params)
    {
        $firstLog = $params["logs"]->first();
        $lastLog = $params["logs"]->last();

        $item = $this->getDefaultCols($params);

        if ($firstLog && ($firstLog["log_type"] == "in" || $firstLog["log_type"] == "auto")) {
            $item["in"] = $firstLog["time"];
            $item["device_id_in"] = $firstLog["DeviceID"];
        }

        if ($lastLog && count($params["logs"]) > 1 && ($lastLog["log_type"] == "out" || $lastLog["log_type"] == "auto")) {
            $item["status"] = "P";
            $item["device_id_out"] = $lastLog["DeviceID"];
            $item["out"] = $lastLog["time"];
            $item["total_hrs"] = $this->getTotalHrsMins($firstLog["time"], $lastLog["time"]);

            if ($params["isOverTime"]) {
                $item["ot"] = $this->calculatedOT($item["total_hrs"], $params["shift"]->working_hours, $params["shift"]->overtime_interval);
            }
        }

        return $item;
    }

    public function processSingle($params)
    {
        $firstLog = $params["logs"]->first();
        $lastLog = $params["logs"]->last();

        $item = $this->getDefaultCols($params);

        if ($firstLog && ($firstLog["log_type"] == "in" || $firstLog["log_type"] == "auto")) {
            $items["late_coming"] =  $this->calculatedLateComing($firstLog["time"], $params["shift"]->on_duty_time, $params["shift"]->late_time);
            $item["in"] = $firstLog["time"];
            $item["device_id_in"] = $firstLog["DeviceID"];
        }

        if ($item["late_coming"] != "---") {
            $item["status"] = "LC";
        }

        if ($lastLog && count($params["logs"]) > 1 && ($lastLog["log_type"] == "out" || $lastLog["log_type"] == "auto")) {
            $item["status"] = "P";
            $item["device_id_out"] = $lastLog["DeviceID"];
            $item["out"] = $lastLog["time"];
            $item["total_hrs"] = $this->getTotalHrsMins($firstLog["time"], $lastLog["time"]);

            if ($params["isOverTime"]) {
                $item["ot"] = $this->calculatedOT($item["total_hrs"], $params["shift"]->working_hours, $params["shift"]->overtime_interval);
            }

            $item["early_going"] = $this->calculatedEarlyGoing($lastLog["time"], $params["shift"]->off_duty_time, $params["shift"]->early_time);

            if ($item["early_going"] != "---") {
                $item["status"] = "EG";
            }
        }

        return $item;
    }

    public function processMulti($params)
    {
        $items = [];

        $item = $this->getDefaultCols($params);


        $logsIns = $params["logs"]->where("log_type", "in");
        $logsOuts = $params["logs"]->where("log_type", "out");


        $data = $params["logs"];
        $item["status"] = count($data) % 2 !== 0 ?  Attendance::MISSING : Attendance::PRESENT;
        $item["logs"] = [];
        $item["total_hrs"] = 0;
        $totalMinutes = 0;

        for ($i = 0; $i < count($data); $i++) {
            $currentLog = $data[$i];
            $nextLog = isset($data[$i + 1]) ? $data[$i + 1] : false;

            $item["logs"][] =  [

                "in" => $currentLog['log_type'] != "out" ?  $currentLog['time'] : "---",
                "out" =>  $nextLog && $nextLog['log_type'] != "in" ?  $nextLog['time'] : "---",
                "diff" => $nextLog ? $this->minutesToHoursNEW($currentLog['time'], $nextLog['time']) : "---",
                "device_in" => $currentLog['device']['short_name'] ?? $currentLog['device']['name'] ??  "---",
                "device_out" => $nextLog['device']['short_name'] ?? $nextLog['device']['name'] ?? "---",
            ];

            if ((isset($currentLog['time']) && $currentLog['time'] != '---') and (isset($nextLog['time']) && $nextLog['time'] != '---')) {

                $parsed_out = strtotime($nextLog['time'] ?? 0);
                $parsed_in = strtotime($currentLog['time'] ?? 0);

                if ($parsed_in > $parsed_out) {
                    $parsed_out += 86400;
                }

                $diff = $parsed_out - $parsed_in;

                $minutes = floor($diff / 60);

                $totalMinutes += $minutes > 0 ? $minutes : 0;
            }

            $item["total_hrs"] = $this->minutesToHours($totalMinutes);

            if ($params["isOverTime"]) {
                $item["ot"] = $this->calculatedOT($item["total_hrs"], $params["shift"]->working_hours, $params["shift"]->overtime_interval);
            }

            try {
                $attendance = Attendance::whereDate("date", $item['date'])->where("employee_id", $item['employee_id'])->where("company_id", $item['company_id']);
                $found = $attendance->first();
                $found ? $attendance->update($item) : Attendance::create($item);
                $items[$item['employee_id']] = $item['employee_id'];
                $i++;
            } catch (\Throwable $e) {
                return $this->getMeta("All Shift", $e->getMessage());
            }
        }

        return array_values($items);
    }

    public function storeOrUpdate($items)
    {
    }


    public function getDefaultCols($params)
    {
        return [
            "total_hrs" => "---",
            "in" => "---",
            "out" => $params["logs"]->where("log_type", "out")->last()["time"] ?? "---",
            "ot" => "---",
            "device_id_in" => "---",
            "device_id_out" => "---",
            "date" => $params["date"],
            "company_id" => $params["company_id"],
            "employee_id" => $params["system_user_id"],
            "shift_id" => $params["shift"]["id"] ?? 0,
            "shift_type_id" => $params["shift"]["shift_type_id"]  ?? 0,
            "status" => "M",
            // "logs" => []
        ];
    }
}
