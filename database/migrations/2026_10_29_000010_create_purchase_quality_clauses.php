<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clauses qualité des commandes d'achat (certificat EN 10204, code de construction,
 * niveau nucléaire, exigences de sûreté).
 *
 * - criticité : trois colonnes nullable sur l'article, recopiées puis modifiables
 *   sur la ligne d'achat (le type de certificat servira au contrôle à la réception) ;
 * - référentiel des clauses et règles d'application (table de correspondance) ;
 * - copie figée des clauses sur la commande : l'édition du référentiel ne change
 *   pas une commande émise.
 *
 * Additive. Les commandes déjà sorties de « en cours » sont marquées figées sans
 * clause : leur PDF reste celui d'avant, quelles que soient les règles saisies ensuite.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'purchase_lines'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('certificate_type', 10)->nullable();
                $table->string('construction_code', 20)->nullable();
                $table->string('nuclear_level', 10)->nullable();
            });
        }

        Schema::create('purchase_quality_clauses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label');
            $table->text('text_fr');
            $table->text('text_en')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Une règle : critères renseignés tous égaux à ceux d'une ligne ; un critère
        // vide accepte toute valeur. Au moins un critère (contrôlé par l'écran).
        Schema::create('purchase_quality_clause_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_quality_clause_id')->constrained('purchase_quality_clauses')->cascadeOnDelete();
            $table->string('certificate_type', 10)->nullable();
            $table->string('construction_code', 20)->nullable();
            $table->string('nuclear_level', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_quality_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchases_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('purchase_quality_clause_id')->nullable()
                ->constrained('purchase_quality_clauses')->nullOnDelete();
            $table->string('code', 30);
            $table->string('label');
            $table->text('text_fr');
            $table->text('text_en')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('quality_clauses_frozen_at')->nullable();
        });

        DB::table('purchases')->where('statu', '!=', 1)->update(['quality_clauses_frozen_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('quality_clauses_frozen_at');
        });

        Schema::dropIfExists('purchase_order_quality_clauses');
        Schema::dropIfExists('purchase_quality_clause_rules');
        Schema::dropIfExists('purchase_quality_clauses');

        foreach (['products', 'purchase_lines'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['certificate_type', 'construction_code', 'nuclear_level']);
            });
        }
    }
};
