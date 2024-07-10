<?php

namespace App\Models\Workflow;

use App\Models\File;
use App\Models\User;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Services\InvoiceCalculatorService;
use App\Models\Workflow\InvoiceLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoices extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['uuid',
                            'code', 
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
                            'payment_date',
                            'due_date',
                            'export_date',
                            'incoterm',
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
        $InvoiceCalculatorService = new InvoiceCalculatorService($this);
        return $InvoiceCalculatorService->getTotalPrice();
    }

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
