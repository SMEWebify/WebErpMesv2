<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qonto_sync_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('wem_client_id')->nullable()->index();
            $table->string('qonto_client_id')->nullable()->index();
            $table->decimal('matching_score', 5, 2)->nullable();
            $table->json('candidate_payload')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qonto_sync_reviews');
    }
};
