<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roster extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'shift_ids' => 'array',
        'shift_names' => 'array',
        'days' => 'array',
        'json' => 'array',
        'shift_type_ids' => 'array',
    ];
}