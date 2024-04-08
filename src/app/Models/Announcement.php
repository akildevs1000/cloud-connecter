<?php

namespace App\Models;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-M-y',
    ];

    /**
     * The roles that belong to the Announcement
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class)->withTimestamps();
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }
    public function category()
    {
        return $this->belongsTo(AnnouncementsCategories::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('updated_at', 'desc');
        });
    }

    public function filters($request)
    {
        $model = self::query();

        $model->with(['employees:id,first_name,last_name,display_name,employee_id,system_user_id', 'branch', 'departments', 'category', 'user.company', 'user.employee']);


        $model->where('company_id', $request->company_id);

        $model->when($request->filled('title'), function ($q) use ($request) {
            $key = $request->title;
            $q->where('title', 'ILIKE', "$key%");
        });
        $model->when($request->filled('description'), function ($q) use ($request) {
            $q->where('description', 'ILIKE', "$request->description%");
        });
        $model->when($request->filled('dates') && count($request->dates) > 1, function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                $query->where('start_date', '>=', $request->dates[0])
                    ->where('end_date', '<=', $request->dates[1]);
            });
        });
        $model->when($request->filled('branch_id'), function ($q) use ($request) {
            $q->where('branch_id', $request->branch_id);
        });
        $model->when($request->filled('categories'), function ($q) use ($request) {
            $key = $request->categories;
            $q->where('category_id',   $key);
        });




        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');
            if (strpos($request->sortBy, '.')) {
            } else {
                $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                }
            }
        });

        return $model;
    }
}
