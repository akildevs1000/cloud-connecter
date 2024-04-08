<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['time_in_numbers', "show_from_date", "show_to_date"];

    protected $with = ['shift_type'];

    protected $casts = [
        'days' => 'array',
    ];

    public function getShowFromDateAttribute(): string
    {


        //return date('d M Y', strtotime($this->from_date));
        return date('Y-m-d', strtotime($this->from_date));
    }

    public function getShowToDateAttribute(): string
    {

        //return date('d M Y', strtotime($this->to_date));
        return date('Y-m-d', strtotime($this->to_date));
    }

    public function shift_type()
    {
        return $this->belongsTo(ShiftType::class);
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('id', 'desc');
        });
    }

    public function autoshift()
    {
        return $this->hasOne(AutoShift::class);
    }

    public function getTimeInNumbersAttribute()
    {
        return strtotime($this->on_duty_time);
        return date("Y-m-d H:i", strtotime($this->on_duty_time));
    }

    /**
     * Get the user that owns the Shift
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee_schedule()
    {
        return $this->belongsTo(ScheduleEmployee::class, 'shift_id');
    }

    public function getAutoShiftsAll($companyId, $branch_id)
    {
        // Auto Shift
        return self::orderBy("on_duty_time")
            ->where("company_id", $companyId)
            ->where("branch_id", $branch_id)
            ->where("isAutoShift", 1)
            ->withOut("shift_type")
            ->with("employee_schedule")
            ->get(
                [
                    "id",
                    "name",
                    "on_duty_time",
                    "off_duty_time",
                    "working_hours",
                    "overtime_interval",
                    "early_time",
                    "shift_type_id"
                ]

            )->toArray();
    }
}
