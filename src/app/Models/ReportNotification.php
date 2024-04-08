<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportNotification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'body' => 'array',
        'reports' => 'array',
        'mediums' => 'array',
        'tos' => 'array',
        'ccs' => 'array',
        'bccs' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    public function managers()
    {
        return $this->hasMany(ReportNotificationManagers::class, "notification_id");
    }
    public function logs()
    {
        return $this->hasMany(ReportNotificationLogs::class, "notification_id");
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
