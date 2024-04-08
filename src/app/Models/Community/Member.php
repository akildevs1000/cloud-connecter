<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    public static $member_types = [
        'Family Member',
        'Relative',
        'Maid',
        'Visitor',
    ];

    protected $guarded = [];

    protected $appends = [
        "profile_picture_name",
    ];
    public function tenant()
    {
        return $this->belongsTo(Tanent::class);
    }

    public function getProfilePictureNameAttribute()
    {
        return explode("community/profile_picture/", $this->profile_picture)[1] ?? "";
    }

    public function getProfilePictureAttribute($value)
    {
        if (!$value) return null;
        return asset('community/profile_picture/' . $value);
    }
}
