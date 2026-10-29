<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revue de contrat (ISO 19443) : écarts avec l'offre et check-list des points
 * revus, à côté des colonnes reviewed_by / reviewed_at / review_decision de 2024.
 * Additive : une commande existante garde sa revue telle quelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('review_gaps')->nullable()->after('review_decision');
            $table->json('review_checklist')->nullable()->after('review_gaps');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['review_gaps', 'review_checklist']);
        });
    }
};
