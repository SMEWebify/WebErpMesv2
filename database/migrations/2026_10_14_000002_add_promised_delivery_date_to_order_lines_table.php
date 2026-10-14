<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Date de livraison promise au client, figée à la création de la ligne
     * (OrderLinesObserver). Les lignes existantes restent nulles : la reprise
     * passe par `php artisan wem:orders:backfill-promised-dates --dry-run`.
     */
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('order_lines', 'promised_delivery_date')) {
                $table->date('promised_delivery_date')->nullable()->after('delivery_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            if (Schema::hasColumn('order_lines', 'promised_delivery_date')) {
                $table->dropColumn('promised_delivery_date');
            }
        });
    }
};
