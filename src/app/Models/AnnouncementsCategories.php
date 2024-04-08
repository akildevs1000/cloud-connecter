<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementsCategories extends Model
{
    use HasFactory;
    protected $fillable = [

        'name',
        // 'priority',
        'company_id',
        // 'description',
    ];
    // protected static function boot()
    // {
    //     parent::boot();

    //     // Order by   ASC
    //     static::addGlobalScope('order', function (Builder $builder) {
    //         $builder->orderBy('name', 'asc');
    //     });
    // }

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
