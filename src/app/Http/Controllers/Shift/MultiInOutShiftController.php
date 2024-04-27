<?php

namespace App\Http\Controllers\Shift;

use App\Models\Attendance;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\ScheduleEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;


class MultiInOutShiftController extends Controller
{

    public $update_date;

    public function processByManual(Request $request)
    {
        $shift_type_id = 2;

        $companyIds = $request->company_ids ?? [];

        $UserIDs = $request->UserIDs ?? [];

        $currentDate = $request->date ?? date('Y-m-d');

        $arr = [];

        $companies = $this->getModelDataByCompanyId($currentDate, $companyIds, $UserIDs, $shift_type_id);

        foreach ($companies as $company_id => $data) {
            // return ScheduleEmployee::where("company_id",$company_id)->delete();
            $arr[] = $this->processData($company_id, $data, $currentDate, $shift_type_id);
        }
        // return $arr;
        return "Logs Count " . array_sum($arr);
    }

    public function getModelDataByCompanyId($currentDate, $companyIds, $UserIDs, $shift_type_id)
    {
        $model = AttendanceLog::query();

        $model->where(function ($q) use ($currentDate, $companyIds, $UserIDs, $shift_type_id) {
            $q->where("checked", false);
            $q->where("company_id", '>', 0);

            $q->whereHas("schedule", function ($q) use ($shift_type_id, $currentDate) {
                $q->where('shift_type_id', $shift_type_id);
                $q->whereDate('from_date', "<=", $currentDate);
                $q->whereDate('to_date', ">=", $currentDate);
            });

            $q->when(count($companyIds) > 0, function ($q) use ($companyIds) {
                $q->whereIn("company_id", $companyIds);
            });

            $q->when(count($UserIDs) > 0, function ($q) use ($UserIDs) {
                $q->whereIn("UserID", $UserIDs);
            });

            $q->whereDate("LogTime", $currentDate);
        });

        $nextDate = date('Y-m-d', strtotime($currentDate . '+ 1 day'));

        $model->orWhere(function ($q) use ($nextDate, $companyIds, $UserIDs, $shift_type_id) {
            $q->where("checked", false);
            $q->where("company_id", '>', 0);

            $q->whereHas("schedule", function ($q) use ($shift_type_id, $nextDate) {
                $q->where('shift_type_id', $shift_type_id);
                $q->whereDate('from_date', "<=", $nextDate);
                $q->whereDate('to_date', ">=", $nextDate);
            });

            $q->when(count($companyIds) > 0, function ($q) use ($companyIds) {
                $q->whereIn("company_id", $companyIds);
            });

            $q->when(count($UserIDs) > 0, function ($q) use ($UserIDs) {
                $q->whereIn("UserID", $UserIDs);
            });

            $q->whereDate("LogTime", $nextDate);
        });

        // $model->with(["schedule"]);

        $model->orderBy("LogTime");

        return $model->get(["UserID", "company_id"])->groupBy(["company_id", "UserID"])->toArray();
    }

    public function getMultiInOutSchedule($currentDate, $companyId, $UserID, $shift_type_id)
    {
        $schedule = ScheduleEmployee::where('company_id', $companyId)
            ->where("employee_id", $UserID)
            ->where("shift_type_id", $shift_type_id)
            ->first();

        return $this->getSchedule($currentDate, $schedule);
    }

    public function getLogsWithInRange($companyId, $UserID, $range, $shift_type_id)
    {
        $model = AttendanceLog::query();
        $model->whereHas("schedule", function ($q) use ($shift_type_id) {
            $q->where('shift_type_id', $shift_type_id);
        });
        $model->where("company_id", $companyId);
        $model->where("UserID", $UserID);
        // $model->where("log_type", "auto");
        $model->whereBetween("LogTime", $range);
        $model->orderBy("LogTime");
        return $model->get(["id", "UserID", "LogTime", "DeviceID", "company_id"]);
    }

    public function processData($companyId, $data, $date, $shift_type_id)
    {
        $temp = [];
        $items = [];
        $UserIDs = [];

        foreach ($data as $UserID => $data) {

            $schedule = $this->getMultiInOutSchedule($date, $companyId, $UserID, $shift_type_id);

            if (!$schedule) {
                return $this->response("Employee with $UserID SYSTEM USER ID is not scheduled yet.", null, false);
            }

            $UserIDs[] = $UserID;

            $data = $this->getLogsWithInRange($companyId, $UserID, $schedule["range"], $shift_type_id);

            $temp = [
                "roster_id" => 0,
                "status" => count($data) == 1 ?  Attendance::MISSING : Attendance::PRESENT,
                // "status" => count($data)  % 2 !== 0 ?  Attendance::MISSING : Attendance::PRESENT,
                "shift_type_id" => $shift_type_id,
                "date" => $date,
                "company_id" => $companyId,
                "shift_id" => $schedule['shift_id'],
                "roster_id" => $schedule['roster_id'],
                "employee_id" => $UserID,
                "logs" => [],
                "total_hrs" => 0,
            ];

            $totalMinutes = 0;

            for ($i = 0; $i < count($data); $i++) {
                $currentLog = $data[$i];
                $nextLog = isset($data[$i + 1]) ? $data[$i + 1] : false;

                $temp["logs"][] =  [
                    "in" => $currentLog['time'],
                    "out" =>  $nextLog && $nextLog['time'] ? $nextLog['time'] : "---",
                    "diff" => $nextLog ? $this->minutesToHoursNEW($currentLog['time'], $nextLog['time']) : "---",

                    "device_in" => $currentLog['device']['short_name'] ?? $currentLog['device']['name'] ??  "---",
                    "device_out" => $nextLog['device']['short_name'] ?? $nextLog['device']['name'] ?? "---",
                    // $currentLog['LogTime'], $nextLog['time'] ?? "---"
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

                $temp["total_hrs"] = $this->minutesToHours($totalMinutes);

                if ($schedule['isOverTime']) {
                    $temp["ot"] = $this->calculatedOT($temp["total_hrs"], $schedule['working_hours'], $schedule['overtime_interval']);
                }

                $this->storeOrUpdate($temp);
                $items[] = $temp;

                $i++;
            }
        }

        return AttendanceLog::whereIn("UserID",  $UserIDs)->whereDate("LogTime", $date)->where("company_id", $companyId)->update(["checked" => true]);

        return $items;
    }
    public function storeOrUpdate($items)
    {
        $attendance = Attendance::whereDate("date", $items['date'])->where("employee_id", $items['employee_id'])->where("company_id", $items['company_id']);
        $found = $attendance->first();
        return $found ? $attendance->update($items) : Attendance::create($items);
    }

    public function render()
    {
        $shift_type_id = 2;

        $companyIds = Company::pluck("id") ?? [];

        $UserIDs = [];

        $currentTimestamp = date('Y-m-d H:i:s');

        $condtionTimestamp = date("Y-m-d 07:59");

        $currentDate = $currentTimestamp < $condtionTimestamp ? date('Y-m-d', strtotime('yesterday')) : date('Y-m-d');

        $companies = $this->getModelDataByCompanyId($currentDate, $companyIds, $UserIDs, $shift_type_id);

        $arr = [];

        foreach ($companies as $company_id => $data) {
            $arr[] = $this->processData($company_id, $data, $currentDate, $shift_type_id);
            // $result += $this->processData($company_id, $data, $currentDate, $shift_type_id);
        }

        // return $arr;

        info("MultiShift: Log(s) has been render. Data: " . array_sum($arr));
        return "MultiShift: Log(s) has been render. Data: " . array_sum($arr);
    }

    public function renderData(Request $request)
    {
        // Extract start and end dates from the JSON data
        $startDateString = $request->dates[0];
        // $endDateString = $request->dates[1];
        if (isset($request->dates[1])) {
            $endDateString = $request->dates[1];
        } else {
            $endDateString = $request->dates[0];
        }
        $company_ids = $request->company_ids;
        $employee_ids = $request->employee_ids;

        // Convert start and end dates to DateTime objects
        $startDate = new \DateTime($startDateString);
        $endDate = new \DateTime($endDateString);
        $currentDate = new \DateTime();

        $arr = [];

        $params = [
            "company_ids" => $company_ids,
            "employee_ids" => $employee_ids,
            "shift_type_id" => 2,
            "checked" => true
        ];

        while ($startDate <= $currentDate && $startDate <= $endDate) {

            $params["date"] = $startDate;
            $arr[] = $this->prepareAttendanceRecords($params);
            $startDate->modify('+1 day');
        }

        return $arr;
    }

    public function prepareAttendanceRecords($params)
    {
        $employeesByType = (new ScheduleEmployee)->getEmployeesByType($params);

        $companyIdWithUserIds = (new AttendanceLog)->getEmployeeIdsForNewLogs($params);

        $logs = (new AttendanceLog)->getLogsByUser($params);

        $items = [];

        foreach ($companyIdWithUserIds as $companyIdWithUserId) {

            $filteredLogs = $logs[$companyIdWithUserId->company_id][$companyIdWithUserId->UserID] ?? false;


            if (!$filteredLogs || $filteredLogs->isEmpty()) {
                continue;
            }

            $schedule = $employeesByType[$companyIdWithUserId->company_id][$companyIdWithUserId->UserID][0] ?? false;

            if (!$schedule) {
                continue;
            }


            $shift = $schedule["shift"];

            $temp = [
                "logs" => [],
                "total_hrs" => 0,
                "out" => "---",
                "ot" => "---",
                "company_id" => $companyIdWithUserId->company_id,
                "date" => $params["date"]->format('Y-m-d'),
                "employee_id" => $companyIdWithUserId->UserID,
                "shift_type_id" => $params["shift_type_id"],
                "shift_id" => $schedule["shift_id"],
                "roster_id" => $schedule["roster_id"],
                "status" => count($filteredLogs)  % 2 !== 0 ?  Attendance::MISSING : Attendance::PRESENT,
            ];

            $totalMinutes = 0;

            $data = $filteredLogs;

            for ($i = 0; $i < count($data); $i++) {
                $currentLog = $data[$i];
                $nextLog = isset($data[$i + 1]) ? $data[$i + 1] : false;

                $temp["logs"][] =  [
                    "in" => $currentLog['time'],
                    "out" =>  $nextLog && $nextLog['time'] ? $nextLog['time'] : "---",
                    "diff" => $nextLog ? $this->minutesToHoursNEW($currentLog['time'], $nextLog['time']) : "---",
                    // $currentLog['LogTime'], $nextLog['time'] ?? "---"
                ];

                if ((isset($currentLog['time']) && $currentLog['time'] != '---') and (isset($nextLog['time']) && $nextLog['time'] != '---')) {

                    $parsed_out = strtotime($nextLog['time'] ?? 0);
                    $parsed_in = strtotime($currentLog['time'] ?? 0);

                    $diff = $parsed_out - $parsed_in;

                    $minutes = floor($diff / 60);

                    $totalMinutes += $minutes > 0 ? $minutes : 0;
                }

                $temp["total_hrs"] = $this->minutesToHours($totalMinutes);


                if ($schedule["isOverTime"]) {
                    $temp["ot"] = $this->calculatedOT($temp["total_hrs"], $shift->working_hours, $shift->overtime_interval);
                }

                $this->storeOrUpdate($temp);

                // $items[] = $temp;
                $i++;
            }
        }
        return "(Multi Shift) " . $params['date']->format('d-M-y') . ": Log(s) has been render. Affected Ids: " . json_encode($params["employee_ids"]);

        return array_values($items);
    }

    public function renderByLogType($id, $date)
    {
        $params = ["company_id" => $id, "date" => $date];

        $employees = (new Employee)->attendanceEmployee($params);


        $logs = AttendanceLog::whereDate("LogTime", $params["date"])
            ->where("company_id", $params["company_id"])
            ->where("checked", false)
            ->whereIn("log_type", ["in", "out"])
            ->distinct("LogTime", "UserID", "company_id")
            ->whereHas("schedule", function ($q) {
                $q->where("shift_type_id", 2);
            })
            ->get()
            ->groupBy(['UserID']);

        $items = [];


        if (!count($logs)) {
            return $this->getMeta("Multi Shift", "No record found");
        };

        foreach ($employees as $row) {

            $params["isOverTime"] = $row->schedule->isOverTime;
            $params["shift"] = $row->schedule->shift ?? false;
            if ($row->schedule->shift_type_id == 2 ?? false) {

                $data = $logs[$row->system_user_id] ?? [];

                if (!count($data)) {
                    $items[] = $this->getMeta("All Shift", "No record found" . $row->system_user_id);
                };

                $item = [
                    "total_hrs" => 0,
                    "in" => "---",
                    "out" => "---",
                    "ot" => "---",
                    "device_id_in" => "---",
                    "device_id_out" => "---",
                    "date" => $params["date"],
                    "company_id" => $params["company_id"],
                    "employee_id" => $row->system_user_id,
                    "shift_id" => $params["shift"]["id"] ?? 0,
                    "shift_type_id" => $params["shift"]["shift_type_id"]  ?? 0,
                    "status" => "A",
                    "logs" =>  []
                ];

                $item["status"] = count($data) % 2 !== 0 ?  Attendance::MISSING : Attendance::PRESENT;
                $totalMinutes = 0;

                for ($i = 0; $i < count($data); $i++) {
                    $currentLog = $data[$i];
                    $nextLog = isset($data[$i + 1]) ? $data[$i + 1] : false;

                    $item["logs"][] =  [

                        "in" => $currentLog['log_type'] != "out" ?  $currentLog['time'] : "---",
                        "out" =>  $nextLog && $nextLog['log_type'] != "in" ?  $nextLog['time'] : "---",
                        // "diff" => $nextLog ? $this->minutesToHoursNEW($currentLog['time'], $nextLog['time']) : "---",
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
            }
        }
        $UserIds = array_keys($logs->toArray());
        AttendanceLog::whereIn("UserID", $UserIds)->where("company_id", $id)->update(["checked" => true]);
        $message =  "Log(s) has been render. Affected Ids: " . json_encode($UserIds);
        return $this->getMeta("All Shift", $message);
    }
}
