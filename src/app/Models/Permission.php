<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Permission extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $appends = ["title"];

    public function getTitleAttribute()
    {
        return Str::replace('_', ' ', $this->name);
    }
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('name', 'asc');
        });
    }
}
