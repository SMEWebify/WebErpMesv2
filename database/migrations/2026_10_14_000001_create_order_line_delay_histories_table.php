<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des reports d'une ligne de commande : chaque changement de
     * delivery_date ou d'internal_delay, écrit par OrderLinesObserver. Les
     * colonnes de la ligne ne gardent que la dernière valeur saisie.
     */
    public function up(): void
    {
        if (Schema::hasTable('order_line_delay_histories')) {
            return;
        }

        Schema::create('order_line_delay_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_lines_id');
            $table->string('field', 20); // delivery_date | internal_delay
            $table->date('old_value')->nullable();
            $table->date('new_value')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('origin', 20)->default('manual');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable()->useCurrent();

            // Index déclaré avant la contrainte : MySQL le réutilise au lieu d'en créer un second.
            $table->index('order_lines_id');
            $table->foreign('order_lines_id')->references('id')->on('order_lines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_line_delay_histories');
    }
};
