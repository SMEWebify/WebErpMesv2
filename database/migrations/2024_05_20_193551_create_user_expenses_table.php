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
        Schema::create('user_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('user_expense_reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('user_expense_categories')->onDelete('cascade');
            $table->date('expense_date');
            $table->string('location');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->foreignId('payer_id')->constrained('users')->onDelete('cascade');
            $table->string('scan_file')->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_expenses');
    }
};
