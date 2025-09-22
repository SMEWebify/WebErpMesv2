<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Packaging;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Returns;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deliverys extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['uuid',
                            'code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',  
                            'user_id',
                            'comment',
                            'order_id',
                            'purchases_id',
                            'tracking_number',
                        ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'delivery';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Dilevery has been {$eventName}";
    }

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    public function adresse()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Orders()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function DeliveryLines()
    {
        return $this->hasMany(DeliveryLines::class)->orderBy('ordre');
    }

    // Relationship with the files associated with the delevery
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    /**
     * Get all of the email logs for the model.
     *
     * This function defines a polymorphic one-to-many relationship
     * between the model model and the EmailLog model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function emailLogs()
    {
        return $this->morphMany(EmailLog::class, 'emailable');
    }

    // Relationship with the files only photo associated with the delevery
    public function photos()
    {
        return $this->morphToMany(File::class, 'fileable')->where('as_photo', 1);
    }

    // Relationship with the packagings associated with the delevery
    public function packaging()
    {
        return $this->hasMany(Packaging::class);
    }

    // Relationship with purchase associated with the delevery
    public function purchase()
    {
        return $this->belongsTo(Purchases::class, 'purchases_id');
    }

    public function QualityNonConformity()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    public function returns()
    {
        return $this->hasMany(Returns::class, 'deliverys_id');
    }

    public function GetshortCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
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
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([ 
                                                'code', 
                                                'label', 
                                                'companies_id', 
                                                'companies_contacts_id',   
                                                'companies_addresses_id',  
                                                'statu',  
                                                'user_id',
                                                'comment',
                                                'order_id',
                                                'purchases_id',
                                                'tracking_number',]);
        // Chain fluent methods for configuration options
    }
}
