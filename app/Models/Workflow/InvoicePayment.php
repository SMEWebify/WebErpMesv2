<?php

namespace App\Models\Workflow;

use App\Models\User;
use App\Models\Accounting\AccountingPaymentMethod;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method_id',
        'reference',
        'note',
        'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(AccountingPaymentMethod::class, 'payment_method_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
