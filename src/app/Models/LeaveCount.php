<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCount extends Model
{
    use HasFactory;
    protected $table = 'leave_count';
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-M-y',
    ];

    public function leave_type()
    {
        return $this->belongsTo(LeaveType::class)->withDefault([
            "name" => "---", "short_name" => "---",
        ]);
    }
    public function leave_groups()
    {
        return $this->belongsTo(LeaveGroups::class, 'group_id', 'id')->withDefault([
            "group_name" => "---",
        ]);
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
