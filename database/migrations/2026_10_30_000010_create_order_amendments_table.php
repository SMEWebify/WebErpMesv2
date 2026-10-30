<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avenants de commande (ISO 19443) : un enregistrement par avenant au lieu des
 * colonnes change_requested_by / change_reason / change_approved_at d'orders,
 * écrasées à chaque changement. Ces colonnes restent en base, lues pour l'historique.
 *
 * Créer un avenant rouvre la revue de contrat ; son visa referme la revue et
 * complète l'avenant (App\Services\Workflow\OrderAmendments). Additive : aucune
 * commande existante n'a d'avenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->text('reason');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('amendment_date');
            // pending | approved | rejected | cancelled
            $table->string('status', 20)->default('pending');
            $table->decimal('amount_before_ttc', 15, 4)->default(0);
            $table->decimal('amount_after_ttc', 15, 4)->nullable();
            $table->string('review_decision', 20)->nullable();
            $table->text('review_gaps')->nullable();
            // Auteur et date du visa, ou de l'annulation.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            // Revue de la commande avant l'avenant (décision, auteur, date, écarts, check-list).
            $table->json('previous_review')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'number']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_amendments');
    }
};
