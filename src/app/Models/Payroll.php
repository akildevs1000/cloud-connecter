<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['effective_date_formatted'];

    protected $casts = [
        'earnings' => 'array',
        'created_at' => 'datetime:d-M-y',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "employee_id");
    }

    public function payroll_formula()
    {
        return $this->hasOne(PayrollFormula::class, "company_id", "company_id");
    }

    // public function attendance()
    // {
    //     return $this->hasOne(Attendance::class,"employee_id","employee_id");
    // }

    public function getEffectiveDateFormattedAttribute()
    {
        return date("d-M-Y", strtotime($this->effective_date));
    }
}
