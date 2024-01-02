<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leads extends Model
{
    use HasFactory;

    protected $fillable = [
        'companies_id',
        'companies_contacts_id',
        'companies_addresses_id',
        'user_id',
        'status',
        'source',
        'priority',
        'campaign',
        'comment',
    ];

    // Relationship with the company associated with the lead
    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

   // Relationship with the contact associated with the lead
    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    // Relationship with the adresse associated with the lead
    public function adresse()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }

    // Relationship with the user associated with the lead
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //Get Created attribute like '	06 December 2023'
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}
