<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveGroups extends Model
{
    use HasFactory;
    protected $table = 'leave_groups';
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-M-y',
    ];

    // public function leave_type()
    // {
    //     return $this->belongsTo(LeaveType::class)->withDefault([
    //         "name" => "---", "short_name" => "---",
    //     ]);
    // }
    // public function leave_count()
    // {
    //     return $this->belongsTo(LeaveCount::class, 'id', 'group_id');
    // }
    public function leave_count()
    {
        return $this->hasMany(LeaveCount::class, "group_id", "id");
    }
    
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
