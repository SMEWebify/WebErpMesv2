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
        Schema::create('andon_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->nullable();
            $table->foreignId('methods_ressources_id')->nullable();
            $table->string('type'); // Type of alert (e.g., quality, breakdown, etc.)
            $table->text('description')->nullable(); // Description of the issue
            $table->integer('status')->default(1);// Status of the alert
            // 1 = non_resolu, 2 = en_cours, 3 = resolu 
            $table->timestamp('triggered_at')->useCurrent(); // Time when the alert was triggered
            $table->timestamp('resolved_at')->nullable(); // Time when the alert was resolved
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // User responsible for resolving
            $table->timestamps(); // Created at and updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('andon_alerts');
    }
};
