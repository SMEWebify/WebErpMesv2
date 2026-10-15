<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encours client maximal. Les deux colonnes sont nullables : une société sans
 * plafond (cas de toutes les sociétés existantes) n'est jamais contrôlée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Montant TTC ; null = pas de plafond.
            $table->decimal('credit_limit', 15, 2)->nullable()->after('discount');
            // none | warn | block (App\Enums\CreditBlockLevel) ; null = none.
            $table->string('credit_block_level', 8)->nullable()->after('credit_limit');
        });

        // Passe-droit ponctuel : vaut pour un seul document, tracé avec son
        // auteur, son motif et l'état de l'encours au moment du forçage.
        Schema::create('credit_limit_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('companies_id')->constrained('companies')->cascadeOnDelete();
            $table->nullableMorphs('document');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->decimal('outstanding', 15, 2);
            $table->decimal('credit_limit', 15, 2);
            $table->decimal('projected', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_limit_overrides');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'credit_block_level']);
        });
    }
};
