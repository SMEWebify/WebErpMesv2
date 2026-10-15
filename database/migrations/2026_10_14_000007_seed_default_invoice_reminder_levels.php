<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trois niveaux de relance par défaut et leurs modèles d'e-mail, pour que
 * l'écran « Factures à relancer » serve dès le déploiement. Rien n'est inséré
 * si des niveaux existent déjà. Textes modifiables dans Administration →
 * Modèles d'e-mail (type « Relance de facture »).
 *
 * Variables remplacées à l'envoi : {client}, {factures}, {total_du},
 * {retard}, {entreprise}.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('invoice_reminder_levels')->exists()) {
            return;
        }

        $levels = [
            [
                'level' => 1, 'label' => 'Rappel', 'delay_days' => 1, 'color' => 'info',
                'subject' => 'Rappel : facture(s) arrivée(s) à échéance',
                'content' => '<p>Bonjour,</p>'
                    . '<p>Sauf erreur de notre part, le règlement des factures ci-dessous ne nous est pas parvenu :</p>'
                    . '<div>{factures}</div>'
                    . '<p>Montant restant dû : <strong>{total_du}</strong>.</p>'
                    . '<p>Si votre paiement est en cours, merci de ne pas tenir compte de ce message. Vous trouverez les factures en pièces jointes.</p>'
                    . '<p>Cordialement,<br>{entreprise}</p>',
            ],
            [
                'level' => 2, 'label' => 'Relance', 'delay_days' => 15, 'color' => 'warning',
                'subject' => 'Relance : facture(s) impayée(s)',
                'content' => '<p>Bonjour,</p>'
                    . '<p>Malgré notre précédent rappel, les factures suivantes restent impayées ({retard} jours de retard pour la plus ancienne) :</p>'
                    . '<div>{factures}</div>'
                    . '<p>Montant restant dû : <strong>{total_du}</strong>.</p>'
                    . '<p>Nous vous remercions de bien vouloir procéder à leur règlement dans les meilleurs délais, ou de nous indiquer toute difficulté.</p>'
                    . '<p>Cordialement,<br>{entreprise}</p>',
            ],
            [
                'level' => 3, 'label' => 'Mise en demeure', 'delay_days' => 30, 'color' => 'danger',
                'subject' => 'Mise en demeure de payer',
                'content' => '<p>Madame, Monsieur,</p>'
                    . '<p>Nos relances étant restées sans effet, nous vous mettons en demeure de régler sous huit jours les factures suivantes :</p>'
                    . '<div>{factures}</div>'
                    . '<p>Montant restant dû : <strong>{total_du}</strong>.</p>'
                    . '<p>À défaut, des pénalités de retard et l\'indemnité forfaitaire pour frais de recouvrement de 40 € par facture seront appliquées, et nous nous réservons le droit d\'engager une procédure de recouvrement.</p>'
                    . '<p>{entreprise}</p>',
            ],
        ];

        $now = now();

        foreach ($levels as $level) {
            $templateId = DB::table('email_templates')->insertGetId([
                'document_type' => 'invoice-reminder',
                'subject'       => $level['subject'],
                'content'       => $level['content'],
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            DB::table('invoice_reminder_levels')->insert([
                'level'             => $level['level'],
                'label'             => $level['label'],
                'delay_days'        => $level['delay_days'],
                'email_template_id' => $templateId,
                'color'             => $level['color'],
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Les niveaux et modèles ont pu être modifiés : on ne les supprime pas.
        // La migration précédente supprime de toute façon la table des niveaux.
    }
};
