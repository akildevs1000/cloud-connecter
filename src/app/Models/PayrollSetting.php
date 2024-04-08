<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['day_number'];

    public function getDayNumberAttribute()
    {
        return (int) date("d", strtotime($this->date));
    }

    /**
     * Get the user that owns the PayrollSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
