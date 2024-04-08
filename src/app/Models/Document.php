<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function getValueAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('documents/' . $this->key . "/" . $value);
    }
}
