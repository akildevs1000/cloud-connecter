<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceActivesettings extends Model
{
    use HasFactory;
    protected $table = 'devices_active_settings';
    protected $guarded = [];

    public function devices()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('id', 'desc');
        });
    }
}
