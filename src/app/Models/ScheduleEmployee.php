<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleEmployee extends Model
{
    use HasFactory;

    protected $with = ["shift", "shift_type"];

    protected $appends = ["show_from_date", "show_to_date"];

    protected $guarded = [];

    protected $casts = [
        'schedules' => 'array',
        'employee_ids' => 'array',
        'isOverTime' => 'boolean'
    ];

    public function getShowFromDateAttribute(): string
    {
        return date('d M Y', strtotime($this->from_date));
    }

    public function getShowToDateAttribute(): string
    {
        return date('d M Y', strtotime($this->to_date));
    }

    /**
     * Get the shift that owns the ScheduleEmployee
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class)->withOut(["shift_type"])->withDefault([
            'name' => '---',
        ]);
    }

    public function shift_type()
    {
        return $this->belongsTo(ShiftType::class, "shift_type_id")->withDefault([
            'name' => '---',
        ]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "employee_id", "system_user_id");
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id",);
    }

    /**
     * Get all of the attendances for the ScheduleEmployee
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_id');
    }

    public function attendance_logs()
    {
        return $this->hasMany(AttendanceLog::class, 'UserID', 'employee_id');
    }

    // public function device()
    // {
    //     return $this->hasOneThrough(AttendanceLog::class, Device::class,"employee_id","device_id","id","id");
    // }


    public function roster()
    {
        return $this->belongsTo(Roster::class);
    }

    public function getEmployeesByType($shift_type_id)
    {
        return self::with("shift")->get()->groupBy(["company_id", "employee_id"]);
    }
}
