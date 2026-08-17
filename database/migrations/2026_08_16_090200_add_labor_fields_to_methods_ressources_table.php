<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ressource n'est plus forcément une machine.
 *
 * `is_labor` distingue une capacité humaine (équipe, poste manuel) d'une
 * capacité machine : c'est ce qui détermine la source d'indisponibilité à
 * déduire (absences pour les personnes, arrêts machine pour les machines, lot 4)
 * et ce qui permet enfin de planifier une opération manuelle — ébavurage,
 * assemblage, contrôle — avec une vraie capacité.
 *
 * Cet axe est indépendant de methods_services.type : ce dernier dit la nature
 * économique de la ligne (productive / matière / sous-traitance), pas de quoi
 * est faite la capacité qui l'exécute.
 *
 * `labor_ratio` = opérateurs consommés par heure de la ressource.
 * 0 (défaut) = aucune main-d'œuvre déclarée, comportement inchangé tant que
 * l'atelier n'a pas configuré ses ressources humaines ; 1 = un opérateur dédié ;
 * 0.5 = un opérateur pour deux machines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methods_ressources', function (Blueprint $table) {
            $table->boolean('is_labor')->default(false)->after('capacity');
            $table->decimal('labor_ratio', 4, 2)->default(0)->after('is_labor');
        });
    }

    public function down(): void
    {
        Schema::table('methods_ressources', function (Blueprint $table) {
            $table->dropColumn(['is_labor', 'labor_ratio']);
        });
    }
};
