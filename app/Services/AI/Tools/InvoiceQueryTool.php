<?php

namespace App\Services\AI\Tools;

use App\Models\Workflow\Invoices;
use Carbon\Carbon;

/**
 * Outil de consultation des factures.
 * Exposé à Claude via le tool use.
 */
class InvoiceQueryTool
{
    /** Statuts factures */
    private const STATUTS = [
        1 => 'En cours',
        2 => 'Envoyée',
        3 => 'En attente',
        4 => 'Impayée',
        5 => 'Payée',
    ];

    /** Statuts comptables */
    private const ACCOUNTING_STATUTS = [
        1 => 'Non exportée',
        2 => 'Exportée',
    ];

    /**
     * Recherche des factures impayées ou par client.
     *
     * @param  string|null  $client       Nom (ou partie) du client
     * @param  bool         $unpaid_only  Uniquement les impayées
     * @param  int          $limit        Nombre max de résultats
     */
    public function search(
        ?string $client      = null,
        bool    $unpaid_only = false,
        int     $limit       = 15,
    ): array {
        $query = Invoices::with(['companie:id,label,last_name'])
            ->select(['id', 'code', 'label', 'statu', 'accounting_status', 'companies_id', 'due_date', 'created_at'])
            ->when($client, fn ($q) => $q->whereHas('companie', fn ($q2) =>
                $q2->where('label', 'like', "%{$client}%")
            ))
            ->when($unpaid_only, fn ($q) => $q->whereIn('statu', [2, 3, 4])) // Envoyée, En attente, Impayée
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        if ($query->isEmpty()) {
            $msg = $unpaid_only
                ? ($client ? "Aucune facture impayée pour {$client}." : 'Aucune facture impayée.')
                : 'Aucune facture trouvée.';
            return ['found' => false, 'message' => $msg];
        }

        $now = Carbon::now();

        return [
            'found'    => true,
            'count'    => $query->count(),
            'invoices' => $query->map(fn ($inv) => [
                'id'                => $inv->id,
                'code'              => $inv->code,
                'label'             => $inv->label,
                'statut'            => self::STATUTS[$inv->statu] ?? "Statut {$inv->statu}",
                'statut_comptable'  => self::ACCOUNTING_STATUTS[$inv->accounting_status] ?? null,
                'client'            => $inv->companie?->label,
                'date_echeance'     => $inv->due_date
                    ? Carbon::parse($inv->due_date)->format('d/m/Y')
                    : null,
                'en_retard'         => $inv->due_date
                    ? Carbon::parse($inv->due_date)->isPast() && $inv->statu < 5
                    : false,
                'created_at'        => $inv->created_at?->format('d/m/Y'),
                'url'               => url("/workflow/invoices/{$inv->id}"),
            ])->toArray(),
        ];
    }

    /** Définition JSON Schema pour Claude */
    public static function definition(): array
    {
        return [
            'name'        => 'search_invoices',
            'description' => 'Recherche des factures clients dans l\'ERP. Peut filtrer par client et/ou retourner uniquement les factures impayées ou en retard.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'client' => [
                        'type'        => 'string',
                        'description' => 'Nom (ou partie du nom) du client. Ex: "Dupont".',
                    ],
                    'unpaid_only' => [
                        'type'        => 'boolean',
                        'description' => 'Si true, retourne uniquement les factures non payées (envoyées, en attente, impayées).',
                        'default'     => false,
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'description' => 'Nombre maximum de résultats (défaut: 15).',
                        'default'     => 15,
                    ],
                ],
                'required' => [],
            ],
        ];
    }
}
