<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Quotes;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunitiesActivitiesLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Opportunities extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'companies_id',
        'companies_contacts_id',
        'companies_addresses_id',
        'user_id',
        'leads_id',
        'label',
        'budget',
        'close_date',
        'statu',
        'probality',
        'comment',
    ];

    // Relationship with the company associated with the Opportunities
    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

   // Relationship with the contact associated with the Opportunities
    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    // Relationship with the adresse associated with the Opportunities
    public function adresse()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }

    // Relationship with the user associated with the Opportunities
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with the lead associated with the Opportunities
    public function lead()
    {
        return $this->belongsTo(Leads::class, 'leads_id');
    }

    // Relationship with the files associated with the Opportunities
    public function files()
    {
        return $this->hasMany(File::class);
    }

    // Relationship with the quotes associated with the Opportunities
    public function quotes()
    {
        return $this->hasMany(Quotes::class);
    }

    // Relationship with the activities associated with the Opportunities
    public function activities()
    {
        return $this->hasMany(OpportunitiesActivitiesLogs::class);
    }

    // Relationship with the events associated with the Opportunities
    public function events()
    {
        return $this->hasMany(OpportunitiesEventsLogs::class);
    }

    //Get Created attribute like '	06 December 2023'
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}
