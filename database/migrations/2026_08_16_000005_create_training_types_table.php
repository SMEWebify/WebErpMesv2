<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referential of the trainings and authorisations an employee can hold.
 *
 * osh_formations already tracked trainings per employee, but type_of_training
 * was free text: "CACES 3" and "Caces 3" never grouped, so no matrix could be
 * built out of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label');
            $table->string('color', 7)->nullable();
            // Default validity, used to suggest an expiry date. 0 = unlimited.
            $table->unsignedInteger('validity_months')->default(0);
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('training_type_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_type_id')->constrained('training_types')->cascadeOnDelete();
            $table->unsignedBigInteger('methods_ressources_id');
            $table->timestamps();

            $table->unique(['training_type_id', 'methods_ressources_id'], 'training_type_resource_unique');
            $table->index('methods_ressources_id');
        });

        Schema::table('osh_formations', function (Blueprint $table) {
            $table->foreignId('training_type_id')->nullable()->after('user_id')
                ->constrained('training_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('osh_formations', function (Blueprint $table) {
            $table->dropForeign(['training_type_id']);
            $table->dropColumn('training_type_id');
        });

        Schema::dropIfExists('training_type_resource');
        Schema::dropIfExists('training_types');
    }
};
