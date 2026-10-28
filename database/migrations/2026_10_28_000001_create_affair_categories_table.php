<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catégorie d'affaire (débitmétrie, pyrométrie, mécanique…) : référentiel
 * paramétrable, porté par l'opportunité puis repris par le devis et la commande.
 * Additive : colonnes nullable, aucune donnée existante touchée. La table est
 * créée vide (les catégories sont propres à chaque société, cf. AffairCategorySeeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affair_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label');
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        foreach (['opportunities', 'quotes', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('affair_category_id')->nullable()
                    ->constrained('affair_categories')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['opportunities', 'quotes', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('affair_category_id');
            });
        }

        Schema::dropIfExists('affair_categories');
    }
};
