<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passe-droit sur un fournisseur non qualifié (App\Services\Purchases\SupplierQualification) :
 * vaut pour une seule commande d'achat, tracé avec son auteur, son motif et l'état de
 * l'évaluation au moment du forçage. Table neuve : rien ne change pour l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_qualification_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('companies_id')->constrained('companies')->cascadeOnDelete();
            $table->nullableMorphs('document');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // create | validate | supplier_change
            $table->string('context', 20);
            $table->text('reason');
            // unqualified | review_overdue
            $table->string('qualification_status', 20);
            $table->string('evaluation_status', 191)->nullable();
            $table->date('next_review_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_qualification_overrides');
    }
};
