<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'from_date' => 'datetime:d-M-Y',
        'to_date' => 'datetime:d-M-Y',
        'requested_at' => 'datetime:d-M-Y H:i:s',
    ];

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "employee_device_id", "system_user_id")
            ->withOut(["schedule", "department", "designation", "sub_department", "user"])
            ->with("branch:id,branch_name")
            ->select("id", "system_user_id", "branch_id");
    }
}
