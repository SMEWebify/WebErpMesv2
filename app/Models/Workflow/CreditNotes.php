<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Invoices;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Services\CreditNoteCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNotes extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code', 
        'label', 
        'invoices_id', 
        'companies_id', 
        'companies_contacts_id', 
        'companies_addresses_id', 
        'statu', 
        'user_id', 
        'reason', 
        'validated_by', 
        'validated_at'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class);
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

    public function creditNotelines()
    {
        return $this->hasMany(CreditNoteLines::class, 'credit_note_id');
    }

    public function getTotalPriceAttribute()
    {
        $creditNoteCalculator = new CreditNoteCalculatorService($this);
        return $creditNoteCalculator->getTotalPrice();
    }
    
    // Relationship with the files associated with the delevery
    public function files()
    {
        return $this->hasMany(File::class);
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
