<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    const ABSENT = "A"; //1;
    const PRESENT = "P"; //2;
    const MISSING = "M"; //3;

    protected $guarded = [];

    protected $appends = [
        "edit_date",
        "day",
    ];

    protected $casts = [
        'date' => 'date',
        'logs' => 'array',
        'shift_type_id' => 'integer',
    ];

    protected $hidden = ["branch_id", "created_at", "updated_at"];
    // protected $hidden = ["company_id", "branch_id", "created_at", "updated_at"];

    public function shift()
    {
        return $this->belongsTo(Shift::class)->withOut("shift_type");
    }

    public function shift_type()
    {
        return $this->belongsTo(ShiftType::class);
    }

    public function getDateAttribute($value)
    {
        return date("d M y", strtotime($value));
    }

    public function getDayAttribute()
    {
        // return date("D", strtotime($this->date));
        return date("l", strtotime($this->date));
    }
    public function getHrsMins($difference)
    {
        $h = floor($difference / 3600);
        $h = $h < 0 ? "0" : $h;
        $m = floor($difference % 3600) / 60;
        $m = $m < 0 ? "0" : $m;

        return (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
    }

    // public function getTotalHrsAttribute($value)
    // {
    //     return strtotime($value) < strtotime('18:00') ? $value : '00:00';
    // }

    /**
     * Get the user that owns the Attendance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device_in()
    {
        return $this->belongsTo(Device::class, 'device_id_in', 'device_id')->withDefault([
            'name' => '---',
        ]);
    }

    /**
     * Get the user that owns the Attendance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device_out()
    {
        return $this->belongsTo(Device::class, 'device_id_out', 'device_id')->withDefault([
            'name' => '---',
        ]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "employee_id", "system_user_id")->withOut("schedule")->withDefault([
            'first_name' => '---',
            "department" => [
                "name" => "---",
            ],
        ]);
    }
    public function employeeapi()
    {
        return $this->belongsTo(Employee::class, "employee_id", "system_user_id")->withOut(["schedule", "department", "designation", "sub_department", "branch"]);
    }
    public function employeeAttendance()
    {
        return $this->belongsTo(Employee::class, "employee_id");
    }

    public function getEditDateAttribute()
    {
        return date("Y-m-d", strtotime($this->date));
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    public function AttendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, "UserID", "employee_id");
    }

    public function schedule()
    {
        return $this->belongsTo(ScheduleEmployee::class, "employee_id", "employee_id")->withOut(["shift_type"]);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            //$builder->orderBy('id', 'desc');
        });
    }

    public function last_reason()
    {
        return $this->hasOne(Reason::class, 'reasonable_id', 'id')->latest();
    }

    public function processAttendanceModel($request)
    {

        $model = self::query();

        $model->where('company_id', $request->company_id);
        $model->with(['shift_type', 'last_reason', 'branch']);

        $model->when($request->filled('employee_id'), function ($q) use ($request) {
            $q->where('employee_id', $request->employee_id);
        });


        $model->when($request->filled('shift_type_id') && $request->shift_type_id == 2, function ($q) use ($request) {
            $q->where('shift_type_id', 2);
            // $q->where(function ($query) {
            //     $query->where('shift_type_id',   2)
            //         ->orWhere('shift_type_id', '---');
            // });
        });

        $model->when($request->filled('shift_type_id') && $request->shift_type_id == 5, function ($q) {
            $q->where('shift_type_id', 5);
            // $q->where(function ($query) {
            //     $query->where('shift_type_id',   5)
            //         ->orWhere('shift_type_id', '---');
            // });
        });

        $model->when($request->filled('shift_type_id') && in_array($request->shift_type_id, [1, 3, 4, 6]), function ($q) {
            //$q->whereIn('shift_type_id', [1, 3, 4, 6]);
            $q->where(function ($query) {
                $query->whereIn('shift_type_id', [1, 3, 4, 6])
                    ->orWhere('shift_type_id', '---');
            });
        });

        $department_ids = $request->department_ids;

        if (gettype($department_ids) !== "array") {
            $department_ids = explode(",", $department_ids);
        }

        $model->when($request->filled('department_ids') && count($department_ids) > 0, function ($q) use ($request, $department_ids) {
            $q->whereIn('employee_id', Employee::whereIn("department_id", $department_ids)->where('company_id', $request->company_id)->pluck("system_user_id"));
        });

        $model->when($request->filled('status') && $request->status != "-1", function ($q) use ($request) {
            $q->where('status', $request->status);
        });


        $model->when($request->status == "ME", function ($q) {
            $q->where('is_manual_entry', true);
        });

        $model->when($request->late_early == "LC", function ($q) {
            $q->where('late_coming', "!=", "---");
        });

        $model->when($request->late_early == "EG", function ($q) {
            $q->where('early_going', "!=", "---");
        });

        $model->when($request->overtime == 1, function ($q) {
            $q->where('ot', "!=", "---");
        });

        $model->when($request->filled('branch_id'), function ($q) use ($request) {
            $key = strtolower($request->branch_id);
            $q->whereHas('employee', fn (Builder $query) => $query->where('branch_id',   $key));
        });
        // $model->when($request->filled('branch_id'), function ($q) use ($request) {
        //     $q->where('branch_id',   $request->branch_id);
        // });


        $model->when($request->daily_date && $request->report_type == 'Daily', function ($q) use ($request) {
            $q->whereDate('date', $request->daily_date);
        });

        $model->when($request->from_date && $request->to_date && $request->report_type != 'Daily', function ($q) use ($request) {
            $q->whereBetween("date", [$request->from_date, $request->to_date]);
        });

        // $model->whereBetween("date", [$request->from_date, $request->to_date]);

        $model->whereHas('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->where('status', 1);
            $q->select('system_user_id', 'display_name', "department_id", "first_name", "last_name", "profile_picture", "employee_id", "branch_id");
            $q->with(['department', 'branch']);
        });

        $model->with([
            'employee' => function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
                $q->where('status', 1);
                $q->select('system_user_id', 'full_name', 'display_name', "department_id", "first_name", "last_name", "profile_picture", "employee_id", "branch_id");
                $q->with(['department', 'branch']);
            }
        ]);

        $model->with('device_in', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('device_out', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('shift', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('schedule', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        //$model->with('schedule');

        $model->when($request->filled('date'), function ($q) use ($request) {
            $q->whereDate('date', '=', $request->date);
        });

        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');

            $q->orderBy($request->sortBy, $sortDesc == 'true' ? 'desc' : 'asc');
        });

        $model->when(!$request->filled('sortBy'), function ($q) use ($request) {

            if ($request->from_date == $request->to_date) {
                $q->orderBy(Employee::select("first_name")->whereColumn("employees.company_id", "attendances.company_id")->whereColumn("employees.system_user_id", "attendances.employee_id")->limit(0, 1),   'asc');
            } else {
                $q->orderBy('date', 'asc');
            }
        });

        $model->whereDoesntHave('device_in', fn ($q) => $q->where('device_type', 'Access Control'));
        $model->whereDoesntHave('device_out', fn ($q) => $q->where('device_type', 'Access Control'));



        return $model;
    }
    public function processAttendanceModelPDFJob($request)
    {

        $model = self::query();

        $model->where('company_id', $request->company_id);
        $model->with(['shift_type', 'last_reason', 'branch']);

        $model->when($request->employee_id, function ($q) use ($request) {
            $q->where('employee_id', $request->employee_id);
        });


        $model->when($request->shift_type_id && $request->shift_type_id == 2, function ($q) use ($request) {
            $q->where('shift_type_id', 2);
            // $q->where(function ($query) {
            //     $query->where('shift_type_id',   2)
            //         ->orWhere('shift_type_id', '---');
            // });
        });

        $model->when($request->shift_type_id && $request->shift_type_id == 5, function ($q) {
            $q->where('shift_type_id', 5);
            // $q->where(function ($query) {
            //     $query->where('shift_type_id',   5)
            //         ->orWhere('shift_type_id', '---');
            // });
        });

        $model->when($request->shift_type_id && in_array($request->shift_type_id, [1, 3, 4, 6]), function ($q) {
            //$q->whereIn('shift_type_id', [1, 3, 4, 6]);
            $q->where(function ($query) {
                $query->whereIn('shift_type_id', [1, 3, 4, 6])
                    ->orWhere('shift_type_id', '---');
            });
        });

        $department_ids = $request->department_ids;

        if (gettype($department_ids) !== "array") {
            $department_ids = explode(",", $department_ids);
        }

        $model->when($request->department_ids && count($department_ids) > 0, function ($q) use ($request, $department_ids) {
            $q->whereIn('employee_id', Employee::whereIn("department_id", $department_ids)->where('company_id', $request->company_id)->pluck("system_user_id"));
        });

        $model->when($request->status && $request->status != "-1", function ($q) use ($request) {
            $q->where('status', $request->status);
        });


        $model->when($request->status == "ME", function ($q) {
            $q->where('is_manual_entry', true);
        });

        $model->when($request->late_early == "LC", function ($q) {
            $q->where('late_coming', "!=", "---");
        });

        $model->when($request->late_early == "EG", function ($q) {
            $q->where('early_going', "!=", "---");
        });

        $model->when($request->overtime == 1, function ($q) {
            $q->where('ot', "!=", "---");
        });

        $model->when($request->branch_id, function ($q) use ($request) {
            $key = strtolower($request->branch_id);
            $q->whereHas('employee', fn (Builder $query) => $query->where('branch_id',   $key));
        });
        // $model->when($request->filled('branch_id'), function ($q) use ($request) {
        //     $q->where('branch_id',   $request->branch_id);
        // });


        $model->when($request->daily_date && $request->report_type == 'Daily', function ($q) use ($request) {
            $q->whereDate('date', $request->daily_date);
        });

        $model->when($request->from_date && $request->to_date && $request->report_type != 'Daily', function ($q) use ($request) {
            $q->whereBetween("date", [$request->from_date, $request->to_date]);
        });

        // $model->whereBetween("date", [$request->from_date, $request->to_date]);

        $model->whereHas('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->where('status', 1);
            $q->select('system_user_id', 'display_name', "department_id", "first_name", "last_name", "profile_picture", "employee_id", "branch_id");
            $q->with(['department', 'branch']);
        });

        $model->with([
            'employee' => function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
                $q->where('status', 1);
                $q->select('system_user_id', 'full_name', 'display_name', "department_id", "first_name", "last_name", "profile_picture", "employee_id", "branch_id");
                $q->with(['department', 'branch']);
            }
        ]);

        $model->with('device_in', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('device_out', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('shift', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('schedule', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        //$model->with('schedule');

        $model->when($request->date, function ($q) use ($request) {
            $q->whereDate('date', '=', $request->date);
        });

        $model->when($request->sortBy, function ($q) use ($request) {
            $sortDesc = $request->sortDesc;

            $q->orderBy($request->sortBy, $sortDesc == 'true' ? 'desc' : 'asc');
        });

        $model->when(!$request->sortBy, function ($q) use ($request) {

            if ($request->from_date == $request->to_date) {
                $q->orderBy(Employee::select("first_name")->whereColumn("employees.company_id", "attendances.company_id")->whereColumn("employees.system_user_id", "attendances.employee_id")->limit(0, 1),   'asc');
            } else {
                $q->orderBy('date', 'asc');
            }
        });

        $model->whereDoesntHave('device_in', fn ($q) => $q->where('device_type', 'Access Control'));
        $model->whereDoesntHave('device_out', fn ($q) => $q->where('device_type', 'Access Control'));



        return $model;
    }


    public function startDBOperation($date, $script, $payload)
    {
        if (!count($payload)) {
            return "($script Shift) {$date->format('d-M-y')}: No Data Found";
        }

        $employee_ids = array_column($payload, "employee_id");
        $company_ids = array_column($payload, "company_id");

        try {
            $model = self::query();
            $model->where("date", $date->format('Y-m-d'));
            $model->whereIn("employee_id", $employee_ids);
            $model->whereIn("company_id", $company_ids);
            $model->delete();
            $model->insert($payload);
            AttendanceLog::whereIn("UserID", $employee_ids)->whereIn("company_id", $company_ids)->update(["checked" => true]);
            return "($script Shift) " . $date->format('d-M-y') . ": Log(s) has been render. Affected Ids: " . json_encode($employee_ids) . " Affected Company_id Ids: " . json_encode($company_ids);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
