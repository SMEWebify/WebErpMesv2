<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Imbrication réellement découpée (programme de la FAO, de Nest2Prod MES ou saisi à
 * la main) : la sortie de tôle répartie sur les lignes de commande qu'elle sert.
 * Les estimations NestEngine n'y sont jamais écrites.
 *
 * Tables neuves + une colonne nullable sur stock_moves : rien ne change pour l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nests', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);                 // manual | n2p | import
            $table->string('external_id')->nullable();    // id de l'imbrication chez la source
            $table->string('name');                       // nom du programme
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('material')->nullable();
            $table->decimal('thickness', 10, 3)->nullable();
            $table->decimal('sheet_x', 10, 3)->nullable();
            $table->decimal('sheet_y', 10, 3)->nullable();
            $table->decimal('sheet_qty', 10, 3)->default(0);
            $table->decimal('usage_percent', 6, 2)->nullable();
            $table->string('machine')->nullable();
            $table->string('status', 20)->default('done'); // planned | in_progress | done | cancelled
            $table->timestamp('cut_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('remnants_recorded_at')->nullable();
            $table->json('payload')->nullable();          // dernier message reçu de la source
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('cut_at');
        });

        Schema::create('nest_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nest_id')->constrained('nests')->cascadeOnDelete();
            $table->unsignedBigInteger('order_line_id')->nullable()->index();
            $table->unsignedBigInteger('task_id')->nullable()->index();
            $table->string('part_ref')->nullable();       // nom de pièce de la FAO / code article
            $table->string('external_ref')->nullable();   // référence de la pièce chez la source
            $table->decimal('qty', 12, 3)->default(0);
            $table->decimal('good_qty', 12, 3)->nullable();
            $table->decimal('bad_qty', 12, 3)->nullable();
            $table->decimal('area_mm2', 16, 3)->nullable(); // surface d'une pièce
            $table->decimal('weight_kg', 12, 4)->nullable(); // poids d'une pièce
            $table->timestamps();
        });

        Schema::table('stock_moves', function (Blueprint $table) {
            $table->foreignId('nest_id')->nullable()->after('remnant_source_move_id')
                ->constrained('nests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nest_id');
        });
        Schema::dropIfExists('nest_parts');
        Schema::dropIfExists('nests');
    }
};
