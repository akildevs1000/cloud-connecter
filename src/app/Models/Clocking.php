<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clocking extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getAttachmentAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('clocking/attachments/' . $value);
    }
}
