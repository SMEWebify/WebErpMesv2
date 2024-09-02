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
        Schema::create('packagings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deliverys_id');
            $table->string('code', 100);
            $table->string('type', 200);
            $table->tinyInteger('status')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->decimal('gross_weight', 20, 8);
            $table->decimal('net_weight', 20, 8);
            $table->decimal('length', 20, 8);
            $table->decimal('width', 20, 8);
            $table->decimal('height', 20, 8);
            $table->text('comment')->nullable();
            $table->dateTime('packing_date')->nullable();
            $table->dateTime('loaded_date')->nullable();
            $table->text('load_comment')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('deliverys_id')->references('id')->on('deliverys')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packagings');
    }
};
