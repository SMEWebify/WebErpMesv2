<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('quality_amdecs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->string('failure_mode'); // Failure mode
            $table->text('effect'); // Effect of failure
            $table->text('cause'); // Cause of failure
            $table->integer('severity'); // Gravity
            $table->integer('occurrence'); // Fréquence d'occurrence
            $table->integer('detection'); // Detection
            $table->integer('rpn'); // Risk Priority Number (Gravité x Fréquence x Détection)
            $table->text('current_control')->nullable(); // Current control
            $table->text('recommended_action')->nullable(); // Recommended actions
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amdecs');
    }
};
