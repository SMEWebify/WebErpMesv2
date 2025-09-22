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
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('label');
            $table->integer('statu')->default(1);
            // 1 => Received
            // 2 => Diagnosed
            // 3 => In rework
            // 4 => Closed
            $table->unsignedBigInteger('deliverys_id')->nullable();
            $table->unsignedBigInteger('quality_non_conformity_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('diagnosed_by')->nullable();
            $table->text('customer_report')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('deliverys_id')->references('id')->on('deliverys')->nullOnDelete();
            $table->foreign('quality_non_conformity_id')->references('id')->on('quality_non_conformities')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('diagnosed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
