<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relances de factures : niveaux paramétrables, dernier niveau atteint sur la
 * facture (colonnes nullables) et historique de chaque relance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminder_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('level')->unique();
            $table->string('label');
            // Jours après l'échéance à partir desquels ce niveau est proposé.
            $table->unsignedSmallInteger('delay_days');
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('color', 20)->default('warning');
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('reminder_level_id')->nullable()->after('due_date')
                ->constrained('invoice_reminder_levels')->nullOnDelete();
            $table->timestamp('last_reminder_at')->nullable()->after('reminder_level_id');
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('reminder_level_id')->nullable()->constrained('invoice_reminder_levels')->nullOnDelete();
            $table->timestamp('reminded_at');
            // email | phone | letter
            $table->string('channel', 16);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('email_sent')->default(false);
            $table->foreignId('email_log_id')->nullable()->constrained('email_logs')->nullOnDelete();
            // État de la facture au moment de la relance.
            $table->decimal('amount_due', 15, 2)->nullable();
            $table->integer('days_late')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'reminded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reminder_level_id');
            $table->dropColumn('last_reminder_at');
        });

        Schema::dropIfExists('invoice_reminder_levels');
    }
};
