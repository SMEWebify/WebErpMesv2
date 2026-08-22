<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitlement granted to an employee for one leave type over one reference
 * period. Only the credit side lives here: what has been taken is always
 * recomputed from times_absences, so the two can never drift apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('entitled_days', 6, 2)->default(0);
            $table->decimal('carried_over_days', 6, 2)->default(0);
            $table->decimal('adjustment_days', 6, 2)->default(0);
            $table->string('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'leave_type_id', 'period_start'], 'leave_balances_user_type_period_unique');
            $table->index(['user_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
