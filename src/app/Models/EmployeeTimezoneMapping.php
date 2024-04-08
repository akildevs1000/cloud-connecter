<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTimezoneMapping extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'employee_id' => "array",
        'device_id' => "array",
        'employee_ids' => "array",
        'device_ids' => "array",

    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id', 'timezone_id');
    }
}
