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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('label');
            $table->unsignedBigInteger('invoices_id');
            $table->unsignedBigInteger('companies_id');
            $table->unsignedBigInteger('companies_contacts_id');
            $table->unsignedBigInteger('companies_addresses_id');
            $table->integer('statu')->default(1);
            // 1 = Pending approval, 2 = Approved, 3 = Rejected
            $table->unsignedBigInteger('user_id');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('invoices_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('companies_contacts_id')->references('id')->on('companies_contacts')->onDelete('cascade');
            $table->foreign('companies_addresses_id')->references('id')->on('companies_addresses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
