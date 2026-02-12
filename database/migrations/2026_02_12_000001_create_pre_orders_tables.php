<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_order_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('checksum')->unique();
            $table->timestamp('imported_at');
            $table->timestamps();
        });

        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_order_import_id')->constrained('pre_order_imports')->cascadeOnDelete();
            $table->string('source_pdf');
            $table->unsignedTinyInteger('status')->default(1);
            // 1 = Pending, 2 = Converted
            $table->unsignedBigInteger('converted_order_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['pre_order_import_id', 'source_pdf']);
        });

        Schema::create('pre_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_order_id')->constrained('pre_orders')->cascadeOnDelete();
            $table->unsignedInteger('row_index')->default(0);
            $table->string('reference')->nullable();
            $table->string('product')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('unit_price', 12, 3)->default(0);
            $table->decimal('total_price', 12, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_order_lines');
        Schema::dropIfExists('pre_orders');
        Schema::dropIfExists('pre_order_imports');
    }
};

