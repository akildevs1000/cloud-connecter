<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneDevices extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $with = ["devices"];

    // Define the belongsTo relationship with Zone
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function devices()
    {
        return $this->belongsTo(Device::class, "device_id");
    }
}
