<?php

namespace App\Models\Purchases;

use App\Models\File;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\SupplierRating;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Workflow\Deliverys;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchases extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',  
                            'user_id',
                            'comment',
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

    public function PurchaseLines()
    {
        return $this->hasMany(PurchaseLines::class)->orderBy('ordre');
    }

    // Relationship with the files associated with the Purchases
    public function files()
    {
        return $this->hasMany(File::class);
    }

    // Relationship with the Rating associated with the Purchases
    public function Rating()
    {
        return $this->hasMany(SupplierRating::class);
    }
        
    // Relationship with the deliveries associated with the Purchases
    public function deliveries()
    {
        return $this->hasMany(Deliverys::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([ 'code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
