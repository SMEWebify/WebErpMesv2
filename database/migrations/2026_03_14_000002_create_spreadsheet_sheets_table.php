<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spreadsheet_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spreadsheet_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('Sheet1');
            $table->integer('order')->default(0);
            $table->longText('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_sheets');
    }
};
