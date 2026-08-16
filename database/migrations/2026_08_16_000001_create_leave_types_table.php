<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nature of an absence (paid leave, RTT, sick leave...).
 *
 * times_absences already carried a duration coding (full day / half day /
 * hours) but nothing said *what* the absence was, which made any leave
 * entitlement report impossible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label');
            $table->string('color', 7)->nullable();
            // A sick leave is an absence but does not consume a balance.
            $table->boolean('counts_against_balance')->default(true);
            $table->decimal('default_annual_quota', 6, 2)->default(0);
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
