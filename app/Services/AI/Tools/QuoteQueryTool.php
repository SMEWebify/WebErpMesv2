<?php

namespace App\Services\AI\Tools;

use App\Models\Workflow\Quotes;

/**
 * Outil de recherche de devis.
 * Exposé à Claude via le tool use.
 */
class QuoteQueryTool
{
    /** Statuts devis */
    private const STATUTS = [
        1 => 'En cours',
        2 => 'Envoyé',
        3 => 'Accepté',
        4 => 'Refusé',
        5 => 'Annulé',
    ];

    /**
     * Recherche des devis par client, code ou statut.
     *
     * @param  string|null  $client  Nom (ou partie) du client
     * @param  string|null  $code    Code devis
     * @param  int|null     $statu   Statut (1-5)
     * @param  int          $limit   Nombre max de résultats
     */
    public function search(
        ?string $client = null,
        ?string $code   = null,
        ?int    $statu  = null,
        int     $limit  = 10,
    ): array {
        $query = Quotes::with(['companie:id,label,last_name', 'contact:id,first_name,name'])
            ->select(['id', 'code', 'label', 'statu', 'companies_id', 'companies_contacts_id', 'created_at', 'validity_date'])
            ->when($client, fn ($q) => $q->whereHas('companie', fn ($q2) =>
                $q2->where('label', 'like', "%{$client}%")
            ))
            ->when($code,  fn ($q) => $q->where('code', 'like', "%{$code}%"))
            ->when($statu !== null, fn ($q) => $q->where('statu', $statu))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        if ($query->isEmpty()) {
            return ['found' => false, 'message' => 'Aucun devis trouvé.'];
        }

        return [
            'found'  => true,
            'count'  => $query->count(),
            'quotes' => $query->map(fn ($q) => [
                'id'             => $q->id,
                'code'           => $q->code,
                'label'          => $q->label,
                'statut'         => self::STATUTS[$q->statu] ?? "Statut {$q->statu}",
                'client'         => $q->companie?->label,
                'contact'        => $q->contact
                    ? trim($q->contact->first_name . ' ' . $q->contact->name)
                    : null,
                'created_at'     => $q->created_at?->format('d/m/Y'),
                'validity_date'  => $q->validity_date
                    ? \Carbon\Carbon::parse($q->validity_date)->format('d/m/Y')
                    : null,
                'url'            => url("/workflow/quotes/{$q->id}"),
            ])->toArray(),
        ];
    }

    /** Définition JSON Schema pour Claude */
    public static function definition(): array
    {
        return [
            'name'        => 'search_quotes',
            'description' => 'Recherche des devis clients dans l\'ERP par nom de client, code ou statut.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'client' => [
                        'type'        => 'string',
                        'description' => 'Nom (ou partie du nom) du client.',
                    ],
                    'code' => [
                        'type'        => 'string',
                        'description' => 'Code du devis. Ex: "DEV-2024-0012".',
                    ],
                    'statu' => [
                        'type'        => 'integer',
                        'description' => 'Statut : 1=En cours, 2=Envoyé, 3=Accepté, 4=Refusé, 5=Annulé.',
                        'enum'        => [1, 2, 3, 4, 5],
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'description' => 'Nombre maximum de résultats (défaut: 10).',
                        'default'     => 10,
                    ],
                ],
                'required' => [],
            ],
        ];
    }
}
