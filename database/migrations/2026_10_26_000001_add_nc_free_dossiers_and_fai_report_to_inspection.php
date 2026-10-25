<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - inspection_measures.quality_non_conformity_id : la NC déclarée depuis une
 *   mesure hors tolérance (une NC du module qualité, plus de table à part) ;
 * - inspection_projects.companies_id facultatif : un contrôle libre (article
 *   choisi à la main, sans tâche) n'a pas forcément de client ;
 * - champs du rapport premier article (lot 3) : type complet / partiel,
 *   signatures « vérifié par » / « approuvé par », accord client, commentaire,
 *   champs libres du formulaire 1, PDF rangé dans la GED.
 * Additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_measures', function (Blueprint $table) {
            $table->foreignId('quality_non_conformity_id')->nullable()->after('quality_control_device_id')
                ->constrained('quality_non_conformities')->nullOnDelete();
        });

        Schema::table('inspection_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('companies_id')->nullable()->change();
        });

        Schema::table('inspection_projects', function (Blueprint $table) {
            $table->string('fai_type', 10)->default('full');
            $table->json('report_fields')->nullable();
            $table->text('report_comment')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('customer_approval')->nullable();
            $table->date('customer_approved_at')->nullable();
            $table->foreignId('report_file_id')->nullable()->constrained('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inspection_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('report_file_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'fai_type', 'report_fields', 'report_comment', 'verified_at', 'approved_at',
                'customer_approval', 'customer_approved_at',
            ]);
        });

        Schema::table('inspection_measures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quality_non_conformity_id');
        });
    }
};
