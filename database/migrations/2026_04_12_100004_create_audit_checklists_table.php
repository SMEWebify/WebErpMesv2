<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('iso_clause')->nullable();
            $table->unsignedBigInteger('audit_process_id')->nullable();
            $table->foreign('audit_process_id')->references('id')->on('audit_processes')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_checklists');
    }
};
