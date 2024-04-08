<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function branch_for_stats_only()
    {
        return $this->belongsTo(CompanyBranch::class, "br_id");
    }
    public function in_log()
    {
        return $this->belongsTo(AttendanceLog::class, "in_id")->with("device");
    }

    public function out_log()
    {
        return $this->belongsTo(AttendanceLog::class, "out_id")->with("device");
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id', 'system_user_id')->with(["recent_log", "branch"]);
    }
}
