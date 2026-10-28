<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dérogations : preuve de l'accord du client (ISO 19443 / ISO 9001 § 8.7).
 *
 * - Colonnes ajoutées, toutes facultatives : client, contact, ligne de commande,
 *   date et signataire de la réponse du client, auteur et date de la validation.
 *   Les dérogations existantes restent valides telles quelles.
 * - `reply` et `decision` deviennent facultatives : la réponse du client n'existe
 *   pas encore quand la demande est créée.
 * - `quality_derogation_signatures` : enveloppes DocuSign envoyées au contact du
 *   client, sur le modèle de `quote_signatures`.
 * - Permission `quality-derogation-validate` (rôle Admin) : passer une dérogation
 *   en « Validé » et modifier une dérogation validée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_derogations', function (Blueprint $table) {
            $table->unsignedBigInteger('companies_id')->nullable()->after('quality_non_conformitie_id');
            $table->unsignedBigInteger('companies_contacts_id')->nullable()->after('companies_id');
            $table->unsignedBigInteger('order_lines_id')->nullable()->after('companies_contacts_id');
            $table->date('customer_reply_date')->nullable()->after('reply');
            $table->string('customer_signatory')->nullable()->after('customer_reply_date');
            $table->unsignedBigInteger('validated_by')->nullable()->after('decision');
            $table->timestamp('validated_at')->nullable()->after('validated_by');

            $table->index('order_lines_id');
            $table->index('companies_id');
        });

        Schema::table('quality_derogations', function (Blueprint $table) {
            $table->text('reply')->nullable()->change();
            $table->text('decision')->nullable()->change();
        });

        Schema::create('quality_derogation_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quality_derogation_id');
            $table->string('provider', 32);
            $table->string('envelope_id')->unique();
            // sent | delivered | completed | declined | voided
            $table->string('status', 16)->index();
            $table->string('signer_name');
            $table->string('signer_email');
            // Empreinte du contenu envoyé : une dérogation modifiée depuis invalide l'enveloppe.
            $table->string('content_hash', 64);
            $table->text('status_reason')->nullable();
            $table->unsignedBigInteger('signed_file_id')->nullable();
            $table->unsignedBigInteger('certificate_file_id')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->foreign('quality_derogation_id')->references('id')->on('quality_derogations')->cascadeOnDelete();
            $table->index(['quality_derogation_id', 'status']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'quality-derogation-validate', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'quality-derogation-validate')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('quality_derogation_signatures');

        Schema::table('quality_derogations', function (Blueprint $table) {
            $table->dropIndex(['order_lines_id']);
            $table->dropIndex(['companies_id']);
            $table->dropColumn([
                'companies_id',
                'companies_contacts_id',
                'order_lines_id',
                'customer_reply_date',
                'customer_signatory',
                'validated_by',
                'validated_at',
            ]);
        });
    }
};
