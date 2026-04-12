<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_schedules', function (Blueprint $table) {
            $table->foreign('audit_checklist_id')->references('id')->on('audit_checklists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_schedules', function (Blueprint $table) {
            $table->dropForeign(['audit_checklist_id']);
        });
    }
};
