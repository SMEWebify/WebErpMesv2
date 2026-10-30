<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenu des colis d'un BL : quelle quantité de quelle ligne du BL est dans
 * quel colis. Une ligne peut être répartie sur plusieurs colis ; la quantité
 * qui n'est dans aucun colis s'imprime « hors colis » sur la liste de colisage.
 * Table nouvelle : les colis et lignes existants ne sont pas touchés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_id')->constrained('packagings')->cascadeOnDelete();
            $table->foreignId('delivery_line_id')->constrained('delivery_lines')->cascadeOnDelete();
            $table->decimal('qty', 15, 3);
            $table->timestamps();

            $table->unique(['packaging_id', 'delivery_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_lines');
    }
};
