<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Registre des risques d'une affaire (opportunité), suivi jusqu'à la livraison.
 *
 * Purement additive : table neuve, `affair_categories.requires_risk_register`
 * à faux pour toutes les catégories existantes (aucun avertissement tant qu'on
 * ne coche rien). `affair-risk-close` (clôture d'un risque critique) est donnée
 * au rôle Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affair_risks')) {
            Schema::create('affair_risks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('opportunities_id')->constrained('opportunities')->cascadeOnDelete();
                $table->foreignId('orders_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('label');
                $table->text('description')->nullable();
                // config('affairs.risks.categories')
                $table->string('category', 30);
                $table->unsignedTinyInteger('probability');
                $table->unsignedTinyInteger('severity');
                $table->text('mitigation')->nullable();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('due_date')->nullable();
                // open | mitigated | closed | occurred (AffairRiskRegister::STATUSES)
                $table->string('status', 20)->default('open');
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['opportunities_id', 'status']);
            });
        }

        Schema::table('affair_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('affair_categories', 'requires_risk_register')) {
                $table->boolean('requires_risk_register')->default(false);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'affair-risk-close', 'guard_name' => 'web']);
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();

        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('affair_risks');

        Schema::table('affair_categories', function (Blueprint $table) {
            $table->dropColumn('requires_risk_register');
        });
        // La permission est gardée : elle a pu être attribuée à d'autres rôles.
    }
};
