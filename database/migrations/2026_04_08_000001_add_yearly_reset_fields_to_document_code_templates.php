<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_code_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('yearly_reset_month')->default(1)->after('reset_period');
            $table->unsignedTinyInteger('yearly_reset_day')->default(1)->after('yearly_reset_month');
        });
    }

    public function down(): void
    {
        Schema::table('document_code_templates', function (Blueprint $table) {
            $table->dropColumn(['yearly_reset_month', 'yearly_reset_day']);
        });
    }
};
