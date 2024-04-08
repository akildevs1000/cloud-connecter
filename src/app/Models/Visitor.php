<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_name', 'name_with_user_id', 'status', 'from_date_display', 'to_date_display', 'time_in_display', 'time_out_display'];

    protected $casts = [
        "created_at" => "datetime:d-M-Y",
    ];



    public function getLogoAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('media/visitor/logo/' . $value);
    }

    /**
     * Get the user that owns the Visitor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    // public function status()
    // {
    //     return $this->belongsTo(Status::class);
    // }

    public function getStatusAttribute()
    {
        return $this->getVisitorStatusIds($this->status_id);
    }

    public function getFromDateDisplayAttribute()
    {
        return date("d-M-Y", strtotime($this->visit_from));
    }

    public function getToDateDisplayAttribute()
    {
        return date("d-M-Y", strtotime($this->visit_to));
    }


    public function getTimeInDisplayAttribute()
    {
        return date("H:i", strtotime($this->time_in));
    }

    public function getTimeOutDisplayAttribute()
    {
        return date("H:i", strtotime($this->time_out));
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function getVisitorStatusIds($id = '')
    {
        // $status = [
        //     1 => "pending",
        //     2 => "approved ",
        //     3 => "rejected ",
        //     4 => "gurd_approved ",
        //     5 => "updated_device",
        //     6 => "checked_in",
        //     7 => "checked_out "
        // ];

        $status = [
            ["id" => "",  "name" => "All"],
            ["id" => "1", "name" => "Pending"],
            ["id" => "2", "name" => "Approved"],
            ["id" => "3", "name" => "Rejected"],
            ["id" => "4", "name" => "Uploaded to Device"],
            ["id" => "5", "name" => "Deleted from Device"],
        ];

        if ($id) {
            $statusName = '';
            foreach ($status as $s) {
                if ($s["id"] === $id) {
                    $statusName = $s["name"];
                    break;
                }
            }


            return  $statusName;
        } else {
            return $status;
        }
    }
    public function attendances()
    {
        return $this->hasMany(VisitorAttendance::class);
    }
    public function purpose()
    {
        return $this->belongsTo(Purpose::class, "purpose_id");
    }

    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id", "id");
    }
    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id', 'timezone_id')->withDefault([
            "timezone_name" => "---",
        ]);
    }

    public function host()
    {
        return $this->belongsTo(HostCompany::class, 'host_company_id')
            ->with("employee:id,user_id,employee_id,system_user_id,first_name,last_name,display_name,profile_picture,phone_number,branch_id");
    }


    public function getNameWithUserIDAttribute()
    {
        return $this->first_name . ' ' . $this->last_name . $this->system_user_id;
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }


    public function filters($request)
    {
        $model = self::query();

        $model->where("company_id", $request->input("company_id"));

        $model->when($request->filled('status_id'), fn ($q) => $q->Where('status_id',   $request->input("status_id")));

        // $model->when($request->filled('branch_id'), fn ($q) => $q->Where('branch_id',   $request->input("branch_id")));

        // $model->when($request->filled("from_date"), fn ($q) => $q->whereDate("visit_from", '<=', $request->input("from_date")));

        // $model->when($request->filled("to_date"), fn ($q) => $q->whereDate("visit_to", '>=', $request->input("to_date")));
        $startDate = Carbon::parse($request->from_date);
        $endDate = Carbon::parse($request->to_date);


        $model = $model->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('visit_from', [$startDate, $endDate])
                ->orWhereBetween('visit_to', [$startDate, $endDate])
                ->orWhere(function ($query) use ($startDate, $endDate) {
                    $query->whereDate('visit_from', '<=', $startDate)
                        ->whereDate('visit_to', '>=', $endDate);
                });
        });

        $model->when($request->filled('host_company_id'), fn ($q) => $q->Where('host_company_id',   $request->input("host_company_id")));

        $model->when($request->filled('purpose_id'), fn ($q) => $q->Where('purpose_id',   $request->input("purpose_id")));

        $model->when($request->filled('branch_id'), fn ($q) => $q->Where('branch_id',   $request->input("branch_id")));



        $ilikeFields = ['id', 'company_name', 'system_user_id', 'manager_name', 'phone', 'email', 'zone_id', 'phone_number', 'time_in'];


        foreach ($ilikeFields as $field) {
            $model->when($request->filled($field), function ($q) use ($field, $request) {
                $q->when($request->filled('purpose_id'), fn ($q) => $q->where($field, 'ILIKE', $request->input($field) . '%'));
            });
        }

        $first_name = $request->first_name;

        $model->when($request->filled('first_name'), function ($q) use ($first_name) {
            $q->where(function ($q) use ($first_name) {
                $q->Where('first_name', 'ILIKE', "$first_name%");
                $q->orWhere('last_name', 'ILIKE', "$first_name%");
            });
        });

        $model->when($request->filled('phone_number_or_email'), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->Where('phone_number', 'ILIKE', "$request->phone_number_or_email%");
                $q->orWhere('email', 'ILIKE', "$request->phone_number_or_email%");
                $q->orWhere('first_name', 'ILIKE', "$request->phone_number_or_email%");
                $q->orWhere('last_name', 'ILIKE', "$request->phone_number_or_email%");
            });
        });

        $model->with(["host" => fn ($q) => $q->withOut(["user", "employee"])]);

        $model->orderBy("id", "DESC");

        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            if (!strpos($request->sortBy, '.')) {
                $q->orderBy($request->sortBy . "", $request->input('sortDesc') == 'true' ? 'desc' : 'asc');
            }
        });

        //----------------------

        // $model->when($request->filled('statsFilterValue'), function ($q) use ($request) {
        //     if ($request->statsFilterValue == 'all_approved') {
        //         $q->Where('status_id', "!=", 1);
        //     } else 
        //     if ($request->statsFilterValue == 'Expected' || $request->statsFilterValue == 'Approved') {
        //         $q->WhereIn('status_id',  [2, 4]);
        //     } else if ($request->statsFilterValue == 'Checked In') {

        //         $q->whereHas('attendances', fn (Builder $q) => $q->where('in', '!=', null));
        //     } else  if ($request->statsFilterValue == 'Checked Out') {

        //         $q->whereHas('attendances', fn (Builder $q) => $q->where('out', '!=', null));
        //     } else if ($request->statsFilterValue == 'Over Stayed') {

        //         $q->whereHas('attendances', fn (Builder $q) => $q->where("visitor_attendances.out", null)->where("visitors.time_out", '<', date("H:i")));
        //     } else if ($request->statsFilterValue == 'Pending') {

        //         $q->Where('status_id',  1);
        //     } else if ($request->statsFilterValue == 'Rejected') {

        //         $q->Where('status_id',  3);
        //     }
        // });




        return $model;
    }
}
