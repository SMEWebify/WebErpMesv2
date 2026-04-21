<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_location_products', function (Blueprint $table) {
            // Coût unitaire moyen pondéré (CUMP) recalculé et persisté à chaque
            // mouvement entrant. Permet la valorisation du stock sans recalculer
            // l'historique complet à chaque consultation.
            $table->decimal('unit_cost', 15, 4)->default(0)->after('mini_qty');
        });
    }

    public function down(): void
    {
        Schema::table('stock_location_products', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
