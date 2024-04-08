<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function company_id()
    {
        return $this->belongsTo(Company::class);
    }
    public function employee_device()
    {
        return $this->belongsTo(EmployeeTimezoneMapping::class, 'timezone_id', 'timezone_id');
    }

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    protected $casts = [
        "interval" => "array",
        "scheduled_days" => "array",
        "json" => "array",
        'updated_at' => 'datetime:d-M-y',
    ];
}
