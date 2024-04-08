<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the user that owns the Room
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function tanent()
    {
        return $this->hasOne(Tanent::class)->withDefault([
            "full_name" => "---",
            "start_date" => "---",
            "end_date" => "---",
            "end_date" => "---",
        ]);
    }

    public function room_category()
    {
        return $this->belongsTo(RoomCategory::class);
    }
}
