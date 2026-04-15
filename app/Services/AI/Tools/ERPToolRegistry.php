<?php

namespace App\Services\AI\Tools;

use InvalidArgumentException;

/**
 * Registre centralisé de tous les outils ERP exposés à Claude.
 *
 * Responsabilités :
 *  - Fournir les définitions JSON Schema à envoyer à l'API Claude
 *  - Dispatcher l'exécution vers le bon outil PHP quand Claude fait un tool_use
 */
class ERPToolRegistry
{
    public function __construct(
        private readonly OrderQueryTool   $orderTool,
        private readonly StockQueryTool   $stockTool,
        private readonly InvoiceQueryTool $invoiceTool,
        private readonly QuoteQueryTool   $quoteTool,
    ) {}

    /**
     * Retourne toutes les définitions d'outils au format attendu par l'API Claude.
     */
    public function definitions(): array
    {
        return [
            OrderQueryTool::definition(),
            StockQueryTool::definition(),
            InvoiceQueryTool::definition(),
            QuoteQueryTool::definition(),
        ];
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
            default => throw new InvalidArgumentException("Outil inconnu : {$toolName}"),
        };
    }
}
