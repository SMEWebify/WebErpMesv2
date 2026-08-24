<?php

namespace App\Services\AI\Tools;

use App\Models\Companies\Companies;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\Purchases;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Outil universel de requête sur les tables ERP.
 *
 * Claude décrit ce qu'il veut en JSON (table + filtres + agrégat), le service
 * traduit en Eloquent. Aucun SQL n'est jamais reçu depuis l'IA — on ne fait
 * que composer un query builder à partir d'une whitelist stricte de tables
 * et de colonnes.
 *
 * Sécurité :
 *  - Tables limitées à la whitelist SCHEMA ci-dessous
 *  - Colonnes limitées aux `columns` autorisées par table
 *  - Colonnes sensibles bannies globalement (password, tokens, deleted_at…)
 *  - `limit` plafonné, agrégats et group_by contrôlés
 *  - Lecture seule (aucun update/insert/delete possible)
 */
class UniversalQueryTool
{
    /** Nombre max de lignes retournées (garde-fou même si Claude demande plus). */
    private const HARD_LIMIT = 500;

    /** Limite par défaut si Claude n'en donne pas. */
    private const DEFAULT_LIMIT = 50;

    /** Colonnes bannies partout, quelle que soit la table. */
    private const FORBIDDEN_COLUMNS = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'api_token', 'deleted_at',
    ];

    /** Opérateurs where autorisés. */
    private const ALLOWED_OPERATORS = [
        '=', '!=', '<>', '<', '<=', '>', '>=', 'like', 'not like',
        'in', 'not in', 'is null', 'is not null', 'between',
    ];

    /** Agrégats autorisés. */
    private const ALLOWED_AGGREGATES = ['count', 'sum', 'avg', 'min', 'max'];

    /** Fonctions de groupement temporel autorisées sur une colonne date. */
    private const TIME_GROUPINGS = ['year', 'month', 'day', 'yearmonth', 'week'];

    /**
     * Schéma exposé à Claude. Chaque entrée décrit ce que l'IA a le droit de voir/utiliser.
     * - `model`       : classe Eloquent (source de vérité)
     * - `columns`     : liste blanche des colonnes lisibles/filtrables/tri
     * - `date_column` : colonne date principale (aide pour "ce mois-ci", "en retard")
     * - `label`       : description courte pour Claude
     */
    private const SCHEMA = [
        'orders' => [
            'model'       => Orders::class,
            'date_column' => 'created_at',
            'label'       => 'Commandes clients (en-tête). Statut 1=Ouverte, 2=En cours, 3=Livrée, 4=Partiellement livrée, 5=Stoppée, 6=Annulée.',
            'columns'     => [
                'id', 'code', 'label', 'customer_reference', 'companies_id',
                'companies_contacts_id', 'validity_date', 'statu', 'user_id',
                'type', 'quotes_id', 'created_at', 'updated_at',
            ],
            'relations' => [
                'companie'    => ['columns' => ['id', 'label', 'siren']],
                'OrderLines'  => ['columns' => ['id', 'orders_id', 'code', 'label', 'qty', 'delivered_qty', 'selling_price', 'discount', 'delivery_date', 'tasks_status', 'delivery_status', 'invoice_status']],
            ],
        ],

        'order_lines' => [
            'model'       => OrderLines::class,
            'date_column' => 'created_at',
            'label'       => 'Lignes de commande. Chaque ligne appartient à une commande via orders_id. tasks_status : 1=Aucune, 2=Créée, 3=En cours, 4=Terminée. delivery_status : 1=Non livrée, 2=Partielle, 3=Livrée, 4=Sans BL. invoice_status : 1=Non facturée, 2=Partielle, 3=Facturée. Chiffre d\'affaires ligne = qty × selling_price × (1 - discount/100).',
            'columns'     => [
                'id', 'orders_id', 'ordre', 'code', 'product_id', 'label',
                'qty', 'delivered_qty', 'delivered_remaining_qty',
                'invoiced_qty', 'invoiced_remaining_qty',
                'selling_price', 'discount', 'internal_delay', 'delivery_date',
                'tasks_status', 'delivery_status', 'invoice_status',
                'created_at', 'updated_at',
            ],
        ],

        'quotes' => [
            'model'       => Quotes::class,
            'date_column' => 'created_at',
            'label'       => 'Devis clients (en-tête). Statut 1=Ouvert, 2=Envoyé, 3=Gagné, 4=Perdu, 5=Fermé, 6=Obsolète.',
            'columns'     => [
                'id', 'code', 'label', 'customer_reference', 'companies_id',
                'companies_contacts_id', 'validity_date', 'statu', 'user_id',
                'opportunities_id', 'created_at', 'updated_at',
            ],
            'relations' => [
                'companie'   => ['columns' => ['id', 'label', 'siren']],
                'QuoteLines' => ['columns' => ['id', 'quotes_id', 'code', 'label', 'qty', 'selling_price', 'discount', 'statu']],
            ],
        ],

        'quote_lines' => [
            'model'       => QuoteLines::class,
            'date_column' => 'created_at',
            'label'       => 'Lignes de devis. quotes_id = clé du devis parent. Montant ligne = qty × selling_price × (1 - discount/100).',
            'columns'     => [
                'id', 'quotes_id', 'ordre', 'code', 'product_id', 'label',
                'qty', 'selling_price', 'discount', 'delivery_date', 'statu',
                'created_at', 'updated_at',
            ],
        ],

        'invoices' => [
            'model'       => Invoices::class,
            'date_column' => 'created_at',
            'label'       => 'Factures clients. Statut 1=En cours, 2=Envoyée, 3=En attente, 4=Impayée, 5=Payée. invoice_type : 1=Facture, 2=Avoir, 3=Proforma, 4=Acompte. Une facture est "en retard" si due_date < aujourd\'hui ET statu != 5.',
            'columns'     => [
                'id', 'code', 'label', 'companies_id', 'companies_contacts_id',
                'statu', 'invoice_type', 'accounting_status', 'user_id',
                'order_id', 'due_date', 'payment_date', 'export_date',
                'customer_reference', 'created_at', 'updated_at',
            ],
            'relations' => [
                'companie'     => ['columns' => ['id', 'label', 'siren']],
                'invoiceLines' => ['columns' => ['id', 'invoices_id', 'order_line_id', 'qty']],
            ],
        ],

        'invoice_lines' => [
            'model'       => InvoiceLines::class,
            'date_column' => 'created_at',
            'label'       => 'Lignes de facture. Elles pointent vers order_line_id — le montant réel se lit sur la ligne de commande correspondante (qty × selling_price × (1 - discount/100)).',
            'columns'     => [
                'id', 'invoices_id', 'order_line_id', 'delivery_line_id',
                'ordre', 'qty', 'invoice_status', 'created_at', 'updated_at',
            ],
        ],

        'companies' => [
            'model'       => Companies::class,
            'date_column' => 'created_at',
            'label'       => 'Sociétés (clients et fournisseurs). statu_customer : 1=Inactif, 2=Actif, 3=Prospect. statu_supplier : 1=Inactif, 2=Actif. Un enregistrement peut être client, fournisseur ou les deux.',
            'columns'     => [
                'id', 'code', 'label', 'siren', 'naf_code', 'intra_community_vat',
                'statu_customer', 'statu_supplier', 'discount', 'active',
                'user_id', 'created_at', 'updated_at',
            ],
        ],

        'products' => [
            'model'       => Products::class,
            'date_column' => 'created_at',
            'label'       => 'Fiches produit et matières. purchased/sold = booléens (1=oui). purchased_price = prix d\'achat unitaire, selling_price = prix de vente unitaire. Poids en kg.',
            'columns'     => [
                'id', 'code', 'label', 'ind', 'methods_services_id', 'methods_families_id',
                'purchased', 'purchased_price', 'sold', 'selling_price',
                'material', 'thickness', 'weight',
                'x_size', 'y_size', 'z_size', 'diameter',
                'qty_eco_min', 'qty_eco_max',
                'created_at', 'updated_at',
            ],
        ],

        'stock_location_products' => [
            'model'       => StockLocationProducts::class,
            'date_column' => 'created_at',
            'label'       => 'Stock disponible par produit et par emplacement. stock_qty = quantité en stock, mini_qty = seuil de réappro. Un produit est "en rupture" si stock_qty <= 0, "sous mini" si stock_qty < mini_qty.',
            'columns'     => [
                'id', 'code', 'stock_locations_id', 'products_id',
                'stock_qty', 'mini_qty', 'reserve_qty', 'end_date', 'addressing',
                'created_at', 'updated_at',
            ],
        ],

        'deliverys' => [
            'model'       => Deliverys::class,
            'date_column' => 'created_at',
            'label'       => 'Bons de livraison. statu : 1=En cours, 2=Envoyé. invoice_status : 1=Facturable, 2=Non facturable, 3=Partiellement facturé, 4=Facturé.',
            'columns'     => [
                'id', 'code', 'label', 'companies_id', 'companies_contacts_id',
                'statu', 'invoice_status', 'user_id', 'order_id',
                'created_at', 'updated_at',
            ],
        ],

        'purchases' => [
            'model'       => Purchases::class,
            'date_column' => 'created_at',
            'label'       => 'Commandes d\'achat (fournisseurs). Statut 1=En cours, 2=Commandée, 3=Partiellement reçue, 4=Reçue, 5=Annulée. companies_id pointe sur le fournisseur.',
            'columns'     => [
                'id', 'code', 'label', 'companies_id', 'companies_contacts_id',
                'statu', 'user_id', 'created_at', 'updated_at',
            ],
        ],
    ];

    /**
     * Exécute une requête décrite en DSL JSON par Claude.
     *
     * @param  array  $input  Structure décrite dans self::definition().
     */
    public function run(array $input): array
    {
        $table = $input['table'] ?? null;

        if (! is_string($table) || ! isset(self::SCHEMA[$table])) {
            return [
                'error' => 'Table inconnue ou manquante.',
                'available_tables' => array_keys(self::SCHEMA),
            ];
        }

        $schema = self::SCHEMA[$table];

        try {
            /** @var Builder $query */
            $query = $schema['model']::query();

            $this->applyWheres($query, $input['where'] ?? [], $schema);
            $this->applyRelativeDate($query, $input, $schema);

            // Mode agrégat (count / sum / avg / min / max, avec group_by optionnel)
            if (! empty($input['aggregate'])) {
                return $this->runAggregate($query, $input, $schema, $table);
            }

            // Mode liste
            return $this->runList($query, $input, $schema, $table);

        } catch (Throwable $e) {
            Log::warning('[AI:UniversalQueryTool] Erreur d\'exécution', [
                'table' => $table,
                'input' => $input,
                'error' => $e->getMessage(),
            ]);

            return [
                'error'   => 'Requête invalide : ' . $e->getMessage(),
                'hint'    => 'Vérifie les noms de colonnes et les opérateurs. Utilise search_orders/search_invoices pour les cas simples.',
            ];
        }
    }

    /**
     * Applique les filtres WHERE en whitelistant colonnes et opérateurs.
     */
    private function applyWheres(Builder $query, array $wheres, array $schema): void
    {
        foreach ($wheres as $clause) {
            if (! is_array($clause) || count($clause) < 2) {
                throw new \InvalidArgumentException('Chaque clause where doit être [colonne, opérateur, valeur].');
            }

            $column   = $clause[0];
            $operator = strtolower((string) $clause[1]);
            $value    = $clause[2] ?? null;

            $this->assertColumnAllowed($column, $schema);

            if (! in_array($operator, self::ALLOWED_OPERATORS, true)) {
                throw new \InvalidArgumentException("Opérateur non autorisé : {$operator}");
            }

            match (true) {
                $operator === 'is null'      => $query->whereNull($column),
                $operator === 'is not null'  => $query->whereNotNull($column),
                $operator === 'in'           => $query->whereIn($column, (array) $value),
                $operator === 'not in'       => $query->whereNotIn($column, (array) $value),
                $operator === 'between'      => $query->whereBetween($column, (array) $value),
                default                      => $query->where($column, $operator, $value),
            };
        }
    }

    /**
     * Raccourci "date_range" pour filtres temporels naturels sur la colonne date par défaut :
     *   - "today", "yesterday"
     *   - "this_week", "last_week"
     *   - "this_month", "last_month"
     *   - "this_quarter", "last_quarter"
     *   - "this_year", "last_year"
     *   - "last_7_days", "last_30_days", "last_90_days"
     * Ou bien un objet { from: 'YYYY-MM-DD', to: 'YYYY-MM-DD' }.
     */
    private function applyRelativeDate(Builder $query, array $input, array $schema): void
    {
        $range = $input['date_range'] ?? null;
        if (empty($range)) return;

        $column = $input['date_column'] ?? $schema['date_column'] ?? 'created_at';
        $this->assertColumnAllowed($column, $schema);

        if (is_array($range)) {
            $from = $range['from'] ?? null;
            $to   = $range['to']   ?? null;
            if ($from) $query->where($column, '>=', $from);
            if ($to)   $query->where($column, '<=', $to . ' 23:59:59');
            return;
        }

        $now = now();
        [$from, $to] = match ((string) $range) {
            'today'         => [$now->copy()->startOfDay(),       $now->copy()->endOfDay()],
            'yesterday'     => [$now->copy()->subDay()->startOfDay(),   $now->copy()->subDay()->endOfDay()],
            'this_week'     => [$now->copy()->startOfWeek(),      $now->copy()->endOfWeek()],
            'last_week'     => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month'    => [$now->copy()->startOfMonth(),     $now->copy()->endOfMonth()],
            'last_month'    => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter'  => [$now->copy()->startOfQuarter(),   $now->copy()->endOfQuarter()],
            'last_quarter'  => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'this_year'     => [$now->copy()->startOfYear(),      $now->copy()->endOfYear()],
            'last_year'     => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'last_7_days'   => [$now->copy()->subDays(7),         $now],
            'last_30_days'  => [$now->copy()->subDays(30),        $now],
            'last_90_days'  => [$now->copy()->subDays(90),        $now],
            default         => [null, null],
        };

        if ($from && $to) {
            $query->whereBetween($column, [$from, $to]);
        }
    }

    /**
     * Mode liste : renvoie des lignes avec les colonnes demandées.
     */
    private function runList(Builder $query, array $input, array $schema, string $table): array
    {
        $selectCols = $input['select'] ?? $schema['columns'];
        foreach ($selectCols as $col) {
            $this->assertColumnAllowed($col, $schema);
        }
        $query->select(array_unique(array_merge(['id'], $selectCols)));

        // with(): relations autorisées uniquement
        foreach (($input['with'] ?? []) as $relation) {
            if (! isset($schema['relations'][$relation])) {
                throw new \InvalidArgumentException("Relation non autorisée : {$relation}. Autorisées : " . implode(', ', array_keys($schema['relations'] ?? [])));
            }
            $cols = $schema['relations'][$relation]['columns'];
            $query->with([$relation => fn ($q) => $q->select($cols)]);
        }

        // order_by : "column asc" | "column desc"
        $orderBy = $input['order_by'] ?? null;
        if ($orderBy) {
            [$col, $dir] = $this->parseOrderBy($orderBy);
            $this->assertColumnAllowed($col, $schema);
            $query->orderBy($col, $dir);
        }

        $limit = max(1, min(self::HARD_LIMIT, (int) ($input['limit'] ?? self::DEFAULT_LIMIT)));
        $query->limit($limit);

        $rows = $query->get();

        return [
            'table'   => $table,
            'mode'    => 'list',
            'count'   => $rows->count(),
            'limit'   => $limit,
            'rows'    => $rows->toArray(),
        ];
    }

    /**
     * Mode agrégat : count / sum / avg / min / max, éventuellement group_by.
     */
    private function runAggregate(Builder $query, array $input, array $schema, string $table): array
    {
        $agg = $input['aggregate'];
        if (! is_array($agg)) {
            throw new \InvalidArgumentException('aggregate doit être un objet {function, column?}.');
        }

        $func   = strtolower((string) ($agg['function'] ?? 'count'));
        $column = $agg['column'] ?? '*';

        if (! in_array($func, self::ALLOWED_AGGREGATES, true)) {
            throw new \InvalidArgumentException("Agrégat non autorisé : {$func}");
        }

        if ($func !== 'count' || $column !== '*') {
            $this->assertColumnAllowed($column, $schema);
        }

        $groupBy = $input['group_by'] ?? null;
        $limit   = max(1, min(self::HARD_LIMIT, (int) ($input['limit'] ?? self::DEFAULT_LIMIT)));

        // Sans group_by : un seul scalaire
        if (! $groupBy) {
            $value = match ($func) {
                'count' => $column === '*' ? $query->count() : $query->count($column),
                'sum'   => (float) $query->sum($column),
                'avg'   => (float) $query->avg($column),
                'min'   => $query->min($column),
                'max'   => $query->max($column),
            };

            return [
                'table' => $table,
                'mode'  => 'aggregate',
                'aggregate' => [
                    'function' => $func,
                    'column'   => $column,
                    'value'    => $value,
                ],
            ];
        }

        // Avec group_by : soit une colonne, soit une expression temporelle {time: 'month', column: 'created_at'}
        $groupExpr = $this->resolveGroupBy($groupBy, $schema);

        $selectRaw = $groupExpr['select'] . ', ' . $this->aggregateSelect($func, $column) . ' as agg_value';
        $descending = (bool) ($input['order_agg_desc'] ?? true);

        $rows = $query
            ->selectRaw($selectRaw, $groupExpr['bindings'])
            ->groupByRaw($groupExpr['group'])
            ->orderByRaw($descending ? 'agg_value desc' : 'agg_value asc')
            ->limit($limit)
            ->get();

        return [
            'table' => $table,
            'mode'  => 'aggregate',
            'aggregate' => [
                'function' => $func,
                'column'   => $column,
                'group_by' => $groupBy,
            ],
            'count'  => $rows->count(),
            'groups' => $rows->toArray(),
        ];
    }

    /**
     * Résout un group_by :
     *  - string "companies_id"                        → group direct
     *  - array  {time: 'month', column: 'created_at'} → DATE_FORMAT() sur la colonne
     */
    private function resolveGroupBy(mixed $groupBy, array $schema): array
    {
        if (is_string($groupBy)) {
            $this->assertColumnAllowed($groupBy, $schema);
            $quoted = '`' . str_replace('`', '', $groupBy) . '`';
            return ['select' => "{$quoted} as group_key", 'group' => $quoted, 'bindings' => []];
        }

        if (is_array($groupBy) && isset($groupBy['time'], $groupBy['column'])) {
            $time   = strtolower((string) $groupBy['time']);
            $column = $groupBy['column'];

            if (! in_array($time, self::TIME_GROUPINGS, true)) {
                throw new \InvalidArgumentException("time grouping non autorisé : {$time}");
            }
            $this->assertColumnAllowed($column, $schema);
            $quoted = '`' . str_replace('`', '', $column) . '`';

            $format = match ($time) {
                'year'      => '%Y',
                'month'     => '%m',
                'day'       => '%Y-%m-%d',
                'yearmonth' => '%Y-%m',
                'week'      => '%x-W%v',
            };

            return [
                'select'   => "DATE_FORMAT({$quoted}, '{$format}') as group_key",
                'group'    => "DATE_FORMAT({$quoted}, '{$format}')",
                'bindings' => [],
            ];
        }

        throw new \InvalidArgumentException('group_by doit être une colonne ou {time, column}.');
    }

    /**
     * Rend l'expression SELECT pour l'agrégat (sans binding utilisateur).
     */
    private function aggregateSelect(string $func, string $column): string
    {
        if ($func === 'count') {
            return $column === '*' ? 'COUNT(*)' : 'COUNT(`' . str_replace('`', '', $column) . '`)';
        }
        $quoted = '`' . str_replace('`', '', $column) . '`';
        return strtoupper($func) . "({$quoted})";
    }

    private function parseOrderBy(string $orderBy): array
    {
        $parts = preg_split('/\s+/', trim($orderBy));
        $col   = $parts[0] ?? 'id';
        $dir   = strtolower($parts[1] ?? 'desc');
        if (! in_array($dir, ['asc', 'desc'], true)) $dir = 'desc';
        return [$col, $dir];
    }

    private function assertColumnAllowed(string $column, array $schema): void
    {
        if (in_array(strtolower($column), self::FORBIDDEN_COLUMNS, true)) {
            throw new \InvalidArgumentException("Colonne interdite : {$column}");
        }
        if (! in_array($column, $schema['columns'], true)) {
            throw new \InvalidArgumentException(
                "Colonne inconnue « {$column} ». Autorisées : " . implode(', ', $schema['columns'])
            );
        }
    }

    /**
     * Définition JSON Schema pour Claude.
     *
     * On décrit un DSL JSON — la description doit être assez riche pour que
     * Claude compose des requêtes correctes du premier coup.
     */
    public static function definition(): array
    {
        $tables = array_map(
            fn (string $name, array $s) => "- **{$name}** ({$s['label']})\n  colonnes: " . implode(', ', $s['columns']),
            array_keys(self::SCHEMA),
            self::SCHEMA
        );
        $tableList = implode("\n", $tables);

        return [
            'name'        => 'query',
            'description' => <<<TXT
Interroge la base de données ERP en lecture seule. À utiliser pour toute question qui n'entre pas dans les outils spécialisés (search_orders, check_stock, search_invoices, search_quotes, get_daily_journal), notamment les agrégats, top N, statistiques par période.

Deux modes :
  1. Liste — omets `aggregate`. Renvoie des lignes brutes.
  2. Agrégat — fournis `aggregate: {function, column?}`, éventuellement avec `group_by`. Renvoie des scalaires ou des groupes.

TABLES DISPONIBLES :
{$tableList}

EXEMPLES :

Top 5 clients par CA sur ce trimestre :
```json
{
  "table": "order_lines",
  "aggregate": { "function": "sum", "column": "selling_price" },
  "group_by": "orders_id",
  "date_range": "this_quarter",
  "limit": 5
}
```

Nombre de commandes par mois sur l'année en cours :
```json
{
  "table": "orders",
  "aggregate": { "function": "count" },
  "group_by": { "time": "yearmonth", "column": "created_at" },
  "date_range": "this_year",
  "limit": 12
}
```

Factures impayées échues depuis plus de 30 jours :
```json
{
  "table": "invoices",
  "where": [
    ["statu", "in", [2, 3, 4]],
    ["due_date", "<", "2026-07-25"]
  ],
  "select": ["code", "companies_id", "due_date", "statu"],
  "order_by": "due_date asc",
  "limit": 20
}
```

Produits en rupture (stock <= 0) :
```json
{
  "table": "stock_location_products",
  "where": [["stock_qty", "<=", 0]],
  "select": ["products_id", "stock_locations_id", "stock_qty", "mini_qty"],
  "limit": 50
}
```
TXT,
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'table' => [
                        'type'        => 'string',
                        'enum'        => array_keys(self::SCHEMA),
                        'description' => 'Nom de la table à interroger.',
                    ],
                    'select' => [
                        'type'        => 'array',
                        'items'       => ['type' => 'string'],
                        'description' => 'Colonnes à retourner (mode liste uniquement). Par défaut : toutes les colonnes exposées.',
                    ],
                    'where' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'array',
                            'description' => '[colonne, opérateur, valeur]. Opérateurs : =, !=, <, <=, >, >=, like, not like, in, not in, is null, is not null, between.',
                        ],
                        'description' => 'Liste de filtres combinés en AND.',
                    ],
                    'date_range' => [
                        'description' => 'Filtre temporel sur la colonne date par défaut. Valeurs : today, yesterday, this_week, last_week, this_month, last_month, this_quarter, last_quarter, this_year, last_year, last_7_days, last_30_days, last_90_days. Ou objet {from, to} au format YYYY-MM-DD.',
                    ],
                    'date_column' => [
                        'type'        => 'string',
                        'description' => 'Colonne date à utiliser avec date_range si autre que la date par défaut.',
                    ],
                    'with' => [
                        'type'        => 'array',
                        'items'       => ['type' => 'string'],
                        'description' => 'Relations à charger (limitées par table). Ex : ["companie"] sur orders/quotes/invoices.',
                    ],
                    'aggregate' => [
                        'type'       => 'object',
                        'properties' => [
                            'function' => ['type' => 'string', 'enum' => self::ALLOWED_AGGREGATES],
                            'column'   => ['type' => 'string', 'description' => 'Colonne à agréger. * pour count(*).'],
                        ],
                        'description' => 'Passe en mode agrégat.',
                    ],
                    'group_by' => [
                        'description' => 'Colonne ou {time: year|month|day|yearmonth|week, column: <date_col>}. Ignoré si aggregate absent.',
                    ],
                    'order_by' => [
                        'type'        => 'string',
                        'description' => 'Mode liste : "colonne asc" ou "colonne desc". Défaut : id desc.',
                    ],
                    'order_agg_desc' => [
                        'type'        => 'boolean',
                        'description' => 'Mode agrégat groupé : tri décroissant sur la valeur agrégée (défaut true).',
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'description' => 'Nombre max de lignes/groupes (défaut ' . self::DEFAULT_LIMIT . ', max ' . self::HARD_LIMIT . ').',
                    ],
                ],
                'required' => ['table'],
            ],
        ];
    }
}
