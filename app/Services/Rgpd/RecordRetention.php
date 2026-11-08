<?php

namespace App\Services\Rgpd;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conservation des enregistrements (ISO 19443) : quelles personnes et quels tiers
 * soft-deleted ne doivent jamais être supprimés définitivement par rgpd:purge.
 *
 * Deux raisons de conserver :
 * - un visa (revue, BPF, libération, contrôle, validation, signature) : les colonnes
 *   sont en nullOnDelete, une suppression ferait perdre son auteur au visa ;
 * - un enregistrement métier dont la clé est en onDelete('cascade') (devis, commande,
 *   facture, NC…) : la suppression définitive effacerait l'enregistrement lui-même.
 *
 * Tables et colonnes absentes de la base (table supprimée, colonne retirée) sont
 * ignorées. Un nouveau visa doit être ajouté à USER_VISA_COLUMNS.
 */
class RecordRetention
{
    /** Colonnes de visa, signature ou validation qui citent un utilisateur. */
    public const USER_VISA_COLUMNS = [
        'quotes'                           => ['reviewed_by', 'change_requested_by'],
        'orders'                           => ['reviewed_by', 'change_requested_by'],
        'order_amendments'                 => ['requested_by', 'reviewed_by'],
        'order_lines'                      => ['released_for_production_by', 'dossier_validated_by'],
        'order_line_delay_histories'       => ['user_id'],
        'tasks'                            => ['released_by'],
        'purchase_receipts'                => ['reception_control_user_id'],
        'purchase_receipt_lines'           => ['inspected_by', 'document_check_by'],
        'quality_derogations'              => ['validated_by'],
        'quality_derogation_signatures'    => ['sent_by'],
        'credit_notes'                     => ['validated_by'],
        'inventories'                      => ['validated_by'],
        'inventory_details'                => ['counted_by'],
        'inspection_plans'                 => ['approved_by'],
        'inspection_projects'              => ['closed_by', 'verified_by', 'approved_by'],
        'inspection_measures'              => ['measured_by'],
        'inspection_documents'             => ['approved_by'],
        'affair_risks'                     => ['closed_by'],
        'returns'                          => ['diagnosed_by'],
        'locked_periods'                   => ['locked_by'],
        'credit_limit_overrides'           => ['user_id'],
        'supplier_qualification_overrides' => ['user_id'],
        'required_operations_overrides'    => ['user_id'],
        'qonto_sync_reviews'               => ['resolved_by'],
    ];

    /** Auteurs d'enregistrements métier ou qualité (souvent en suppression en cascade). */
    public const USER_RECORD_COLUMNS = [
        'quotes'                      => ['user_id'],
        'orders'                      => ['user_id'],
        'order_confirmations'         => ['user_id'],
        'order_amendments'            => ['created_by'],
        'deliverys'                   => ['user_id'],
        'packagings'                  => ['user_id'],
        'invoices'                    => ['user_id'],
        'invoice_payments'            => ['user_id'],
        'credit_notes'                => ['user_id'],
        'purchases'                   => ['user_id'],
        'purchases_quotations'        => ['user_id'],
        'purchase_receipts'           => ['user_id'],
        'purchase_invoices'           => ['user_id'],
        'leads'                       => ['user_id'],
        'opportunities'               => ['user_id'],
        'affair_risks'                => ['created_by', 'owner_id'],
        'quality_non_conformities'    => ['user_id'],
        'quality_actions'             => ['user_id'],
        'quality_derogations'         => ['user_id'],
        'quality_amdecs'              => ['user_id'],
        'inspection_plans'            => ['created_by'],
        'inspection_projects'         => ['created_by'],
        'inspection_measure_sessions' => ['created_by'],
        'audit_plans'                 => ['created_by'],
        'audit_executions'            => ['created_by'],
        'audit_findings'              => ['created_by'],
        'osh_incidents'               => ['user_id'],
        'task_activities'             => ['user_id'],
        'stock_moves'                 => ['user_id'],
        'files'                       => ['user_id'],
    ];

    /** Documents, NC et dérogations qui citent une société. */
    public const COMPANY_COLUMNS = [
        'quotes'                           => ['companies_id'],
        'orders'                           => ['companies_id'],
        'order_confirmations'              => ['companies_id'],
        'deliverys'                        => ['companies_id'],
        'invoices'                         => ['companies_id'],
        'credit_notes'                     => ['companies_id'],
        'purchases'                        => ['companies_id'],
        'purchases_quotations'             => ['companies_id'],
        'purchase_receipts'                => ['companies_id'],
        'purchase_invoices'                => ['companies_id'],
        'leads'                            => ['companies_id'],
        'opportunities'                    => ['companies_id'],
        'quality_non_conformities'         => ['companie_id'],
        'quality_derogations'              => ['companies_id'],
        'inspection_projects'              => ['companies_id'],
        'batches'                          => ['companies_id'],
        'serial_numbers'                   => ['companies_id'],
        'supplier_ratings'                 => ['companies_id'],
        'order_ratings'                    => ['companies_id'],
        'supplier_qualification_overrides' => ['companies_id'],
        'credit_limit_overrides'           => ['companies_id'],
    ];

    /** Documents et dérogations qui citent un contact. */
    public const CONTACT_COLUMNS = [
        'quotes'               => ['companies_contacts_id'],
        'orders'               => ['companies_contacts_id'],
        'order_confirmations'  => ['companies_contacts_id'],
        'deliverys'            => ['companies_contacts_id'],
        'invoices'             => ['companies_contacts_id'],
        'credit_notes'         => ['companies_contacts_id'],
        'purchases'            => ['companies_contacts_id'],
        'purchases_quotations' => ['companies_contacts_id'],
        'leads'                => ['companies_contacts_id'],
        'opportunities'        => ['companies_contacts_id'],
        'quality_derogations'  => ['companies_contacts_id'],
    ];

    /** Documents qui citent une adresse. */
    public const ADDRESS_COLUMNS = [
        'quotes'               => ['companies_addresses_id'],
        'orders'               => ['companies_addresses_id'],
        'order_confirmations'  => ['companies_addresses_id'],
        'deliverys'            => ['companies_addresses_id'],
        'invoices'             => ['companies_addresses_id'],
        'credit_notes'         => ['companies_addresses_id'],
        'purchases'            => ['companies_addresses_id'],
        'purchases_quotations' => ['companies_addresses_id'],
        'leads'                => ['companies_addresses_id'],
        'opportunities'        => ['companies_addresses_id'],
    ];

    /** @var array<string, bool> */
    private array $columnExists = [];

    /**
     * Utilisateurs à conserver parmi $ids : visa, enregistrement métier ou causer
     * d'une activité (tous journaux).
     *
     * @param  int[]  $ids
     * @return int[]
     */
    public function retainedUserIds(array $ids): array
    {
        $kept = $this->referencedIds(self::USER_VISA_COLUMNS, $ids);
        $kept = array_merge($kept, $this->referencedIds(self::USER_RECORD_COLUMNS, array_diff($ids, $kept)));

        $pending = array_values(array_diff($ids, $kept));
        if ($pending !== [] && $this->hasColumn($table = config('activitylog.table_name', 'activity_log'), 'causer_id')) {
            foreach (array_chunk($pending, 500) as $chunk) {
                $kept = array_merge($kept, DB::table($table)
                    ->where('causer_type', (new User())->getMorphClass())
                    ->whereIn('causer_id', $chunk)
                    ->distinct()
                    ->pluck('causer_id')
                    ->map(fn ($id) => (int) $id)
                    ->all());
            }
        }

        return $this->normalize($kept);
    }

    /** @param int[] $ids @return int[] */
    public function retainedContactIds(array $ids): array
    {
        return $this->referencedIds(self::CONTACT_COLUMNS, $ids);
    }

    /** @param int[] $ids @return int[] */
    public function retainedAddressIds(array $ids): array
    {
        return $this->referencedIds(self::ADDRESS_COLUMNS, $ids);
    }

    /**
     * Sociétés à conserver : citées directement, ou dont un contact / une adresse
     * (supprimé ou non) est cité — la suppression de la société emporterait ses
     * contacts et adresses, et avec eux les documents, en cascade.
     *
     * @param  int[]  $ids
     * @return int[]
     */
    public function retainedCompanyIds(array $ids): array
    {
        $kept = $this->referencedIds(self::COMPANY_COLUMNS, $ids);
        $pending = array_values(array_diff($ids, $kept));

        foreach ([
            'companies_contacts'  => self::CONTACT_COLUMNS,
            'companies_addresses' => self::ADDRESS_COLUMNS,
        ] as $childTable => $columns) {
            if ($pending === []) {
                break;
            }

            $children = [];
            foreach (array_chunk($pending, 500) as $chunk) {
                foreach (DB::table($childTable)->whereIn('companies_id', $chunk)->get(['id', 'companies_id']) as $row) {
                    $children[(int) $row->id] = (int) $row->companies_id;
                }
            }

            foreach ($this->referencedIds($columns, array_keys($children)) as $childId) {
                $kept[] = $children[$childId];
            }
            $pending = array_values(array_diff($pending, $kept));
        }

        return $this->normalize($kept);
    }

    /**
     * Identifiants de $ids cités par au moins une des colonnes.
     *
     * @param  array<string, string[]>  $columns
     * @param  int[]  $ids
     * @return int[]
     */
    public function referencedIds(array $columns, array $ids): array
    {
        $pending = $this->normalize($ids);
        $found = [];

        foreach ($columns as $table => $tableColumns) {
            foreach ($tableColumns as $column) {
                if ($pending === []) {
                    return $this->normalize($found);
                }
                if (! $this->hasColumn($table, $column)) {
                    continue;
                }

                foreach (array_chunk($pending, 500) as $chunk) {
                    $found = array_merge($found, DB::table($table)
                        ->whereIn($column, $chunk)
                        ->distinct()
                        ->pluck($column)
                        ->map(fn ($id) => (int) $id)
                        ->all());
                }
                $pending = array_values(array_diff($pending, $found));
            }
        }

        return $this->normalize($found);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->columnExists["{$table}.{$column}"]
            ??= Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    /** @param array<int|string> $ids @return int[] */
    private function normalize(array $ids): array
    {
        return array_values(array_unique(array_map('intval', $ids)));
    }
}
