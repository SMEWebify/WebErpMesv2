<?php

namespace App\Services\AI\Tools;

use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use App\Services\AI\Tools\DailyJournalTool;

/**
 * Registre centralisé de tous les outils ERP exposés à Claude.
 *
 * Responsabilités :
 *  - Fournir les définitions JSON Schema à envoyer à l'API Claude
 *  - Dispatcher l'exécution vers le bon outil PHP quand Claude fait un tool_use
 *
 * Droits : l'assistant ne voit que ce que l'utilisateur voit à l'écran. Un outil
 * n'est proposé (definitions) et exécuté (dispatch) que si l'utilisateur porte
 * la permission de menu correspondante. Le contrôle de dispatch() est celui qui
 * fait foi : le modèle peut demander un outil qu'on ne lui a pas proposé.
 */
class ERPToolRegistry
{
    /**
     * Permissions exigées par outil (une seule suffit).
     * `get_daily_journal` et `query` filtrent en plus leurs sections / tables.
     */
    private const TOOL_PERMISSIONS = [
        'search_orders'     => ['orders-menu'],
        'check_stock'       => ['products-menu'],
        'search_invoices'   => ['invoices-menu'],
        'search_quotes'     => ['quotes-menu'],
        'get_daily_journal' => DailyJournalTool::SECTION_PERMISSIONS,
    ];

    public function __construct(
        private readonly OrderQueryTool     $orderTool,
        private readonly StockQueryTool     $stockTool,
        private readonly InvoiceQueryTool   $invoiceTool,
        private readonly QuoteQueryTool     $quoteTool,
        private readonly DailyJournalTool   $journalTool,
        private readonly UniversalQueryTool $queryTool,
    ) {}

    /**
     * Retourne les définitions des outils autorisés à l'utilisateur courant,
     * au format attendu par l'API Claude.
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ([
            'search_orders'     => fn () => OrderQueryTool::definition(),
            'check_stock'       => fn () => StockQueryTool::definition(),
            'search_invoices'   => fn () => InvoiceQueryTool::definition(),
            'search_quotes'     => fn () => QuoteQueryTool::definition(),
            'get_daily_journal' => fn () => DailyJournalTool::definition(),
        ] as $name => $definition) {
            if ($this->allows($name)) {
                $definitions[] = $definition();
            }
        }

        $tables = $this->queryTool->allowedTables();
        if ($tables !== []) {
            $definitions[] = UniversalQueryTool::definition($tables);
        }

        return $definitions;
    }

    /**
     * Exécute un outil par son nom avec les paramètres fournis par Claude.
     *
     * @param  string  $toolName  Nom de l'outil (ex: "search_orders")
     * @param  array   $input     Paramètres fournis par Claude
     * @return array              Résultat à renvoyer comme tool_result
     */
    public function dispatch(string $toolName, array $input): array
    {
        if (isset(self::TOOL_PERMISSIONS[$toolName]) && ! $this->allows($toolName)) {
            return ['error' => "L'utilisateur n'a pas accès à ces données."];
        }

        return match ($toolName) {
            'search_orders'   => $this->orderTool->search(
                client: $input['client']  ?? null,
                code:   $input['code']    ?? null,
                statu:  isset($input['statu']) ? (int) $input['statu'] : null,
                limit:  (int) ($input['limit'] ?? 10),
            ),
            'check_stock'     => $this->stockTool->check(
                reference: $input['reference'] ?? null,
                label:     $input['label']     ?? null,
                limit:     (int) ($input['limit'] ?? 10),
            ),
            'search_invoices' => $this->invoiceTool->search(
                client:      $input['client']      ?? null,
                unpaid_only: (bool) ($input['unpaid_only'] ?? false),
                limit:       (int) ($input['limit'] ?? 15),
            ),
            'search_quotes'   => $this->quoteTool->search(
                client: $input['client'] ?? null,
                code:   $input['code']   ?? null,
                statu:  isset($input['statu']) ? (int) $input['statu'] : null,
                limit:  (int) ($input['limit'] ?? 10),
            ),
            'get_daily_journal' => $this->journalTool->fetch(
                date: $input['date'] ?? null,
            ),
            'query'           => $this->queryTool->run($input),
            default => throw new InvalidArgumentException("Outil inconnu : {$toolName}"),
        };
    }

    private function allows(string $toolName): bool
    {
        return Gate::any(self::TOOL_PERMISSIONS[$toolName]);
    }
}
