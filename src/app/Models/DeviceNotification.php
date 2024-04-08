<?php

namespace App\Models;

use App\Http\Controllers\DeviceNotificationsLogController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceNotification  extends Model
{
    use HasFactory;
    // public function getRouteKeyName()
    // {
    //     return 'id'; // Replace 'slug' with the column name you want to use as the key.
    // }

    //  protected $table = 'device_notifications';

    protected $guarded = [];

    protected $casts = [

        'mediums' => 'array',


    ];

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    public function managers()
    {
        return $this->hasMany(DeviceNotificationsManagers::class, "notification_id");
    }
    public function logs()
    {
        return $this->hasMany(DeviceNotificationsLog::class, "notification_id");
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
