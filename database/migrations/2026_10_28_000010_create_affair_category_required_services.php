<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opérations obligatoires par catégorie d'affaire (CND, épreuve hydrostatique,
 * DESP, peinture…) : postes de charge existants que la gamme de chaque ligne
 * article d'un devis doit contenir avant sa conversion en commande.
 *
 * required_operations_overrides : passe-droit ponctuel, tracé avec la commande
 * créée, son auteur, son motif et les opérations qui manquaient.
 * Additive : aucune catégorie n'exige rien tant que rien n'est coché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affair_category_required_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affair_category_id')->constrained('affair_categories')->cascadeOnDelete();
            $table->foreignId('methods_services_id')->constrained('methods_services')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['affair_category_id', 'methods_services_id'], 'affair_category_service_unique');
        });

        Schema::create('required_operations_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotes_id')->constrained('quotes')->cascadeOnDelete();
            $table->nullableMorphs('document');
            $table->foreignId('affair_category_id')->nullable()->constrained('affair_categories')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            // [{line_id, label, services: [{id, code, label}]}] au moment du forçage
            $table->json('missing');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('required_operations_overrides');
        Schema::dropIfExists('affair_category_required_services');
    }
};
