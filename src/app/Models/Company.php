<?php

namespace App\Models;

use App\Models\TradeLicense;
use App\Models\CompanyContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password', 'updated_at',
    ];
    protected $dates = [
        'member_from', 'expiry',
    ];

    protected $casts = [
        'member_from' => 'date:Y/m/d',
        'expiry' => 'date:Y/m/d',
        'created_at' => 'datetime:d-M-y',
        'no_branch' => 'boolean',
    ];
    protected $appends = ['show_member_from', 'show_expiry'];

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function contact()
    {
        return $this->hasOne(CompanyContact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modules()
    {
        return $this->hasOne(AssignModule::class);
    }

    public function trade_license()
    {
        return $this->hasOne(TradeLicense::class);
    }

    public function shift()
    {
        return $this->hasOne(Shift::class);
    }
    public function company_mail_content()
    {
        return $this->hasMany(MailContent::class, "company_id")->where('name', 'email');
    }
    public function company_whatsapp_content()
    {
        return $this->hasMany(MailContent::class, "company_id")->where('name', 'whatsapp');
    }


    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function attendancd_logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }


    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payroll_settings()
    {
        return $this->hasOne(PayrollSetting::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function companybranches()
    {
        return $this->hasMany(CompanyBranch::class);
    }
    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function getLogoAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('upload/' . $value);
    }

    public function getCreatedAtAttribute($value): string
    {
        return date('d M Y', strtotime($value));
    }

    public function getShowMemberFromAttribute(): string
    {
        return date('d M Y', strtotime($this->member_from));
    }

    public function getShowExpiryAttribute(): string
    {
        return date('d M Y', strtotime($this->expiry));
    }

    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('id', 'desc');
        });
    }

    protected function companyCode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value < 1000 ? 'AE000' . $value : 'AE' . $value,
            set: fn ($value) => $value,
        );
    }
}
