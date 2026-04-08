<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies_document_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('companies_id');
            $table->enum('document_type', ['quote', 'order', 'delivery', 'invoice']);
            $table->unsignedBigInteger('companies_contacts_id')->nullable();
            $table->unsignedBigInteger('companies_addresses_id')->nullable();

            $table->unique(['companies_id', 'document_type']);

            $table->foreign('companies_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('companies_contacts_id')
                  ->references('id')->on('companies_contacts')
                  ->onDelete('set null');

            $table->foreign('companies_addresses_id')
                  ->references('id')->on('companies_addresses')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies_document_defaults');
    }
};
