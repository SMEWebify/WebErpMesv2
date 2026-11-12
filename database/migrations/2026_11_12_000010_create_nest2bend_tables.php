<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEST2BEND (simulation de pliage, SaaS externe en iframe).
 *
 * - `nest2bend_settings` : table singleton, comme `esignature_settings`. URL et clé API
 *   saisies dans l'écran Intégrations ; la clé est chiffrée par le cast `encrypted` du
 *   modèle. Les autres colonnes sont le dernier état lu sur GET /api/v1/compte, pour que
 *   les écrans n'appellent jamais NEST2BEND à l'affichage.
 * - `bending_simulations` : une ligne par session ouverte depuis l'ERP. Sert à rattacher
 *   un résultat à la ligne de devis qui l'a demandé : un resultId n'est accepté que s'il
 *   correspond à une session ouverte ici, pour cette ligne et cet utilisateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nest2bend_settings', function (Blueprint $table) {
            $table->id();
            $table->string('base_url')->nullable();
            // Cipher gonfle la taille en base64 → text plutôt que string.
            $table->text('api_key')->nullable();
            // none | unknown | invalid | expired | active (Nest2BendSetting::STATUS_*)
            $table->string('status', 16)->default('none');
            // droits.etat : actif | essai | grace | lecture_seule
            $table->string('etat', 32)->nullable();
            $table->boolean('peut_ecrire')->default(false);
            $table->timestamp('echeance')->nullable();
            $table->string('societe')->nullable();
            $table->json('modules')->nullable();
            $table->unsignedInteger('presses_max')->nullable();
            $table->timestamp('checked_at')->nullable();
            // Dernier échec technique (réseau, 5xx) ; l'état ci-dessus est alors gardé.
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('bending_simulations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_lines_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            // Fichier STEP envoyé (version de la GED au moment du clic).
            $table->unsignedBigInteger('file_id')->nullable();
            $table->string('reference_externe');
            $table->string('session_id', 64)->unique();
            $table->string('result_id', 64)->nullable()->unique();
            // pending | done
            $table->string('status', 16)->default('pending');
            $table->string('politique', 16)->nullable();
            $table->unsignedInteger('quantite')->nullable();
            $table->decimal('preparation_s', 12, 2)->nullable();
            $table->decimal('unitaire_s', 12, 2)->nullable();
            $table->json('compteurs')->nullable();
            $table->json('section')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bending_simulations');
        Schema::dropIfExists('nest2bend_settings');
    }
};
