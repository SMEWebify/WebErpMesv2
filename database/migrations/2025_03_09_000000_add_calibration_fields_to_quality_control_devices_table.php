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
        Schema::table('quality_control_devices', function (Blueprint $table) {
            $table->dateTime('calibrated_at')->nullable()->after('picture');
            $table->dateTime('calibration_due_at')->nullable()->after('calibrated_at');
            $table->string('calibration_status')->nullable()->after('calibration_due_at');
            $table->string('calibration_provider')->nullable()->after('calibration_status');
            $table->string('location')->nullable()->after('calibration_provider');
            $table->decimal('capability_index', 8, 3)->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quality_control_devices', function (Blueprint $table) {
            $table->dropColumn([
                'calibrated_at',
                'calibration_due_at',
                'calibration_status',
                'calibration_provider',
                'location',
                'capability_index',
            ]);
        });
    }
};
