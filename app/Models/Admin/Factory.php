<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Factory extends Model
{
    use HasFactory;

    protected $table = 'factory';

    protected $fillable = ['name', 
                            'address',
                            'city', 
                            'zipcode',
                            'region',
                            'country',
                            'phone_number', 
                            'mail',
                            'web_site',
                            'pdf_header_font_color',
                            'picture',
                            'siren', 
                            'nat_regis_num',
                            'vat_num',
                            'accounting_vats_id',
                            'share_capital', 
                            'curency',
                            'add_day_validity_quote',
                            'add_delivery_delay_order', 
                            'public_link_cgv',
                            'add_cgv_to_pdf',
                            'cgv_file'];


    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }

    public function getImageFactoryPath(){
        // Example image is located at `public/images/factory`
        if($this->picture){
            return base64_encode(file_get_contents(public_path('images/factory/'.$this->picture)));
        }
        else{
            return null;
        }
    }
}
