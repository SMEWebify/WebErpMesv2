<?php

namespace App\Models\Workflow;

use App\Models\User;
use App\Models\Companies\Companies;
use App\Models\Workflow\InvoiceLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Services\InvoiceCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoices extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',
                            'invoice_type',
                            'accounting_status',
                            'user_id',
                            'bank_id',
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

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLines::class)->orderBy('ordre');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getTotalPriceAttribute()
    {
        $invoiceCalculator = new InvoiceCalculator($this);
        return $invoiceCalculator->getTotalPrice();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
