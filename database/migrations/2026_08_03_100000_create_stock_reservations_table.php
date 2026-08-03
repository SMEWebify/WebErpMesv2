<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('products_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Ce que la tâche demande (qty tâche - qty déjà consommée par stock_moves).
            // Recalculé à chaque event, jamais saisi manuellement.
            $table->decimal('qty_requested', 12, 3)->default(0);

            // Ce qui est effectivement couvert par du stock physique disponible,
            // selon la priorité end_date ASC.
            $table->decimal('qty_reserved', 12, 3)->default(0);

            // qty_requested - qty_reserved. Sert au futur écran "manques".
            $table->decimal('qty_missing', 12, 3)->default(0);

            $table->string('status', 20)->default('active');

            $table->string('tracability')->nullable();

            $table->timestamps();

            $table->unique(['task_id', 'products_id']);
            $table->index(['products_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
