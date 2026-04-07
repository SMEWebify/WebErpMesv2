<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'orders',
        'quotes',
        'credit_notes',
        'order_lines',
        'quote_lines',
        'invoice_lines',
        'credit_note_lines',
        'delivery_lines',
        'returns',
        'return_lines',
        'leads',
        'opportunities',
        'pre_orders',
        'pre_order_lines',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
