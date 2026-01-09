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
        Schema::table('order_site_implantations', function (Blueprint $table) {
            $table->integer('workforce')->nullable();
            $table->string('equipment')->nullable();
            $table->string('step')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_site_implantations', function (Blueprint $table) {
            $table->dropColumn([
                'workforce',
                'equipment',
                'step',
                'start_date',
                'end_date',
                'notes',
            ]);
        });
    }
};
