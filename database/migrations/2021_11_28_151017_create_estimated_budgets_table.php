<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimatedBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estimated_budgets', function (Blueprint $table) {
            $table->id();
            $table->year('year'); 
            $table->decimal('amount1', 10, 3)->default(0);
            $table->decimal('amount2', 10, 3)->default(0);
            $table->decimal('amount3', 10, 3)->default(0);
            $table->decimal('amount4', 10, 3)->default(0);
            $table->decimal('amount5', 10, 3)->default(0);
            $table->decimal('amount6', 10, 3)->default(0);
            $table->decimal('amount7', 10, 3)->default(0);
            $table->decimal('amount8', 10, 3)->default();
            $table->decimal('amount9', 10, 3)->default(0);
            $table->decimal('amount10', 10, 3)->default(0);
            $table->decimal('amount11', 10, 3)->default(0);
            $table->decimal('amount12', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estimated_budgets');
    }
}
