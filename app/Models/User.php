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
use App\Models\HumanResources\Attendance;
use App\Models\Products\StockLocation;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Planning\TaskActivities;
use Illuminate\Notifications\Notifiable;
use App\Models\Quality\QualityDerogation;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;
use App\Models\Admin\UserEmploymentContracts;
use App\Models\Planning\AndonAlerts;
use App\Models\Products\StockLocationProducts;
use App\Models\UserAutoEmailReport;
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
    // Fillable attributes for mass assignment
    protected $fillable= [
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
        'return_notification',
        'banned_until',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    // Hidden attributes for arrays
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    // Cast attributes to native types
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Dates attributes
    protected $dates = [
        'banned_until'
    ];

    /**
     * Get the URL for the user's profile image.
     *
     * This method checks if the user's image URL is empty. If it is, it sets a default image URL.
     * It then returns the full URL to the user's profile image.
     *
     * @return string The URL to the user's profile image.
     */
    public function adminlte_image()
    {
        if (empty($this->image_url)) {
            $this->image_url = "img_avatar.png";
        }

        return asset('/images/profiles/' . $this->image_url);
    }

    /**
     * Get the URL for the user's profile.
     *
     * @return string
     */
    public function adminlte_profile_url()
    {
        return route('user.profile', $this->id);
    }

    /**
     * Get the description for the user.
     *
     * @return string
     */
    public function adminlte_desc()
    {
        return $this->desc;
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    /**
     * Define a one-to-many relationship with the Announcements model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Announcements()
    {
        return $this->hasMany(Announcements::class);
    }

    /**
     * Define a one-to-many relationship with the Companies model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companie()
    {
        return $this->hasMany(Companies::class);
    }

    public function autoEmailReports()
    {
        return $this->hasMany(UserAutoEmailReport::class);
    }

    /**
     * Define a one-to-many relationship with the MethodsSection model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function section()
    {
        return $this->hasMany(MethodsSection::class);
    }

    /**
     * Define a one-to-many relationship with Attendance records.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Define a one-to-many relationship with the QualityAction model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quality_actions()
    {
        return $this->hasMany(QualityAction::class);
    }

    /**
     * Define a one-to-many relationship with the QualityControlDevice model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quality_control_device()
    {
        return $this->hasMany(QualityControlDevice::class);
    }

    /**
     * Define a one-to-many relationship with the QualityDerogation model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quality_derogations()
    {
        return $this->hasMany(QualityDerogation::class);
    }

    /**
     * Define a one-to-many relationship with the QualityNonConformity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quality_non_conformities()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    /**
     * Get the count of quality non-conformities.
     *
     * @return int
     */
    public function getNcCountAttribute()
    {
        return $this->quality_non_conformities()->count();
    }

    /**
     * Define a one-to-many relationship with the TimesAbsence model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function absence_request()
    {
        return $this->hasMany(TimesAbsence::class);
    }

    /**
     * Define a one-to-many relationship with the StockLocation model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stock_location()
    {
        return $this->hasMany(StockLocation::class);
    }

    /**
     * Define a one-to-many relationship with the StockLocationProducts model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stock_location_product()
    {
        return $this->hasMany(StockLocationProducts::class);
    }

    /**
     * Define a one-to-many relationship with the Leads model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leads()
    {
        return $this->hasMany(Leads::class);
    }

    /**
     * Get the count of leads.
     *
     * @return int
     */
    public function getLeadsCountAttribute()
    {
        return $this->leads()->count();
    }

    /**
     * Define a one-to-many relationship with the Quotes model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quotes()
    {
        return $this->hasMany(Quotes::class);
    }

    /**
     * Get the count of quotes.
     *
     * @return int
     */
    public function getQuotesCountAttribute()
    {
        return $this->quotes()->count();
    }

    /**
     * Define a one-to-many relationship with the Orders model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Orders::class);
    }

    /**
     * Get the count of orders.
     *
     * @return int
     */
    public function getOrdersCountAttribute()
    {
        return $this->orders()->count();
    }

    /**
     * Define a one-to-many relationship with the Task model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('ordre')->whereNotNull('order_lines_id');
    }

    /**
     * Define a one-to-many relationship with the TaskActivities model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskActivities()
    {
        return $this->hasMany(TaskActivities::class);
    }

    /**
     * Define a one-to-many relationship with the File model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Define a one-to-many relationship with the AndonAlerts model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function andon()
    {
        return $this->hasMany(AndonAlerts::class);
    }

    /**
     * Define a one-to-many relationship with the UserEmploymentContracts model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userEmploymentContracts()
    {
        return $this->hasMany(UserEmploymentContracts::class);
    }

    /**
     * Get the activity log options.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email']);
        // Chain fluent methods for configuration options
    }

    /**
     * Encrypt the personal phone number while setting.
     *
     * @param string $value
     */
    public function setPersonnalPhoneNumberAttribute($value)
    {
        $this->attributes['personnal_phone_number'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the personal phone number while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getPersonnalPhoneNumberAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the born date while setting.
     *
     * @param string $value
     */
    public function setBornDateAttribute($value)
    {
        $this->attributes['born_date'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the born date while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getBornDateAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the SSN number while setting.
     *
     * @param string $value
     */
    public function setSsnNumAttribute($value)
    {
        $this->attributes['ssn_num'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the SSN number while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getSsnNumAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the NIC number while setting.
     *
     * @param string $value
     */
    public function setNicNumAttribute($value)
    {
        $this->attributes['nic_num'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the NIC number while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getNicNumAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the driving license while setting.
     *
     * @param string $value
     */
    public function setDrivingLicenseAttribute($value)
    {
        $this->attributes['driving_license'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the driving license while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getDrivingLicenseAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the address1 while setting.
     *
     * @param string $value
     */
    public function setAddress1Attribute($value)
    {
        $this->attributes['address1'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the address1 while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getAddress1Attribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the address2 while setting.
     *
     * @param string $value
     */
    public function setAddress2Attribute($value)
    {
        $this->attributes['address2'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the address2 while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getAddress2Attribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the city while setting.
     *
     * @param string $value
     */
    public function setCityAttribute($value)
    {
        $this->attributes['city'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the city while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getCityAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the country while setting.
     *
     * @param string $value
     */
    public function setCountryAttribute($value)
    {
        $this->attributes['country'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the country while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getCountryAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the province while setting.
     *
     * @param string $value
     */
    public function setProvinceAttribute($value)
    {
        $this->attributes['province'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the province while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getProvinceAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the postal code while setting.
     *
     * @param string $value
     */
    public function setPostalCodeAttribute($value)
    {
        $this->attributes['postal_code'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the postal code while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getPostalCodeAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the home phone while setting.
     *
     * @param string $value
     */
    public function setHomePhoneAttribute($value)
    {
        $this->attributes['home_phone'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the home phone while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getHomePhoneAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the mobile phone while setting.
     *
     * @param string $value
     */
    public function setMobilePhoneAttribute($value)
    {
        $this->attributes['mobile_phone'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the mobile phone while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getMobilePhoneAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Encrypt the private email while setting.
     *
     * @param string $value
     */
    public function setPrivateEmailAttribute($value)
    {
        $this->attributes['private_email'] = Crypt::encrypt($value);
    }

    /**
     * Decrypt the private email while getting.
     *
     * @param string $value
     * @return string|null
     */
    public function getPrivateEmailAttribute($value)
    {
        try {
            return decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }
}
