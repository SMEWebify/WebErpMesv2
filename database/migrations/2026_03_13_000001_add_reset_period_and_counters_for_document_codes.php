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
        Schema::table('document_code_templates', function (Blueprint $table) {
            $table->string('reset_period')->default('none')->after('template');
        });

        Schema::create('document_code_counters', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('period_key');
            $table->unsignedBigInteger('current_value')->default(0);
            $table->timestamps();

            $table->unique(['document_type', 'period_key'], 'doc_code_counter_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_code_counters');

        Schema::table('document_code_templates', function (Blueprint $table) {
            $table->dropColumn('reset_period');
        });
    }
};
