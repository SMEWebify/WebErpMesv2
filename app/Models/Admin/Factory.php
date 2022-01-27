<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factory extends Model
{
    use HasFactory;

    protected $table = 'factory';

    protected $fillable = ['name', 
                            'ADDRESS',
                            'city', 
                            'zipcode',
                            'REGION',
                            'country',
                            'PHONE_NUMBER', 
                            'mail',
                            'WEB_SITE',
                            'picture',
                            'siren', 
                            'nat_regis_num',
                            'vat_num',
                            'accounting_vats_id',
                            'share_capital', 
                            'curency',
                            'add_day_validity_quote',
                            'add_delivery_delay_order'];

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }
}
