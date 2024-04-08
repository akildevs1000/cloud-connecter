<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['show_log_time', "time", "date", "edit_date", "hour_only"];

    protected $casts = [
        // 'LogTime' => 'datetime:d-M-y h:i:s:a',
    ];

    public function getTimeAttribute()
    {
        return date("H:i", strtotime($this->LogTime));
    }

    public function getHourOnlyAttribute()
    {
        return date("H", strtotime($this->LogTime));
    }

    public function getShowLogTimeAttribute()
    {
        return strtotime($this->LogTime);
    }

    public function getDateAttribute()
    {
        return date("d-M-y", strtotime($this->LogTime));
    }

    public function getEditDateAttribute()
    {
        return date("Y-m-d", strtotime($this->LogTime));
    }

    public function device()
    {
        return $this->belongsTo(Device::class, "DeviceID", "device_id")->withDefault(["name" => "Manual", "device_id" => "Manual"]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "UserID", "system_user_id");
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, "UserID", "system_user_id");
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }

    public function schedule()
    {
        return $this->belongsTo(ScheduleEmployee::class, "UserID", "employee_id")->withOut(["shift_type"]);
    }

    public function reason()
    {
        return $this->morphOne(Reason::class, 'reasonable');
    }
    public function last_reason()
    {
        return $this->hasOne(Reason::class, "id", "reasonable_id")->latest();
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class, "UserID", "system_user_id")->with("zone");
    }
    public function tenant()
    {
        return $this->belongsTo(Visitor::class, "UserID", "system_user_id")->with("zone");
    }

    public function filter($request)
    {
        $model = self::query();

        $model->where("company_id", $request->company_id);
        $model->when(request()->filled("age_category"), fn ($q) => $q->where("age_category", 'like', '%' . request("age_category") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("user_type"), fn ($q) => $q->where("user_type", 'like', '%' . request("user_type") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Clarity"), fn ($q) => $q->where("Clarity", 'like', '%' . request("Clarity") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Age"), fn ($q) => $q->where("Age", 'like', '%' . request("Age") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Quality"), fn ($q) => $q->where("Quality", 'like', '%' . request("Quality") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Gender"), fn ($q) => $q->where("Gender", 'like', '%' . request("Gender") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Similarity"), fn ($q) => $q->where("Similarity", 'like', '%' . request("Similarity") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("branch_id"), fn ($q) => $q->where("branch_id", 'like', '%' . request("branch_id") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("UserID"), fn ($q) => $q->where("UserID", 'like', '%' . request("UserID") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("DeviceID"), fn ($q) => $q->where("DeviceID", 'like', '%' . request("DeviceID") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("customer"), fn ($q) => $q->where("customer", 'like', '%' . request("customer") . '%')->where('company_id', $request->company_id));

        $model->when($request->from_date, function ($query) use ($request) {
            return $query->where('LogTime', '>=', $request->from_date);
        });

        $model->when($request->to_date, function ($query) use ($request) {
            return $query->where('LogTime', '<=',   date("Y-m-d", strtotime($request->to_date . " +1 day")));
        });

        $model->with('customer', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });
        $model->with('branch', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });
        $model->with('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->withOut(["schedule", "sub_department", "designation", "user"]);

            $q->select(
                "first_name",
                "last_name",
                "profile_picture",
                "employee_id",
                "branch_id",
                "system_user_id",
                "display_name",
                "timezone_id",
                "department_id"
            );
        });

        $model->with('device', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->when($request->filled('dates') && count($request->dates) > 1, function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                $query->where('LogTime', '>=', $request->dates[0])
                    ->where('LogTime', '<=',   date("Y-m-d", strtotime($request->dates[1] . " +1 day")));
            });
        });

        $model->when($request->filled('employee_first_name'), function ($q) use ($request) {
            $key = strtolower($request->employee_first_name);
            $q->whereHas('employee', fn (Builder $query) => $query->where('first_name', 'ILIKE', "$key%"));
        });

        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');
            if (strpos($request->sortBy, '.')) {
                if ($request->sortBy == 'employee.first_name') {
                    $q->orderBy(Employee::select("first_name")->where("company_id", $request->company_id)->whereColumn("employees.system_user_id", "attendance_logs.UserID"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else if ($request->sortBy == 'device.name') {
                    $q->orderBy(Device::select("name")->where("company_id", $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else if ($request->sortBy == 'device.location') {
                    $q->orderBy(Device::select("location")->where("company_id", $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
                }
                // } else if ($request->sortBy == 'employee.department') {
                //     $q->orderBy(Employee::withOut(['schedule', 'department', 'sub_department', 'designation', 'user', 'role'])
                //             ->join('departments', 'departments.id', '=', 'employees.department_id')
                //             ->join('attendance_logs', 'attendance_logs.UserID', '=', 'employees.system_user_id')
                //             ->select('departments.name')
                //             ->distinct()
                //             ->where('attendance_logs.company_id', $request->company_id)
                //             ->when($request->from_date, function ($query) use ($request) {
                //                 return $query->whereDate('LogTime', '>=', $request->from_date);
                //             })
                //             ->when($request->to_date, function ($query) use ($request) {
                //                 return $query->whereDate('LogTime', '<=', $request->to_date);
                //             })
                //         , $sortDesc == 'true' ? 'desc' : 'asc');

                //
                //}

            } else {
                $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                }
            }
        });
        if (!$request->sortBy) {
            $model->orderBy('LogTime', 'DESC');
        }

        return $model;
    }


    public function statsFilter($request)
    {
        $model = self::query();

        $model->where("company_id", $request->company_id);
        $model->when(request()->filled("age_category"), fn ($q) => $q->where("age_category", 'like', '%' . request("age_category") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("user_type"), fn ($q) => $q->where("user_type", 'like', '%' . request("user_type") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Clarity"), fn ($q) => $q->where("Clarity", 'like', '%' . request("Clarity") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Age"), fn ($q) => $q->where("Age", 'like', '%' . request("Age") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Quality"), fn ($q) => $q->where("Quality", 'like', '%' . request("Quality") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Gender"), fn ($q) => $q->where("Gender", 'like', '%' . request("Gender") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("Similarity"), fn ($q) => $q->where("Similarity", 'like', '%' . request("Similarity") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("branch_id"), fn ($q) => $q->where("branch_id", 'like', '%' . request("branch_id") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("UserID"), fn ($q) => $q->where("UserID", 'like', '%' . request("UserID") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("DeviceID"), fn ($q) => $q->where("DeviceID", 'like', '%' . request("DeviceID") . '%')->where('company_id', $request->company_id));
        $model->when(request()->filled("customer"), fn ($q) => $q->where("customer", 'like', '%' . request("customer") . '%')->where('company_id', $request->company_id));

        $model->when($request->from_date, function ($query) use ($request) {
            return $query->where('LogTime', '>=', $request->from_date);
        });

        $model->when($request->to_date, function ($query) use ($request) {
            return $query->where('LogTime', '<=',   date("Y-m-d", strtotime($request->to_date . " +1 day")));
        });

        $model->with('customer', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });
        $model->with('branch', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });
        $model->with('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->withOut(["schedule", "sub_department", "designation", "user"]);

            $q->select(
                "first_name",
                "last_name",
                "profile_picture",
                "employee_id",
                "branch_id",
                "system_user_id",
                "display_name",
                "timezone_id",
                "department_id"
            );
        });

        $model->with('device', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->when($request->filled('dates') && count($request->dates) > 1, function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                $query->where('LogTime', '>=', $request->dates[0])
                    ->where('LogTime', '<=',   date("Y-m-d", strtotime($request->dates[1] . " +1 day")));
            });
        });

      

        return $model;
    }

    public function getEmployeeIdsForNewLogs($params)
    {
        return self::
            // where("checked", false)->
            when(count($params["company_ids"] ?? []) > 0, function ($q) use ($params) {
                $q->whereIn("company_id", $params["company_ids"]);
            })
            ->when(count($params["employee_ids"] ?? []) > 0, function ($q) use ($params) {
                $q->whereIn("UserID", $params["employee_ids"]);
            })
            ->whereDate("LogTime", $params["date"]->format('Y-m-d'))
            ->distinct("LogTime", "UserID", "company_id")
            ->get(['company_id', 'UserID']);
    }

    public function getLogsByUser($params)
    {
        return self::whereDate("LogTime", $params["date"]->format('Y-m-d'))
            ->distinct("LogTime", "UserID", "company_id")
            ->get()
            ->groupBy(['company_id', 'UserID']);
    }

    public function getLogs($params)
    {
        return self::whereDate("LogTime", $params["date"])
            ->where("company_id", $params["company_id"])
            ->distinct("LogTime", "UserID", "company_id")
            ->get()
            ->groupBy(['UserID']);
    }

    public function getEmployeeIdsForNewLogsToRender_old($params)
    {
        return self::where("company_id", $params["company_id"])
            ->when(!$params["custom_render"], fn ($q) => $q->where("checked", false))
            ->where("company_id", $params["company_id"])
            ->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day"))) // Check for logs on or before the next date

            ->distinct("UserID", "company_id")
            ->pluck('UserID');
    }
    public function getEmployeeIdsForNewLogsToRender($params)
    {
        return self::where("company_id", $params["company_id"])
            ->when(!$params["custom_render"], fn ($q) => $q->where("checked", false))
            ->where("company_id", $params["company_id"])
            ->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day"))) // Check for logs on or before the next date
            ->whereNotIn('UserID', function ($query) {
                $query->select('system_user_id')
                    ->where('visit_from', "<=", date('Y-m-d'))
                    ->where('visit_to', ">=", date('Y-m-d'))

                    ->from('visitors');
            })
            ->whereHas("schedule", fn ($q) => $q->where("isAutoShift", false))
            ->distinct("UserID", "company_id")
            ->pluck('UserID');
    }
    public function getEmployeeIdsForNewLogsNightToRender($params)
    {


        return self::where("company_id", $params["company_id"])
            ->when(!$params["custom_render"], fn ($q) => $q->where("checked", false))
            ->where("company_id", $params["company_id"])
            ->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +2 day"))) // Check for logs on or before the next date
            ->whereNotIn('UserID', function ($query) {
                $query->select('system_user_id')
                    ->where('visit_from', "<=", date('Y-m-d'))
                    ->where('visit_to', ">=", date('Y-m-d'))

                    ->from('visitors');
            })
            ->whereHas("schedule", fn ($q) => $q->where("isAutoShift", false))
            ->distinct("UserID", "company_id")
            ->pluck('UserID');
    }
    public function getEmployeeIdsForNewLogsToRenderAuto($params)
    {


        return self::where("company_id", $params["company_id"])
            ->when(!$params["custom_render"], fn ($q) => $q->where("checked", false))
            ->when(count($params["UserIds"]) > 0, fn ($q) => $q->whereIn("UserID", $params["UserIds"]))
            ->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day"))) // Check for logs on or before the next date
            ->whereNotIn('UserID', function ($query) {
                $query->select('system_user_id')
                    ->where('visit_from', "<=", date('Y-m-d'))
                    ->where('visit_to', ">=", date('Y-m-d'))
                    ->from('visitors');
            })
            ->whereHas("schedule", fn ($q) => $q->where("isAutoShift", true))
            //->whereHas("device", fn ($q) => $q->whereIn("function", ["In", "all", "auto"]))
            ->whereHas('device', fn ($q) => $q->where('device_type', '!=', 'Access Control'))
            ->orderBy("LogTime", "asc")
            ->with(["employee" => function ($query) {
                $query->withOut("schedule", "department", "designation", "sub_department", "user", "branch");
                $query->select("branch_id", "employee_id", "system_user_id");
            }])
            ->get()->groupBy("UserID");
    }
    public function getVisitorIdsForNewLogsToRender($params)
    {
        return self::where("company_id", $params["company_id"])
            ->when(!$params["custom_render"], fn ($q) => $q->where("checked", false))
            ->where("company_id", $params["company_id"])
            ->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day"))) // Check for logs on or before the next date
            ->whereIn('UserID', function ($query) {
                $query->select('system_user_id')
                    ->where('visit_from', "<=", date('Y-m-d'))
                    ->where('visit_to', ">=", date('Y-m-d'))
                    ->from('visitors');
            })
            ->distinct("UserID", "company_id")
            ->pluck('UserID');
    }


    public function getLogsForRender($params)
    {


        return self::with("visitor")->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day")))
            ->whereIn("UserID", $params["UserIds"])
            ->where("company_id", $params["company_id"])
            ->distinct("LogTime", "UserID", "company_id")
            ->get()
            ->load("device")
            ->load(["schedule" => function ($q) use ($params) {
                $q->where("company_id", $params["company_id"]);
                $q->where("to_date", ">=", $params["date"]);
                $q->where("shift_type_id", $params["shift_type_id"]);
                $q->withOut("shift_type");
                // $q->select("shift_id", "isOverTime", "employee_id", "shift_type_id", "shift_id", "shift_id");
                $q->orderBy("to_date", "asc");
            }])
            ->groupBy(['UserID']);
    }

    public function getLogsWithInRangeNew1($params)
    {


        return AttendanceLog::where("company_id", $params["company_id"])
            ->where("LogTime", ">=", $params["date"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day")) . ' ') // Check for logs on or before the next date
            ->distinct("LogTime", "UserID", "company_id")
            ->when($params["UserIds"] != null && count($params["UserIds"]) > 0, function ($query) use ($params) {
                return $query->whereIn('UserID', $params["UserIds"]);
            })

            ->whereHas("schedule", function ($q) use ($params) {
                $q->where("shift_type_id", $params["shift_type_id"]);
            })
            ->orderBy("LogTime", 'asc')
            ->get()

            ->groupBy(['UserID']);
    }
    public function getLogsWithInRangeNightShiftTimings($params)
    {


        if ($params["shift"]->off_duty_time < $params["shift"]->on_duty_time) {
            $params["start"] = $params["date"] . " " . $params["shift"]->on_duty_time;
            $params["end"] = date("Y-m-d", strtotime($params["date"] . " +1 day")) . " " . $params["shift"]->off_duty_time;
        } else {
            $params["start"] = $params["date"] . " " . $params["shift"]->on_duty_time;
            $params["end"] = date("Y-m-d", strtotime($params["date"])) . " " . $params["shift"]->off_duty_time;
        }
        //->whereBetween("LogTime", [$params["start"], $params["end"]])



        $return = AttendanceLog::where("company_id", $params["company_id"])
            // ->whereBetween("LogTime", [$params["start"], $params["end"]])
            ->where("LogTime", ">=", $params["start"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", $params["end"])
            ->distinct("LogTime", "UserID", "company_id")
            ->whereHas("schedule", function ($q) use ($params) {
                $q->where("shift_type_id", $params["shift_type_id"]);
            })
            ->when($params["UserIds"] != null && count($params["UserIds"]) > 0, function ($query) use ($params) {
                return $query->whereIn('UserID', $params["UserIds"]);
            })

            ->orderBy("LogTime", 'asc')
            ->get()
            ->load("device")
            ->load(["schedule" => function ($q) use ($params) {
                $q->where("company_id", $params["company_id"]);
                $q->where("to_date", ">=", $params["date"]);
                $q->where("shift_type_id", $params["shift_type_id"]);
                $q->withOut("shift_type");
                // $q->select("shift_id", "isOverTime", "employee_id", "shift_type_id", "shift_id", "shift_id");
                $q->orderBy("to_date", "asc");
            }])
            ->groupBy(['UserID']);

        return $return;
    }
    public function getLogsWithInRangeNew($params)
    {


        if ($params["shift"]->off_duty_time < $params["shift"]->on_duty_time) {
            $params["start"] = $params["date"] . " " . $params["shift"]->on_duty_time;
            $params["end"] = date("Y-m-d", strtotime($params["date"] . " +1 day")) . " " . $params["shift"]->off_duty_time;
        } else {
            $params["start"] = $params["date"] . " " . $params["shift"]->on_duty_time;
            $params["end"] = date("Y-m-d", strtotime($params["date"])) . " " . $params["shift"]->off_duty_time;
        }
        //->whereBetween("LogTime", [$params["start"], $params["end"]])

        $return = AttendanceLog::where("company_id", $params["company_id"])
            // ->whereBetween("LogTime", [$params["start"], $params["end"]])
            ->where("LogTime", ">=", $params["start"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", $params["end"])
            ->distinct("LogTime", "UserID", "company_id")
            ->whereHas("schedule", function ($q) use ($params) {
                $q->where("shift_type_id", $params["shift_type_id"]);
            })
            ->when($params["UserIds"] != null && count($params["UserIds"]) > 0, function ($query) use ($params) {
                return $query->whereIn('UserID', $params["UserIds"]);
            })
            ->orderBy("LogTime", 'asc')
            ->get()
            ->load("device")
            ->groupBy(['UserID']);

        return $return;



        //     return  $results = DB::select("
        //     SELECT *
        //     FROM attendance_logs
        //     WHERE company_id = 20 
        //         AND 'LogTime' >= '2023-11-30 08:00' 
        //         AND 'LogTime' <= '2023-12-01 01:00' 

        //     ORDER BY 'LogTime' ASC;
        // ");
        return AttendanceLog::where("company_id", $params["company_id"])

            ->where("LogTime", ">=", $params["start"]) // Check for logs on or after the current date
            ->where("LogTime", "<=", $params["end"])
            ->distinct("LogTime", "UserID", "company_id")
            ->whereHas("schedule", function ($q) use ($params) {
                $q->where("shift_type_id", $params["shift_type_id"]);
            })
            ->when($params["UserIds"] != null && count($params["UserIds"]) > 0, function ($query) use ($params) {
                return $query->whereIn('UserID', $params["UserIds"]);
            })
            ->orderBy("LogTime", 'asc')
            ->get()

            ->groupBy(['UserID']);
    }
}
