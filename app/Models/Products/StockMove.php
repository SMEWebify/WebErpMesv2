<?php

namespace App\Models\Products;

use App\Models\File;
use App\Models\User;
use App\Models\Planning\Task;
use Illuminate\Support\Number;
use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Products\Batch;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMove extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['user_id', 
                            'qty', 
                            'reserve_qty',
                            'bad_qty',
                            'stock_location_products_id',
                            'order_line_id',
                            'task_id',
                            'purchase_receipt_line_id',
                            'batch_id',
                            'typ_move',
                            'component_price',
                            'company_id',
                            'x_size',
                            'y_size',
                            'z_size',
                            'nb_part',
                            'surface_perc',
                            'nest_path',
                            'nest_picture_path',
                            'status',
                            'tracability',
                        ];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function StockLocationProducts()
    {
        return $this->belongsTo(StockLocationProducts::class, 'stock_location_products_id');
    }

    public function OrderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    public function Task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function purchaseReceiptLines()
    {
        return $this->belongsTo(PurchaseReceiptLines::class, 'purchase_receipt_line_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    // Relationship with the files associated with the delevery
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    // Relationship with the files only photo associated with the delevery
    public function photos()
    {
        return $this->morphToMany(File::class, 'fileable')->where('as_photo', 1);
    }


    /**
     * Get the formatted selling price attribute.
     *
     * This method retrieves the selling price attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted selling price.
     */
    public function getFormattedComponentPriceAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->component_price, $currency, config('app.locale'));
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
        return date('d F Y', strtotime($this->created_at));
    }
}
