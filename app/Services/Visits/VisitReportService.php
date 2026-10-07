<?php

namespace App\Services\Visits;

use App\Models\Workflow\OpportunityVisits;
use App\Services\AI\AIGateway;
use App\Services\AI\AISettingsResolver;
use App\Services\AI\DTOs\AIRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Rédige le compte rendu d'une visite à partir des notes, des cotes relevées
 * et de la transcription du mémo vocal, avec le provider IA choisi dans
 * /admin/integrations/ai.
 *
 * Le texte produit est un brouillon : il est renvoyé à l'écran pour relecture
 * et n'est enregistré que lorsque l'utilisateur sauvegarde ou valide.
 */
class VisitReportService
{
    public function __construct(
        private readonly AIGateway $gateway,
        private readonly AISettingsResolver $settings,
    ) {
    }

    /**
     * @throws RuntimeException message affichable tel quel à l'utilisateur
     */
    public function draft(OpportunityVisits $visit): string
    {
        if (blank($visit->notes) && blank($visit->transcript) && empty($visit->measurements)) {
            throw new RuntimeException(__('visits.report_nothing_to_summarize'));
        }

        $system = $this->systemPrompt();
        $prompt = $this->prompt($visit);

        $provider = $this->settings->activeProvider();

        $content = $provider === 'claude'
            ? $this->viaClaude($system, $prompt)
            : $this->viaOpenAiCompatible($provider, $system, $prompt);

        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException(__('visits.report_failed'));
        }

        return $content;
    }

    private function viaClaude(string $system, string $prompt): string
    {
        $response = $this->gateway->complete(
            AIRequest::make($prompt)->withSystemPrompt($system)->withMaxTokens(2000),
            'claude'
        );

        if ($response->failed()) {
            Log::warning('Visit report failed', ['error' => $response->error]);
            throw new RuntimeException(__('visits.report_failed'));
        }

        return $response->content;
    }

    /**
     * Appel direct sans outils : le provider de l'assistant injecte d'office le
     * registre d'outils ERP, inutile (et coûteux) pour une simple rédaction.
     */
    private function viaOpenAiCompatible(string $provider, string $system, string $prompt): string
    {
        $config = $this->settings->openAiCompatible($provider);

        try {
            $http = Http::acceptJson()->timeout($config['timeout']);
            if (! empty($config['api_key'])) {
                $http = $http->withToken($config['api_key']);
            }

            $response = $http->post($config['base_url'] . '/chat/completions', [
                'model' => $config['model'],
                'max_tokens' => 2000,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Visit report unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('visits.report_failed'));
        }

        if ($response->failed()) {
            Log::warning('Visit report failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException(__('visits.report_failed'));
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    private function systemPrompt(): string
    {
        $language = match (app()->getLocale()) {
            'fr' => 'français',
            'es' => 'espagnol',
            default => 'anglais',
        };

        return <<<PROMPT
Tu rédiges le compte rendu d'une visite commerciale sur site, pour une entreprise industrielle
(tôlerie, chaudronnerie, usinage). Le lecteur est le chiffreur qui préparera le devis.

Règles :
- N'invente rien. Ce qui n'est pas dans les éléments fournis n'existe pas ; s'il manque une
  information utile au chiffrage, liste-la dans « Points à clarifier ».
- Reprends les cotes telles quelles, avec leur unité. Ne convertis pas, n'arrondis pas.
- La transcription vient d'une dictée sur le terrain : corrige les erreurs de reconnaissance
  évidentes, ignore les hésitations, mais ne change pas le sens.
- Texte brut, sans markdown. Titres de section en majuscules, puces avec « - ».
- Sections, dans cet ordre, en omettant celles qui seraient vides :
  CONTEXTE, BESOIN EXPRIMÉ, RELEVÉS ET COTES, CONTRAINTES DU SITE, POINTS À CLARIFIER, PROCHAINES ÉTAPES.
- Rédige en {$language}.
PROMPT;
    }

    private function prompt(OpportunityVisits $visit): string
    {
        $visit->loadMissing('opportunity.companie', 'opportunity.contact', 'opportunity.adresse', 'user');
        $opportunity = $visit->opportunity;

        $lines = [];
        $lines[] = 'Opportunité : ' . $opportunity->label;
        $lines[] = 'Client : ' . ($opportunity->companie->label ?? '—');

        if ($opportunity->contact) {
            $lines[] = 'Contact : ' . trim(($opportunity->contact->first_name ?? '') . ' ' . ($opportunity->contact->name ?? ''));
        }

        if ($opportunity->adresse) {
            $lines[] = 'Adresse : ' . trim(implode(' ', array_filter([
                $opportunity->adresse->adress ?? null,
                $opportunity->adresse->zipcode ?? null,
                $opportunity->adresse->city ?? null,
            ])));
        }

        $lines[] = 'Date de la visite : ' . $visit->visited_at->format('d/m/Y H:i');
        $lines[] = 'Visite réalisée par : ' . ($visit->user->name ?? '—');

        $measurements = collect($visit->measurements ?? [])
            ->filter(fn ($m) => filled($m['label'] ?? null) || filled($m['value'] ?? null))
            ->map(fn ($m) => '- ' . trim(($m['label'] ?? '') . ' : ' . ($m['value'] ?? '') . ' ' . ($m['unit'] ?? ''))
                . (filled($m['note'] ?? null) ? ' (' . $m['note'] . ')' : ''));

        $lines[] = '';
        $lines[] = 'COTES RELEVÉES :';
        $lines[] = $measurements->isEmpty() ? '(aucune)' : $measurements->implode("\n");

        $lines[] = '';
        $lines[] = 'NOTES PRISES SUR PLACE :';
        $lines[] = filled($visit->notes) ? $visit->notes : '(aucune)';

        $lines[] = '';
        $lines[] = 'TRANSCRIPTION DU MÉMO VOCAL :';
        $lines[] = filled($visit->transcript) ? $visit->transcript : '(aucune)';

        return implode("\n", $lines);
    }
}
