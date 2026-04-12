<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_checklist_id');
            $table->foreign('audit_checklist_id')->references('id')->on('audit_checklists')->cascadeOnDelete();
            $table->text('question');
            $table->string('iso_clause')->nullable();
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_checklist_items');
    }
};
