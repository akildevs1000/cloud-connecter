<?php

namespace App\Models;

use App\Models\Leave;
use App\Models\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory;

    // protected $with = [];

    protected $with = ["schedule", "department", "designation", "department", "sub_department", 'branch', 'user'];

    protected $guarded = [];

    protected $casts = [
        'joining_date' => 'date:Y/m/d',
        'created_at' => 'datetime:d-M-y',
    ];

    protected $appends = ['show_joining_date', 'profile_picture_raw', 'edit_joining_date', 'name_with_user_id'];

    public function schedule()
    {
        return $this->hasOne(ScheduleEmployee::class, "employee_id", "system_user_id")
            // ->where('from_date', '<=', date('Y-m-d'))
            // ->where('to_date', '>=', date('Y-m-d'))
            // ->orderBy('from_date', 'desc')
            ->withDefault([
                "shift_type_id" => "---",
                "shift_type" => [
                    "name" => "---",
                ],
            ]);
    }
    public function schedule_active()
    {
        return $this->hasOne(ScheduleEmployee::class, "employee_id", "system_user_id")
            ->where('from_date', '<=', date('Y-m-d'))
            ->where('to_date', '>=', date('Y-m-d'))
            ->orderBy('from_date', 'desc')
            ->withDefault([
                "shift_type_id" => "---",
                "shift_type" => [
                    "name" => "---",
                ],
            ]);
    }

    public function schedule_all()
    {
        return $this->hasMany(ScheduleEmployee::class, "employee_id", "system_user_id");
    }

    public function latestSchedule()
    {
        return $this->hasOne(ScheduleEmployee::class, 'employee_id', 'system_user_id')->latest();
    }


    public function announcements()
    {
        return $this->belongsToMany(Announcement::class)->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            "email" => "---",
        ]);
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id', 'timezone_id')->withDefault([
            "timezone_name" => "---",
        ]);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class)->withDefault([
            "name" => "---",
        ]);
    }
    public function leave_group()
    {
        return $this->belongsTo(LeaveGroups::class, "leave_group_id", "id");
    }
    public function reporting_manager()
    {
        return $this->belongsTo(Employee::class, "reporting_manager_id", "id");
    }


    public function role()
    {
        return $this->belongsTo(Role::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function payroll()
    {
        return $this->hasOne(Payroll::class);
    }

    public function passport()
    {
        return $this->hasOne(Passport::class);
    }

    public function emirate()
    {
        return $this->hasOne(EmiratesInfo::class);
    }
    public function visa()
    {
        return $this->hasOne(Visa::class);
    }
    public function qualification()
    {
        return $this->hasOne(Qualification::class)->withDefault([
            "certificate" => "---",
        ]);
    }

    public function bank()
    {
        return $this->hasOne(BankInfo::class)->withDefault([
            "bank_name" => "---",
            "account_no" => "---",
            "account_title" => "---",
            "address" => "---",
            "iban" => "---",
        ]);
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function sub_department()
    {
        return $this->belongsTo(SubDepartment::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function getProfilePictureAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('media/employee/profile_picture/' . $value);
        // return asset(env('BUCKET_URL') . '/' . $value);

    }
    public function getProfilePictureRawAttribute()
    {
        $arr = explode('media/employee/profile_picture/', $this->profile_picture);
        return    $arr[1] ?? '';
        // return asset(env('BUCKET_URL') . '/' . $value);

    }
    public function getCreatedAtAttribute($value): string
    {
        return date('d M Y', strtotime($value));
    }

    public function getShowJoiningDateAttribute(): string
    {
        return date('d M Y', strtotime($this->joining_date));
    }

    public function getEditJoiningDateAttribute(): string
    {
        return date('Y-m-d', strtotime($this->joining_date));
    }
    // public function getNameAttribute(): string
    // {
    //     return $this->first_name ?? "";
    // }

    // public function getFullNameAttribute(): string
    // {
    //     return $this->first_name . " " . $this->last_name ?? "---";
    // }

    public function getNameWithUserIDAttribute()
    {
        return $this->first_name . ' ' . $this->last_name . " - " . $this->employee_id;
    }

    // use Illuminate\Database\Eloquent\Builder;

    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            // $builder->orderBy('id', 'desc');
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, "employee_id", "system_user_id");
    }

    public function attendance_logs()
    {
        return $this->hasMany(AttendanceLog::class, "UserID", "system_user_id");
    }

    public function announcement()
    {
        return $this->belongsToMany(Announcement::class)->withTimestamps();
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id", "id");
    }

    public function branch_test()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id", "id")->with("departments");
    }
    /**
     * The roles that belong to the Employee
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reportTo()
    {
        return $this->belongsToMany(Employee::class, 'employee_report', 'employee_id', 'report_id')->withTimestamps();
    }




    public function leave()
    {
        return $this->hasMany(Leave::class, 'employee_id', 'employee_id');
    }

    public function scopeFilter($query, $search)
    {
        $search = strtolower($search);
        $query->when($search ?? false, fn ($query, $search) =>
        $query->where(
            fn ($query) => $query
                ->where('employee_id', $search)
                ->orWhere(DB::raw('lower(first_name)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(last_name)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(phone_number)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(local_email)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(system_user_id)'), 'Like', '%' . $search . '%')
                ->whereNotNull('first_name')
            // ->orWhere('whatsapp_number', 'Like', '%' . $search . '%')
            // ->orWhere('phone_relative_number', 'Like', '%' . $search . '%')
            // ->orWhere('whatsapp_relative_number', 'Like', '%' . $search . '%')
            // ->orWhereHas(
            //     'user',
            //     fn ($query) =>
            //     $query->Where('email', 'Like', '%' . $search . '%')
            // )
            // ->orWhereHas(
            //     'designation',
            //     fn ($query) =>
            //     $query->Where('name', 'Like', '%' . $search . '%')
            // )
            // ->orWhereHas(
            //     'department',
            //     fn ($query) =>
            //     $query->Where('name', 'Like', '%' . $search . '%')
            // )
        ));
    }

    public function filter($request)
    {
        $model = self::query();

        $model->with([
            "user" => function ($q) {
                return $q->with("role");
            },
        ])
            ->with([

                "user" => function ($q) {
                    return $q->with(["branchLogin", "role"]);
                },
            ])
            ->with([
                "reportTo",   "schedule_all", "branch", "department", "department.branch", "sub_department", "designation", "payroll", "timezone", "passport",
                "emirate", "qualification", "bank", "leave_group",  "Visa", "reporting_manager",
            ])
            ->with(["schedule" => function ($q) {
                $q->with("roster");
            }])
            ->where('company_id', $request->company_id)


            ->when($request->filled('department_ids') && count($request->department_ids) > 0, function ($q) use ($request) {
                $q->whereHas('department', fn (Builder $query) => $query->whereIn('department_id', $request->department_ids));
            })

            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('department', fn (Builder $query) => $query->where('department_id', $request->department_id));
            })
            //filters
            ->when($request->filled('employee_id'), function ($q) use ($request) {
                //$q->where('employee_id', 'LIKE', "$key%");
                $q->where(function ($q) use ($request) {
                    $q->Where('employee_id', 'ILIKE', "$request->employee_id%");
                    $q->orWhere('system_user_id', 'ILIKE', "$request->employee_id%");
                });
            })
            ->when($request->filled('id'), function ($q) use ($request) {

                $q->where('id',   $request->id);
            })
            ->when($request->filled('phone_number'), function ($q) use ($request) {

                $q->where('phone_number', 'ILIKE', "$request->phone_number%");
            })
            ->when($request->filled('first_name'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->Where('first_name', 'ILIKE', "$request->first_name%");
                    //$q->orWhere('last_name', 'ILIKE', "$request->first_name%");
                });
            })

            ->when($request->filled('user_email'), function ($q) use ($request) {
                // $q->where('local_email', 'LIKE', "$request->user_email%");
                $q->whereHas('user', fn (Builder $query) => $query->where('email', 'ILIKE', "$request->user_email%"));
            })
            ->when($request->filled('department_name_id'), function ($q) use ($request) {
                // $q->whereHas('department', fn(Builder $query) => $query->where('name', 'ILIKE', "$request->department_name%"));
                $q->whereHas('department', fn (Builder $query) => $query->where('id', $request->department_name_id));
            })

            ->when($request->filled('shceduleshift_id'), function ($q) use ($request) {
                $q->whereHas('schedule', fn (Builder $query) => $query->where('shift_id', $request->shceduleshift_id));
            })
            ->when($request->filled('schedule_shift_name'), function ($q) use ($request) {
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->where('name', 'ILIKE', "$request->schedule_shift_name%"));
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->whereNotNull('name'));
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->where('name', '<>', '---'));
            })
            // ->when($request->filled('timezone_name'), function ($q) use ($request) {
            //     $q->whereHas('timezone', fn (Builder $query) => $query->where('timezone_name', 'ILIKE', "$request->timezone_name%"));
            // })
            // ->when($request->filled('timezone'), function ($q) use ($request) {
            //     $q->whereHas('timezone', fn (Builder $query) => $query->where('timezone_id', $request->timezone));
            // })

            ->when($request->filled('timezone_id'), function ($q) use ($request) {
                $q->whereHas('timezone', fn (Builder $query) => $query->where('id', $request->timezone_id));
            })

            ->when($request->filled('payroll_basic_salary'), function ($q) use ($request) {
                $q->whereHas('payroll', fn (Builder $query) => $query->where('basic_salary', '=', $request->payroll_basic_salary));
            })
            ->when($request->filled('payroll_net_salary'), function ($q) use ($request) {
                $q->whereHas('payroll', fn (Builder $query) => $query->where('net_salary', '=', $request->payroll_net_salary));
            })

            ->when($request->filled("department_branch_id"), function ($q) use ($request) {
                //$q->whereHas('department', fn (Builder $query) => $query->where('branch_id', '=', $request->department_branch_id));
                $q->where('branch_id', '=', $request->branch_id);
            })
            ->when($request->filled("branch_id"), function ($q) use ($request) {
                $q->where('branch_id', '=', $request->branch_id);
                // $q->whereHas('department', fn (Builder $query) => $query->where('branch_id', '=', $request->branch_id));
            })
            ->when($request->filled("filter_branch_id"), function ($q) use ($request) {
                $q->where('branch_id', '=', $request->filter_branch_id);
                // $q->whereHas('department', fn (Builder $query) => $query->where('branch_id', '=', $request->filter_branch_id));
            })



            // ->when($request->filled('sortBy'), function ($q) use ($request) {
            //     $sortDesc = $request->input('sortDesc');
            //     $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');
            // })

            ->when($request->filled('sortBy'), function ($q) use ($request) {
                $sortDesc = $request->input('sortDesc');
                if (strpos($request->sortBy, '.')) {
                    if ($request->sortBy == 'department.name.id' || $request->sortBy == 'department_name_id') {
                        $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'user.email') {
                        $q->orderBy(User::select("email")->whereColumn("users.id", "employees.user_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'schedule.shift_name') {
                        // $q->orderBy(Schedule::select("shift")->whereColumn("schedule_employees.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');

                    } else
                    if ($request->sortBy == 'timezone.name') {
                        $q->orderBy(Timezone::select("timezone_name")->whereColumn("timezones.id", "employees.timezone_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'payroll.basic_salary') {
                        $q->orderBy(Payroll::select("basic_salary")->whereColumn("payrolls.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'payroll.net_salary') {
                        $q->orderBy(Payroll::select("net_salary")->whereColumn("payrolls.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    }
                } else if ($request->sortBy == 'department_name_id') {
                    $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else {
                    $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                    }
                }
            });

        if (!$request->sortBy) {
            $model->orderBy('first_name', 'asc');
        }

        return $model;
    }

    public function document_expiry_filter($request)
    {
        $model = self::query();

        $model
            ->where('company_id', $request->company_id)


            ->when($request->filled('department_ids') && count($request->department_ids) > 0, function ($q) use ($request) {
                $q->whereHas('department', fn (Builder $query) => $query->whereIn('department_id', $request->department_ids));
            })

            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('department', fn (Builder $query) => $query->where('department_id', $request->department_id));
            })
            //filters
            ->when($request->filled('employee_id'), function ($q) use ($request) {
                //$q->where('employee_id', 'LIKE', "$key%");
                $q->where(function ($q) use ($request) {
                    $q->Where('employee_id', 'ILIKE', "$request->employee_id%");
                    $q->orWhere('system_user_id', 'ILIKE', "$request->employee_id%");
                });
            })
            ->when($request->filled('id'), function ($q) use ($request) {

                $q->where('id',   $request->id);
            })
            ->when($request->filled('phone_number'), function ($q) use ($request) {

                $q->where('phone_number', 'ILIKE', "$request->phone_number%");
            })
            ->when($request->filled('first_name'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->Where('first_name', 'ILIKE', "$request->first_name%");
                    //$q->orWhere('last_name', 'ILIKE', "$request->first_name%");
                });
            })

            ->when($request->filled('user_email'), function ($q) use ($request) {
                // $q->where('local_email', 'LIKE', "$request->user_email%");
                $q->whereHas('user', fn (Builder $query) => $query->where('email', 'ILIKE', "$request->user_email%"));
            })
            ->when($request->filled('department_name_id'), function ($q) use ($request) {
                // $q->whereHas('department', fn(Builder $query) => $query->where('name', 'ILIKE', "$request->department_name%"));
                $q->whereHas('department', fn (Builder $query) => $query->where('id', $request->department_name_id));
            })

            ->when($request->filled('shceduleshift_id'), function ($q) use ($request) {
                $q->whereHas('schedule', fn (Builder $query) => $query->where('shift_id', $request->shceduleshift_id));
            })
            ->when($request->filled('schedule_shift_name'), function ($q) use ($request) {
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->where('name', 'ILIKE', "$request->schedule_shift_name%"));
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->whereNotNull('name'));
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->where('name', '<>', '---'));
            })
            // ->when($request->filled('timezone_name'), function ($q) use ($request) {
            //     $q->whereHas('timezone', fn (Builder $query) => $query->where('timezone_name', 'ILIKE', "$request->timezone_name%"));
            // })
            // ->when($request->filled('timezone'), function ($q) use ($request) {
            //     $q->whereHas('timezone', fn (Builder $query) => $query->where('timezone_id', $request->timezone));
            // })

            ->when($request->filled('timezone_id'), function ($q) use ($request) {
                $q->whereHas('timezone', fn (Builder $query) => $query->where('id', $request->timezone_id));
            })

            ->when($request->filled('payroll_basic_salary'), function ($q) use ($request) {
                $q->whereHas('payroll', fn (Builder $query) => $query->where('basic_salary', '=', $request->payroll_basic_salary));
            })
            ->when($request->filled('payroll_net_salary'), function ($q) use ($request) {
                $q->whereHas('payroll', fn (Builder $query) => $query->where('net_salary', '=', $request->payroll_net_salary));
            })

            ->when($request->filled("department_branch_id"), function ($q) use ($request) {
                //$q->whereHas('department', fn (Builder $query) => $query->where('branch_id', '=', $request->department_branch_id));
                $q->where('branch_id', '=', $request->branch_id);
            })
            ->when($request->filled("branch_id"), function ($q) use ($request) {
                $q->where('branch_id', '=', $request->branch_id);
                // $q->whereHas('department', fn (Builder $query) => $query->where('branch_id', '=', $request->branch_id));
            })
            ->when($request->filled("filter_branch_id"), function ($q) use ($request) {
                $q->where('branch_id', '=', $request->filter_branch_id);
                // $q->whereHas('department', fn (Builder $query) => $query->where('branch_id', '=', $request->filter_branch_id));
            })



            // ->when($request->filled('sortBy'), function ($q) use ($request) {
            //     $sortDesc = $request->input('sortDesc');
            //     $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');
            // })

            ->when($request->filled('sortBy'), function ($q) use ($request) {
                $sortDesc = $request->input('sortDesc');
                if (strpos($request->sortBy, '.')) {
                    if ($request->sortBy == 'department.name.id' || $request->sortBy == 'department_name_id') {
                        $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'user.email') {
                        $q->orderBy(User::select("email")->whereColumn("users.id", "employees.user_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'schedule.shift_name') {
                        // $q->orderBy(Schedule::select("shift")->whereColumn("schedule_employees.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');

                    } else
                    if ($request->sortBy == 'timezone.name') {
                        $q->orderBy(Timezone::select("timezone_name")->whereColumn("timezones.id", "employees.timezone_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'payroll.basic_salary') {
                        $q->orderBy(Payroll::select("basic_salary")->whereColumn("payrolls.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'payroll.net_salary') {
                        $q->orderBy(Payroll::select("net_salary")->whereColumn("payrolls.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    }
                } else if ($request->sortBy == 'department_name_id') {
                    $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else {
                    $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                    }
                }
            });

        if (!$request->sortBy) {
            $model->orderBy('first_name', 'asc');
        }

        return $model;
    }


    public function attendanceEmployeeForMulti($params)
    {
        $employees = Employee::query();
        $employees->when(count($params["UserIds"] ?? []) > 0, function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->whereIn("system_user_id", $params["UserIds"]);
        });
        $employees->where("company_id", $params["company_id"]);
        $employees->withOut(["department", "sub_department", "designation"]);
        $employees->whereHas("attendance_logs", function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->where("LogTime", ">=", $params["date"]); // Check for logs on or after the current date
            $q->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day"))); // Check for logs on or before the next date
            // $q->where("checked", false);
            // $q->where("UserID",707);
        });

        $employees->with(["schedule" => function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->where("to_date", ">=", $params["date"]);
            $q->where("shift_type_id", $params["shift_type_id"]);
            $q->withOut("shift_type");
            // $q->select("shift_id", "isOverTime", "employee_id", "shift_type_id", "shift_id", "shift_id");
            $q->orderBy("to_date", "asc");
        }]);

        return $employees->get(["system_user_id"]);
    }

    public function attendanceEmployeeForMultiRender($params)
    {


        $employees = Employee::query();
        $employees->where("company_id", $params["company_id"]);
        $employees->whereIn("system_user_id", $params["UserIds"] ?? []);
        $employees->withOut(["department", "sub_department", "designation"]);
        $employees->whereHas("attendance_logs", function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->where("LogTime", ">=", $params["date"]); // Check for logs on or after the current date
            $q->where("LogTime", "<=", date("Y-m-d", strtotime($params["date"] . " +1 day"))); // Check for logs on or before the next date
            // // $q->where("checked", false);
            // $q->where("UserID",707);
        });

        $employees->with(["schedule" => function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->where("to_date", ">=", $params["date"]);
            $q->where("shift_type_id", $params["shift_type_id"]);
            $q->withOut("shift_type");
            // $q->select("shift_id", "isOverTime", "employee_id", "shift_type_id", "shift_id", "shift_id");
            $q->orderBy("to_date", "asc");
        }]);

        return $employees->get(["system_user_id"]);
    }
    public function GetEmployeeWithShiftDetails($params)
    {


        $employees = Employee::query();
        $employees->where("company_id", $params["company_id"]);
        $employees->whereIn("system_user_id", $params["UserIds"] ?? []);
        $employees->withOut(["department", "sub_department", "designation"]);


        $employees->with(["schedule" => function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->where("to_date", ">=", $params["date"]);
            $q->where("shift_type_id", $params["shift_type_id"]);
            $q->withOut("shift_type");
            // $q->select("shift_id", "isOverTime", "employee_id", "shift_type_id", "shift_id", "shift_id");
            $q->orderBy("to_date", "asc");
        }]);

        return $employees->get(["system_user_id"]);
    }

    public function attendanceEmployeeForRender($params)
    {
        $employees = Employee::query();
        $employees->where("company_id", $params["company_id"]);
        $employees->when(count($params["UserIds"] ?? []) > 0, function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->whereIn("system_user_id", $params["UserIds"]);
        });
        $employees->withOut(["department", "sub_department", "designation"]);

        $employees->whereHas("attendance_logs", function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->whereIn("UserID", $params["UserIds"]);
        });

        $employees->with(["schedule" => function ($q) use ($params) {
            $q->where("company_id", $params["company_id"]);
            $q->where("to_date", ">=", $params["date"]);
            $q->where("shift_type_id", $params["shift_type_id"]);
            $q->withOut("shift_type");
            $q->select("shift_id", "isOverTime", "employee_id", "shift_type_id", "shift_id", "shift_id");
            $q->orderBy("to_date", "asc");
        }]);

        return $employees->get(["system_user_id"]);
    }

    public function scopeExcludeRelations($query)
    {
        return $query->withOut(["schedule", "department", "designation", "sub_department", "user", "branch"]);
    }
}
