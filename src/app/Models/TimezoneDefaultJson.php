<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimezoneDefaultJson extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ["dayTimeList" => "array"];
}
