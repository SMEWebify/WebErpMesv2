<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relie un outil à l'article qui porte son stock (consommables, outillage acheté).
 * Additive et nullable : les outils existants restent sans article, rien ne change pour eux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methods_tools', function (Blueprint $table) {
            $table->foreignId('products_id')->nullable()->after('qty')
                ->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('methods_tools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('products_id');
        });
    }
};
