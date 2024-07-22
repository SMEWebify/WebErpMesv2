<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Orders;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Workflow\DeliveryLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deliverys extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['uuid',
                            'code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',  
                            'user_id',
                            'comment',
                            'order_id',
                        ];

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
        return $this->hasMany(File::class);
    }

    // Relationship with the files only photo associated with the delevery
    public function photos()
    {
        return $this->hasMany(File::class)->where('as_photo', 1);
    }

    public function QualityNonConformity()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    public function GetshortCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
    
    //Get Created attribute like '	06 December 2023'
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([ 'code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
