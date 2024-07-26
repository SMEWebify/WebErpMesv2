<?php

namespace App\Models;

use App\Models\File;
use App\Models\Planning\Task;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Times\TimesAbsence;
use Spatie\Activitylog\LogOptions;
use App\Models\Admin\Announcements;
use App\Models\Companies\Companies;
use App\Models\Quality\QualityAction;
use App\Models\Methods\MethodsSection;
use App\Models\Products\StockLocation;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Planning\TaskActivities;
use Illuminate\Notifications\Notifiable;
use App\Models\Quality\QualityDerogation;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;
use App\Models\Admin\UserEmploymentContracts;
use App\Models\Products\StockLocationProducts;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use HasRoles, HasFactory,LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email', //crypt 
        'password',
        'personnal_phone_number', //crypt 
        'desc',
        'born_date', //crypt 
         /** Add for WebErpMesv2/issues/142 */
        'nationality',
        'gender',
        'marital_status',
        'ssn_num', //crypt 
        'nic_num', //crypt 
        'driving_license', //crypt 
        'driving_license_exp_date',
        'employment_status',
        'job_title',
        'pay_grade',
        'work_station_id',
        'address1', //crypt 
        'address2', //crypt 
        'city', //crypt 
        'country', //crypt 
        'province', //crypt 
        'postal_code', //crypt 
        'home_phone', //crypt 
        'mobile_phone', //crypt 
        'private_email', //crypt 
        'joined_date',
        'confirmation_date',
        'termination_date',
        'supervisor_id',
        'section_id',
        'custom1',
        'custom2',
        'custom3',
        'custom4',
        'statu',
         /** end add for WebErpMesv2/issues/142 */
        'companies_notification',
        'users_notification',
        'quotes_notification',
        'orders_notification',
        'non_conformity_notification',
        'banned_until',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $dates = [
        'banned_until'
    ];

    public function adminlte_image()
    {
        if(empty($this->image_url)){
            $this->image_url="img_avatar.png";
        }

        return asset('/images/profiles/' . $this->image_url);
    }
  
    public function adminlte_profile_url()
    {
        return route('user.profile', $this->id);
    }

    public function adminlte_desc()
    {
        return $this->desc;
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function Announcements()
    {
        return $this->hasMany(Announcements::class);
    }

    public function companie()
    {
        return $this->hasMany(Companies::class);
    }

    public function section()
    {
        return $this->hasMany(MethodsSection::class);
    }

    public function quality_actions()
    {
        return $this->hasMany(QualityAction::class);
    }

    public function quality_control_device()
    {
        return $this->hasMany(QualityControlDevice::class);
    }

    public function quality_derogations()
    {
        return $this->hasMany(QualityDerogation::class);
    }

    public function quality_non_conformities()
    {
        return $this->hasMany(QualityNonConformity::class);
    }
    public function getNcCountAttribute()
    {
        return $this->quality_non_conformities()->count();
    }

    public function absence_request()
    {
        return $this->hasMany(TimesAbsence::class);
    }

    public function stock_location()
    {
        return $this->hasMany(StockLocation::class);
    }

    public function stock_location_product()
    {
        return $this->hasMany(StockLocationProducts::class);
    }

    public function leads()
    {
        return $this->hasMany(Leads::class);
    }

    public function getLeadsCountAttribute()
    {
        return $this->leads()->count();
    }

    public function quotes()
    {
        return $this->hasMany(Quotes::class);
    }

    public function getQuotesCountAttribute()
    {
        return $this->quotes()->count();
    }

    public function orders()
    {
        return $this->hasMany(Orders::class);
    }

    public function getOrdersCountAttribute()
    {
        return $this->orders()->count();
    }
    
    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('ordre')->whereNotNull('order_lines_id');
    }

    public function taskActivities()
    {
        return $this->hasMany(TaskActivities::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function userEmploymentContracts()
    {
        return $this->hasMany(UserEmploymentContracts::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email']);
        // Chain fluent methods for configuration options
    }

    // Encrypt while setting
    public function setPersonnalPhoneNumberAttribute($value)
    {
        $this->attributes['personnal_phone_number'] = Crypt::encrypt($value);
    }

    // Decrypt while getting
    public function getPersonnalPhoneNumberAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setBornDateAttribute($value)
    {
        $this->attributes['born_date'] = Crypt::encrypt($value);
    }

    public function getBornDateAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setSsnNumAttribute($value)
    {
        $this->attributes['ssn_num'] = Crypt::encrypt($value);
    }

    public function getSsnNumAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setNicNumAttribute($value)
    {
        $this->attributes['nic_num'] = Crypt::encrypt($value);
    }

    public function getNicNumAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setDrivingLicenseAttribute($value)
    {
        $this->attributes['driving_license'] = Crypt::encrypt($value);
    }

    public function getDrivingLicenseAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setAddress1Attribute($value)
    {
        $this->attributes['address1'] = Crypt::encrypt($value);
    }

    public function getAddress1Attribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setAddress2Attribute($value)
    {
        $this->attributes['address2'] = Crypt::encrypt($value);
    }

    public function getAddress2Attribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setCityAttribute($value)
    {
        $this->attributes['city'] = Crypt::encrypt($value);
    }

    public function getCityAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setCountryAttribute($value)
    {
        $this->attributes['country'] = Crypt::encrypt($value);
    }

    public function getCountryAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setProvinceAttribute($value)
    {
        $this->attributes['province'] = Crypt::encrypt($value);
    }

    public function getProvinceAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setPostalCodeAttribute($value)
    {
        $this->attributes['postal_code'] = Crypt::encrypt($value);
    }

    public function getPostalCodeAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setHomePhoneAttribute($value)
    {
        $this->attributes['home_phone'] = Crypt::encrypt($value);
    }

    public function getHomePhoneAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setMobilePhoneAttribute($value)
    {
        $this->attributes['mobile_phone'] = Crypt::encrypt($value);
    }

    public function getMobilePhoneAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }

    public function setPrivateEmailAttribute($value)
    {
        $this->attributes['private_email'] = Crypt::encrypt($value);
    }

    public function getPrivateEmailAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null; 
        }
    }
}
