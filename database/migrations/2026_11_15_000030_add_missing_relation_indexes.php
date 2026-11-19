<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index des colonnes de liaison et de recherche restées sans index (audit de nov. 2026).
 *
 * Index simples seulement : aucune contrainte d'unicité ni clé étrangère, donc aucune
 * donnée existante ne peut faire échouer la migration. Chaque index est sauté si la table
 * ou une colonne manque, ou si un index porte déjà exactement ces colonnes.
 *
 * Principaux usages :
 * - purchase_lines, purchase_quotation_lines, purchase_invoice_lines : fiches achat,
 *   synthèse d'affaire (AffairScope::purchaseLinesQuery), rapprochement des factures ;
 * - stock_moves : lots consommés d'une tâche, fiche suiveuse, recalcul des réservations ;
 * - companies_contacts / addresses : contact et adresse par défaut d'une société ;
 * - *_line_details : détail de chaque ligne de devis / commande (hasOne) ;
 * - orders.quotes_id, invoices / deliverys.order_id, order_lines.quote_lines_id : affaire ;
 * - uuid : liens publics et portail client (routes sans authentification) ;
 * - files.name : anciennes URLs servies par LegacyFileController ;
 * - task_activities, activity_log : présence / paie, journal des consultations et sa purge.
 */
return new class extends Migration
{
    private const INDEXES = [
        'purchase_lines'           => [['purchases_id'], ['tasks_id']],
        'purchase_quotation_lines' => [['purchases_quotation_id'], ['tasks_id']],
        'purchase_invoice_lines'   => [['purchase_invoice_id'], ['purchase_receipt_line_id'], ['purchase_line_id']],
        'stock_moves'              => [['task_id', 'typ_move'], ['order_line_id'], ['purchase_receipt_line_id'], ['user_id']],
        'stock_location_products'  => [['stock_locations_id', 'products_id']],
        'companies_contacts'       => [['companies_id', 'default']],
        'companies_addresses'      => [['companies_id', 'default']],
        'companies'                => [['uuid'], ['code']],
        'quote_line_details'       => [['quote_lines_id']],
        'order_line_details'       => [['order_lines_id']],
        'quotes'                   => [['uuid']],
        'quote_lines'              => [['product_id']],
        'orders'                   => [['quotes_id'], ['uuid']],
        'order_lines'              => [['quote_lines_id'], ['product_id'], ['delivery_date']],
        'deliverys'                => [['order_id'], ['uuid']],
        'invoices'                 => [['order_id'], ['uuid']],
        'invoice_lines'            => [['delivery_line_id']],
        'pdp_invoice_submissions'  => [['invoice_id']],
        'opportunities'            => [['uuid']],
        'tasks'                    => [['sub_assembly_id'], ['end_date']],
        'sub_assemblies'           => [['quote_lines_id'], ['order_lines_id'], ['products_id'], ['sub_assembly_id']],
        'task_activities'          => [['user_id', 'timestamp'], ['timestamp']],
        'quality_non_conformities' => [['order_lines_id'], ['task_id']],
        'quality_actions'          => [['quality_non_conformitie_id']],
        'quality_derogations'      => [['quality_non_conformitie_id']],
        'files'                    => [['name']],
        'chats'                    => [['related_type', 'related_id']],
        'activity_log'             => [['log_name', 'created_at']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($columns));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                $name = strtolower($table . '_' . implode('_', $columns) . '_index');

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
