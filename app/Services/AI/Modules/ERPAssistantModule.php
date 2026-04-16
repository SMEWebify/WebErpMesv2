<?php

namespace App\Services\AI\Modules;

use App\Services\AI\AIGateway;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\Tools\ERPToolRegistry;

/**
 * Module IA : Assistant ERP pilotable par commandes texte/vocales.
 *
 * Utilise le tool use Claude pour interroger l'ERP (commandes, stock,
 * factures, devis) sans jamais exposer de données brutes côté client.
 * Claude choisit lui-même quel outil appeler selon la demande.
 */
class ERPAssistantModule
{
    private const PROVIDER = 'tool_claude';

    public function __construct(
        private readonly AIGateway       $gateway,
        private readonly ERPToolRegistry $toolRegistry,
    ) {}

    /**
     * Envoie un message utilisateur + historique à Claude.
     *
     * @param  string       $message  Message de l'utilisateur
     * @param  array        $history  Historique [{role, content}] multi-tour
     * @param  string|null  $locale   Locale de l'interface (ex: 'fr', 'en')
     */
    public function chat(string $message, array $history = [], ?string $locale = null): AIResponse
    {
        $request = AIRequest::make($message)
            ->withSystemPrompt($this->systemPrompt($locale))
            ->withMessages($history)
            ->withTools($this->toolRegistry->definitions())
            ->withMaxTokens(1024)
            ->withMeta(['module' => self::class, 'locale' => $locale]);

        return $this->gateway->complete($request, self::PROVIDER);
    }

    protected function systemPrompt(?string $locale): string
    {
        $appLocale   = $locale ?? app()->getLocale();
        $today       = now()->locale($appLocale)->isoFormat('dddd D MMMM YYYY');
        $langLabel   = match ($appLocale) {
            'fr'    => 'français',
            'en'    => 'English',
            'de'    => 'Deutsch',
            'es'    => 'español',
            default => $appLocale,
        };

        return <<<PROMPT
You are the company's ERP assistant. You help users (sales, managers, admin team) query real-time business data: orders, stock, invoices, quotes.

Today's date: {$today}
Interface language: {$langLabel} (locale: {$appLocale})

## Behavior
- **Always reply in the same language as the user's message.** If they write in French, reply in French. If they write in English, reply in English. Adapt naturally.
- Use the available tools to answer. Never invent data.
- If no result is found, say so clearly.
- Present lists in a readable format (simple markdown: lists, bold).
- When a query is ambiguous, use the tool with available parameters and present the results.
- Always include the URL link to access the record directly.

## What you can do
- Search orders (by client, code, status)
- Check stock for a product or raw material
- List invoices (unpaid, by client)
- Search quotes
- Generate the daily journal (yesterday's new orders, delivery notes, and purchase receipts) — use `get_daily_journal` whenever the user asks for "le journal", "journal du jour", "résumé de la veille", "daily journal", or similar

## What you don't do
- You never modify data (read-only)
- You don't share confidential information outside the ERP scope
- You don't perform actions without explicit user confirmation
PROMPT;
    }
}
