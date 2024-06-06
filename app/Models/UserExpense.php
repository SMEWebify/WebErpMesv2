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

    protected $fillable = [
        'report_id',
        'user_id',
        'category_id',
        'expense_date',
        'location',
        'description',
        'amount',
        'payer_id',
        'scan_file',
        'tax',
        'order_id'
    ];

    public function report()
    {
        return $this->belongsTo(UserExpenseReport::class, 'report_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(UserExpenseCategory::class, 'category_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
