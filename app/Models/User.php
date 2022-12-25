<?php

namespace App\Models;

use App\Models\File;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Quality\QualityAction;
use App\Models\Methods\MethodsSection;
use App\Models\Products\StockLocation;
use App\Models\Planning\TaskActivities;
use Illuminate\Notifications\Notifiable;
use App\Models\Quality\QualityDerogation;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;
use App\Models\Products\StockLocationProducts;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory,LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'personnal_phone_number',
        'desc',
        'born_date',
        'companies_notification',
        'users_notification',
        'quotes_notification',
        'orders_notification',
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

    public function quotes()
    {
        return $this->hasMany(Quotes::class);
    }

    public function orders()
    {
        return $this->hasMany(Orders::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email']);
        // Chain fluent methods for configuration options
    }
}
