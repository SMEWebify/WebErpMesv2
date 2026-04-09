<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_code_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('id_padding')->default(0)->after('yearly_reset_day');
        });
    }

    public function down(): void
    {
        Schema::table('document_code_templates', function (Blueprint $table) {
            $table->dropColumn('id_padding');
        });
    }
};
