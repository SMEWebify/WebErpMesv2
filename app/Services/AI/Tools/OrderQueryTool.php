<?php

namespace App\Services\AI\Tools;

use App\Models\Workflow\Orders;

/**
 * Outil de recherche de commandes.
 * Exposé à Claude via le tool use.
 */
class OrderQueryTool
{
    /** Statuts commandes */
    private const STATUTS = [
        1 => 'En cours',
        2 => 'Terminée',
        3 => 'Annulée',
    ];

    /**
     * Recherche des commandes par nom de client, code commande, ou statut.
     *
     * @param  string|null  $client   Nom (ou partie) du client
     * @param  string|null  $code     Code commande (ex: CMD-2024-0042)
     * @param  int|null     $statu    1=En cours, 2=Terminée, 3=Annulée
     * @param  int          $limit    Nombre max de résultats
     */
    public function search(
        ?string $client = null,
        ?string $code   = null,
        ?int    $statu  = null,
        int     $limit  = 10,
    ): array {
        $query = Orders::with(['companie:id,label,last_name', 'contact:id,first_name,name'])
            ->select(['id', 'code', 'label', 'statu', 'companies_id', 'companies_contacts_id', 'created_at', 'validity_date'])
            ->when($client, fn ($q) => $q->whereHas('companie', fn ($q2) =>
                $q2->where('label', 'like', "%{$client}%")
            ))
            ->when($code, fn ($q) => $q->where('code', 'like', "%{$code}%"))
            ->when($statu !== null, fn ($q) => $q->where('statu', $statu))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        if ($query->isEmpty()) {
            return ['found' => false, 'message' => 'Aucune commande trouvée.'];
        }

        return [
            'found'   => true,
            'count'   => $query->count(),
            'orders'  => $query->map(fn ($o) => [
                'id'          => $o->id,
                'code'        => $o->code,
                'label'       => $o->label,
                'statut'      => self::STATUTS[$o->statu] ?? "Statut {$o->statu}",
                'client'      => $o->companie?->label,
                'contact'     => $o->contact
                    ? trim($o->contact->first_name . ' ' . $o->contact->name)
                    : null,
                'created_at'  => $o->created_at?->format('d/m/Y'),
                'validity_date' => $o->validity_date
                    ? \Carbon\Carbon::parse($o->validity_date)->format('d/m/Y')
                    : null,
                'url'         => url("/workflow/orders/{$o->id}"),
            ])->toArray(),
        ];
    }

    /** Définition JSON Schema pour Claude */
    public static function definition(): array
    {
        return [
            'name'        => 'search_orders',
            'description' => 'Recherche des commandes clients dans l\'ERP. Permet de retrouver une commande par nom de client, code ou statut. Renvoie le code, le statut, le client et le lien vers la commande.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'client' => [
                        'type'        => 'string',
                        'description' => 'Nom (ou partie du nom) du client. Ex: "Dupont", "ABC".',
                    ],
                    'code' => [
                        'type'        => 'string',
                        'description' => 'Code de la commande. Ex: "CMD-2024-0042".',
                    ],
                    'statu' => [
                        'type'        => 'integer',
                        'description' => 'Statut : 1=En cours, 2=Terminée, 3=Annulée. Omettez pour tous les statuts.',
                        'enum'        => [1, 2, 3],
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
