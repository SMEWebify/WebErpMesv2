<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAutoEmailReport extends Model
{
    use HasFactory;

    public const REPORT_OVERDUE_ORDERS = 'overdue_orders';
    public const REPORT_TOMORROW_ORDERS = 'tomorrow_orders';
    public const REPORT_LOW_STOCK = 'low_stock';

    protected $fillable = [
        'user_id',
        'report_type',
        'send_time',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function reportTypes(): array
    {
        return [
            self::REPORT_OVERDUE_ORDERS,
            self::REPORT_TOMORROW_ORDERS,
            self::REPORT_LOW_STOCK,
        ];
    }
}
