<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies_contacts', function (Blueprint $table) {
            $table->boolean('is_customer_portal_user')->default(false)->after('password');
        });

        // Backfill : tout contact ayant déjà un mot de passe est un utilisateur portail.
        // Évite de bloquer les accès existants après déploiement.
        DB::statement("
            UPDATE companies_contacts
            SET is_customer_portal_user = 1
            WHERE password IS NOT NULL AND password != ''
        ");
    }

    public function down(): void
    {
        Schema::table('companies_contacts', function (Blueprint $table) {
            $table->dropColumn('is_customer_portal_user');
        });
    }
};
