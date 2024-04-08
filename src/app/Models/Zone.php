<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-M-y h:i:sa',
    ];

    /**
     * Get the user that owns the Zone
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function devices()
    {
        return $this->hasManyThrough(
            Device::class,
            ZoneDevices::class,
            'zone_id', // Foreign key on ZoneDevices table
            'id', // Foreign key on Devices table
            'id', // Local key on Zone table
            'device_id' // Local key on ZoneDevices table
        );
    }
}
