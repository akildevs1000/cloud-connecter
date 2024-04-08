<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorAttendance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        "edit_date",
        "day",
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class, "visitor_id")->withDefault([
            "first_name" => "---",
            "last_name" => "---"

        ]);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getDateAttribute($value)
    {
        return date("d-M-y", strtotime($value));
    }
    public function deviceInName()
    {
        return $this->belongsTo(Device::class, "device_id_in", "id");
    }
    public function deviceOutName()
    {
        return $this->belongsTo(Device::class, "device_id_out", "id");
    }
    public function getDayAttribute()
    {
        return date("D", strtotime($this->date));
    }
    public function getHrsMins($difference)
    {
        $h = floor($difference / 3600);
        $h = $h < 0 ? "0" : $h;
        $m = floor($difference % 3600) / 60;
        $m = $m < 0 ? "0" : $m;

        return (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
    }

    public function device_in()
    {
        return $this->belongsTo(Device::class, 'device_id_in', 'device_id')->withDefault([
            'name' => '---',
        ]);
    }


    /**
     * Get the user that owns the Attendance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device_out()
    {
        return $this->belongsTo(Device::class, 'device_id_out', 'device_id')->withDefault([
            'name' => '---',
        ]);
    }

    public function getEditDateAttribute()
    {
        return date("Y-m-d", strtotime($this->date));
    }

    public function VisitorLogs()
    {
        return $this->hasMany(AttendanceLog::class, "UserID", "system_user_id");
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }


    public function processVisitorModel($request)
    {
        $company_id = $request->company_id;

        $model = self::query();

        $model->orderBy('date', 'asc');

        $model->where('company_id', $company_id);

        $model->when($request->filled('visitor_id'), function ($q) use ($request) {
            $q->where('visitor_id', $request->visitor_id);
        });
        $model->when($request->filled('host_id'), function ($q) use ($request) {
            $q->whereHas('visitor', fn (Builder $q) => $q->where('host_company_id',   $request->host_id));
        });
        $model->when($request->filled('purpose_id'), function ($q) use ($request) {
            $q->whereHas('visitor', fn (Builder $q) => $q->where('purpose_id',   $request->purpose_id));
        });


        $model->when($request->filled('purpose_id'), function ($q) use ($request) {
            $q->whereHas('visitor', fn (Builder $q) => $q->where('purpose_id',   $request->purpose_id));
        });


        $model->when($request->status !== "All", function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $model->when($request->daily_date && $request->frequency == 'Daily', function ($q) use ($request) {
            $q->whereDate('date', $request->daily_date);
        });

        $model->when($request->frequency != 'Daily' && $request->from_date && $request->to_date, function ($q) use ($request) {
            $q->whereBetween("date", [$request->from_date, $request->to_date]);
        });

        $model->when($request->filled('branch_id'), function ($q) use ($request) {
            $q->where('branch_id',  $request->branch_id);
        });



        $model->when($request->filled('visitorIdentificationNumber'), function ($q) use ($request) {

            $q->whereHas("visitor", fn ($q) => $q->where("id_number", $request->visitorIdentificationNumber));
        });

        $model->when($request->filled('date'), function ($q) use ($request) {
            $q->whereDate('date', '=', $request->date);
        });

        $model->when($request->filled('visitor_first_name') && $request->visitor_first_name != '', function ($q) use ($request) {
            $q->whereHas('visitor', fn (Builder $q) => $q->where('first_name', 'ILIKE', "$request->visitor_first_name%")->Orwhere('phone_number', 'ILIKE', "$request->visitor_first_name%"));
        });

        $model->when($request->filled('in'), function ($q) use ($request) {
            $q->where('in', 'LIKE', "$request->in%");
        });
        $model->when($request->filled('out'), function ($q) use ($request) {
            $q->where('out', 'LIKE', "$request->out%");
        });
        $model->when($request->filled('total_hrs'), function ($q) use ($request) {
            $q->where('total_hrs', 'LIKE', "$request->total_hrs%");
        });

        $model->when($request->filled('overstay'), function ($q) use ($request) {
            if ($request->overstay == 1)
                $q->whereHas("visitor", fn ($q) => $q->where("visitor_attendances.out", null)->where("visitors.time_out", '<', date("H:i")));
            else
                $q->where("over_stay", null);
        });


        // Eager loading relationships
        // $model->with(['visitor' => function ($q) use ($company_id) {
        //     $q->where('company_id', $company_id);
        // }, 'device_in' => function ($q) use ($company_id) {
        //     $q->where('company_id', $company_id);
        // }, 'device_out' => function ($q) use ($company_id) {
        //     $q->where('company_id', $company_id);
        // }]);


        $model->with(['company', 'branch', 'visitor.host', 'visitor.purpose', 'deviceInName', 'deviceOutName']);

        // Sorting
        $sortBy = $request->input('sortBy', 'date');

        $sortDesc = $request->input('sortDesc') === 'true';
        if ($sortBy != '')
            $model->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        return $model;
    }
}
