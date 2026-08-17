<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Découple service et ressource : une machine peut réaliser plusieurs services
 * (centre d'usinage perçage + fraisage, combinée poinçonnage/laser...).
 *
 * Avant : methods_ressources.methods_services_id en 1-n → il fallait dupliquer la
 * machine par service, ce qui comptait sa capacité autant de fois qu'il y avait
 * de doublons et autorisait de la planifier simultanément sur chacun d'eux.
 *
 * La colonne methods_ressources.methods_services_id est conservée comme
 * « service principal » (compat + affichage par défaut) et reste alimentée ;
 * l'affectation et le calcul de charge s'appuient désormais sur ce pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('methods_ressource_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('methods_ressources_id')->constrained('methods_ressources')->cascadeOnDelete();
            $table->foreignId('methods_services_id')->constrained('methods_services')->cascadeOnDelete();
            // Cadence relative de CETTE ressource pour CE service (1 = temps gamme nominal).
            $table->decimal('efficiency', 5, 3)->default(1);
            // Surcharge éventuelle du taux horaire du service pour cette ressource.
            $table->decimal('hourly_rate', 11, 3)->nullable();
            // Ordre de préférence à l'affectation automatique (0 = premier choix).
            $table->unsignedInteger('preference')->default(0);
            $table->timestamps();

            $table->unique(['methods_ressources_id', 'methods_services_id'], 'ressource_service_unique');
        });

        $this->backfill();
    }

    /**
     * Reprise de l'existant : le service unique de chaque ressource devient sa
     * première ligne de pivot. Le join filtre les ressources qui pointaient vers
     * un service supprimé (aucune FK n'existait sur l'ancienne colonne).
     */
    private function backfill(): void
    {
        $now = now();

        DB::table('methods_ressources')
            ->join('methods_services', 'methods_services.id', '=', 'methods_ressources.methods_services_id')
            ->orderBy('methods_ressources.id')
            ->select([
                'methods_ressources.id as ressource_id',
                'methods_services.id as service_id',
            ])
            ->chunk(500, function ($rows) use ($now) {
                DB::table('methods_ressource_service')->insertOrIgnore(
                    $rows->map(fn ($row) => [
                        'methods_ressources_id' => $row->ressource_id,
                        'methods_services_id'   => $row->service_id,
                        'efficiency'            => 1,
                        'hourly_rate'           => null,
                        'preference'            => 0,
                        'created_at'            => $now,
                        'updated_at'            => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('methods_ressource_service');
    }
};
