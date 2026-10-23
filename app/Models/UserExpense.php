<?php

namespace App\Models;

use App\Models\Workflow\Orders;
use App\Models\UserExpenseReport;
use App\Models\UserExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserExpense extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= [
        'report_id',
        'user_id',
        'category_id',
        'expense_date',
        'location',
        'description',
        'amount',
        'payer_id',
        'scan_file',
        'file_id',
        'tax',
        'order_id'
    ];

    /**
     * Get the report associated with the user expense.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function report()
    {
        return $this->belongsTo(UserExpenseReport::class, 'report_id');
    }
    
    /**
     * Get the user associated with the user expense.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category associated with the user expense.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(UserExpenseCategory::class, 'category_id');
    }

    /**
     * Get the payer associated with the user expense.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Receipt (GED file of the report) this expense was entered from.
     */
    public function receipt()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the order associated with the user expense.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
