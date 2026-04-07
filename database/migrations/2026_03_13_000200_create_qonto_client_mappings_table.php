<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qonto_client_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('wem_client_id')->nullable()->index();
            $table->string('qonto_client_id')->nullable()->index();
            $table->string('sync_status', 50)->default('linked');
            $table->decimal('matching_score', 5, 2)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'wem_client_id']);
            $table->unique(['tenant_id', 'qonto_client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qonto_client_mappings');
    }
};
