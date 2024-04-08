<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBranch extends Model
{
    use HasFactory;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expiry' => 'date:Y/m/d',
        'created_date' => 'datetime:d-M-Y',

    ];

    protected $appends = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, "branch_id", "id");
    }
    public function departments()
    {
        return $this->hasMany(Department::class, "branch_id", "id");
    }

    public function devices()
    {
        return $this->hasMany(Device::class, "branch_id", "id");
    }

    public function getLogoAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('upload/' . $value);
    }

    // public function getCreatedAtAttribute($value): string
    // {
    //     return date('d M Y',strtotime($value));
    // }

    // public function getShowExpiryAttribute(): string
    // {
    //     return date('d M Y',strtotime($this->expiry));
    // }
}
