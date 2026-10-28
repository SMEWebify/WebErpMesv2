<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Motif de perte d'un devis ou d'une opportunité (prix, délai, technique…) :
 * référentiel paramétrable. Additive : colonnes nullable, les documents déjà
 * perdus restent sans motif et valides.
 *
 * Contrairement aux catégories d'affaire, les motifs usuels sont communs à toutes
 * les sociétés : ils sont insérés ici, renommables et désactivables depuis l'écran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loss_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label');
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        foreach (['quotes', 'opportunities'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('loss_reason_id')->nullable()
                    ->constrained('loss_reasons')->nullOnDelete();
                $table->text('loss_comment')->nullable();
            });
        }

        $now = now();
        foreach (['PRICE' => 10, 'DELAY' => 20, 'TECHNICAL' => 30, 'COMPETITOR' => 40, 'NO_FOLLOW_UP' => 50] as $code => $ordre) {
            DB::table('loss_reasons')->insert([
                'code'       => $code,
                'label'      => __('losses.defaults.'.$code),
                'ordre'      => $ordre,
                'active'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach (['quotes', 'opportunities'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('loss_reason_id');
                $table->dropColumn('loss_comment');
            });
        }

        Schema::dropIfExists('loss_reasons');
    }
};
