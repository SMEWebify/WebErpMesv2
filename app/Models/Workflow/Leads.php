<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Workflow\Opportunities;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leads extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= [
        'companies_id',
        'companies_contacts_id',
        'companies_addresses_id',
        'user_id',
        'statu',
        'source',
        'priority',
        'campaign',
        'comment',
    ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'lead';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Lead has been {$eventName}";
    }

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

    // Relationship with the lead associated with the Opportunities
    public function Opportunity()
    {
        return $this->hasMany(Opportunities::class, 'leads_id');
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
                                                'companies_id',
                                                'companies_contacts_id',
                                                'companies_addresses_id',
                                                'user_id',
                                                'statu',
                                                'source',
                                                'priority',
                                                'campaign',
                                                'comment',]);
        // Chain fluent methods for configuration options
    }
}
