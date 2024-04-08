<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedDepartmentEmployee extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:d-M-y',
    ];

    public function departments()
    {
        return $this->hasManyThrough(
            Department::class,
            AssignDepartment::class,
            'assigned_department_employee_id', // Foreign key on DepartmentEmployee table (self)
            'id', // Foreign key on Department table 
            'id', // Local key on AssignedDepartmentEmployee table
            'department_id' // Local key on DepartmentEmployee table
        );
    }

    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            AssignEmployee::class,
            'assigned_department_employee_id', // Foreign key on DepartmentEmployee table
            'id', // Foreign key on Department table
            'id', // Local key on AssignedDepartmentEmployee table
            'employee_id' // Local key on DepartmentEmployee table
        )->withOut(["schedule", "department", "designation", "sub_department"]);
    }

    public function assginedDepartment($id)
    {
        return Department::join('assign_departments', 'departments.id', '=', 'assign_departments.department_id')
            ->join('assign_employees', 'assign_departments.assigned_department_employee_id', '=', 'assign_employees.assigned_department_employee_id')
            ->where('assign_employees.employee_id', $id)
            ->where('assign_employees.model', 'AssignedDepartmentEmployee')
            ->select('departments.*');
    }


    public function filters($request)
    {
        $model = self::query();
        // $model->with(['employees:id,first_name,last_name,display_name,employee_id,system_user_id', 'departments:id,name']);
        $model->where('company_id', $request->company_id);

        $model->when($request->filled('title'), function ($q) use ($request) {
            $key = $request->title;
            $q->where('title', 'ILIKE', "$key%");
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
