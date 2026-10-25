<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de contrôle d'un article pour une révision (indice) : le plan de la GED
 * sur lequel les cotes sont bullées. Un plan validé n'est plus modifiable ;
 * une nouvelle révision validée rend la précédente obsolète.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('products_id')->constrained('products')->cascadeOnDelete();
            $table->string('revision', 50)->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedInteger('page_count')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('thumbnail_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['products_id', 'revision', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_plans');
    }
};
