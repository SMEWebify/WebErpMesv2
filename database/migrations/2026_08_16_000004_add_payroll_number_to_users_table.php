<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee number as known by the payroll software.
 *
 * The export has to key on the matricule the payroll side already uses, not on
 * an internal id; when it is left empty the export falls back on users.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('payroll_number', 50)->nullable()->after('job_title');
            $table->index('payroll_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['payroll_number']);
            $table->dropColumn('payroll_number');
        });
    }
};
