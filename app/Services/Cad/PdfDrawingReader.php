<?php

namespace App\Services\Cad;

use App\Services\AI\AISettingsResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfTextParser;

/**
 * Reads the title block of a part drawing in PDF with the AI provider chosen
 * in /admin/integrations/ai.
 *
 * Claude receives the PDF itself, so a scanned drawing is read as well as a
 * CAD export. The OpenAI-compatible providers (OVH) take no document: only
 * the text layer is sent, which a scan does not have.
 *
 * Every failure is a RuntimeException whose message is shown as is next to
 * the file name in the drop zone.
 */
class PdfDrawingReader
{
    /** The text layer of a drawing is short; this only bounds a 50-page PDF. */
    private const MAX_TEXT = 20000;

    /** Below that, the text layer is a stray watermark at best. */
    private const MIN_TEXT = 20;

    /** Reading a drawing takes longer than the short answers the AI timeout is set for. */
    private const MIN_TIMEOUT = 90;

    public function __construct(private readonly AISettingsResolver $settings) {}

    /**
     * @return array{code: ?string, label: ?string, material: ?string, thickness: ?float,
     *               x_size: ?float, y_size: ?float, weight: ?float, qty: ?float,
     *               revision: ?string, finishing: ?string, notes: ?string}
     */
    public function read(UploadedFile $file): array
    {
        $maxSize = (int) config('cad.pdf_ai_max_size');

        if ($maxSize > 0 && $file->getSize() > $maxSize * 1024) {
            throw new RuntimeException('PDF trop volumineux pour la lecture IA (' . round($maxSize / 1024, 1) . ' Mo maximum)');
        }

        $provider = $this->settings->activeProvider();

        // Restarts the PHP budget for each PDF of the drop: several drawings
        // read in a row would otherwise outlast max_execution_time.
        @set_time_limit(self::MIN_TIMEOUT + 30);

        $content = $provider === 'claude'
            ? $this->viaClaude($file)
            : $this->viaOpenAiCompatible($provider, $file);

        return $this->normalise($this->decode($content));
    }

    private function viaClaude(UploadedFile $file): string
    {
        $config = $this->settings->claude();

        if (empty($config['api_key'])) {
            throw new RuntimeException('IA non configurée : renseignez la clé dans Intégrations → IA');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $config['api_key'],
                'anthropic-version' => $this->settings->claudeApiVersion(),
            ])
                ->acceptJson()
                ->timeout(max((int) $config['timeout'], self::MIN_TIMEOUT))
                ->post($this->settings->claudeEndpoint(), [
                    'model' => $config['model'],
                    'max_tokens' => 1024,
                    'system' => $this->systemPrompt(),
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'document',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => 'application/pdf',
                                    'data' => base64_encode((string) file_get_contents($file->getRealPath())),
                                ],
                            ],
                            ['type' => 'text', 'text' => $this->prompt()],
                        ],
                    ]],
                ]);
        } catch (\Throwable $e) {
            Log::warning('PDF drawing read unreachable', ['provider' => 'claude', 'error' => $e->getMessage()]);
            throw new RuntimeException('IA injoignable, réessayez plus tard');
        }

        if ($response->failed()) {
            Log::warning('PDF drawing read failed', [
                'provider' => 'claude',
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('Lecture IA refusée : ' . $response->json('error.message', 'HTTP ' . $response->status()));
        }

        return (string) collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');
    }

    /**
     * Direct call without tools, like VisitReportService: the assistant
     * provider would inject the whole ERP tool registry for nothing.
     */
    private function viaOpenAiCompatible(string $provider, UploadedFile $file): string
    {
        $text = $this->textLayer($file);

        if (mb_strlen($text) < self::MIN_TEXT) {
            throw new RuntimeException('Aucun texte lisible dans ce PDF (plan scanné ou textes vectorisés) : '
                . 'seule l\'IA Claude lit ce type de document (Intégrations → IA)');
        }

        $config = $this->settings->openAiCompatible($provider);

        try {
            $http = Http::acceptJson()->timeout(max((int) $config['timeout'], self::MIN_TIMEOUT));

            if (! empty($config['api_key'])) {
                $http = $http->withToken($config['api_key']);
            }

            $response = $http->post($config['base_url'] . '/chat/completions', [
                'model' => $config['model'],
                'max_tokens' => 1024,
                'temperature' => 0,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => "Texte extrait du PDF « {$file->getClientOriginalName()} » "
                        . "(l'ordre des mots peut être désordonné) :\n\n{$text}\n\n" . $this->prompt()],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('PDF drawing read unreachable', ['provider' => $provider, 'error' => $e->getMessage()]);
            throw new RuntimeException('IA injoignable, réessayez plus tard');
        }

        if ($response->failed()) {
            Log::warning('PDF drawing read failed', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('Lecture IA refusée (HTTP ' . $response->status() . ')');
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    private function textLayer(UploadedFile $file): string
    {
        try {
            $text = (new PdfTextParser())->parseFile($file->getRealPath())->getText();
        } catch (\Throwable $e) {
            throw new RuntimeException('PDF illisible');
        }

        $text = trim(preg_replace('/[ \t]+/u', ' ', $text) ?? '');

        return mb_substr($text, 0, self::MAX_TEXT);
    }

    /**
     * Pull the JSON object out of the answer, whatever the model wrapped it in.
     *
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        $data = ($start !== false && $end !== false && $end > $start)
            ? json_decode(substr($content, $start, $end - $start + 1), true)
            : null;

        if (! is_array($data)) {
            Log::warning('PDF drawing read: unexpected answer', ['content' => mb_substr($content, 0, 500)]);
            throw new RuntimeException('Réponse de l\'IA inexploitable');
        }

        if (($data['is_drawing'] ?? true) === false) {
            throw new RuntimeException('Ce PDF ne ressemble pas à un plan de pièce');
        }

        return $data;
    }

    /**
     * The model is told to answer null when it does not know; this also
     * throws away whatever it put in a numeric field that is not a number.
     *
     * @param  array<string, mixed>  $data
     */
    private function normalise(array $data): array
    {
        $text = function (mixed $value): ?string {
            if (! is_scalar($value)) {
                return null;
            }

            $value = trim((string) $value);

            return $value === '' ? null : $value;
        };

        $number = function (mixed $value): ?float {
            if (is_string($value)) {
                $value = str_replace([',', ' '], ['.', ''], $value);
            }

            if (! is_numeric($value) || (float) $value <= 0) {
                return null;
            }

            return (float) $value;
        };

        return [
            'code' => $text($data['code'] ?? null),
            'label' => $text($data['label'] ?? null),
            'material' => $text($data['material'] ?? null),
            'thickness' => $number($data['thickness'] ?? null),
            'x_size' => $number($data['x_size'] ?? null),
            'y_size' => $number($data['y_size'] ?? null),
            'weight' => $number($data['weight'] ?? null),
            'qty' => $number($data['qty'] ?? null),
            'revision' => $text($data['revision'] ?? null),
            'finishing' => $text($data['finishing'] ?? null),
            'notes' => $text($data['notes'] ?? null),
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Tu lis des plans de pièces mécaniques (tôlerie, chaudronnerie, usinage) pour pré-remplir une
ligne de devis. Tu réponds uniquement par un objet JSON, sans texte autour.

Règles :
- N'invente rien. Une information absente ou illisible vaut null.
- Les valeurs du cartouche priment sur celles lues dans la vue.
- Les cotes sont en millimètres, le poids en kilogrammes : convertis si le plan indique une
  autre unité, sinon recopie la valeur telle quelle.
PROMPT;
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
Renvoie cet objet JSON :
{
  "is_drawing": true si le document est un plan de pièce, false sinon,
  "code": "repère, référence ou numéro de plan de la pièce",
  "label": "désignation de la pièce",
  "material": "matière / nuance (ex. S235, Inox 304L, AlMg3)",
  "thickness": épaisseur en mm (nombre),
  "x_size": plus grande dimension hors tout en mm (nombre ; développé si le plan l'indique),
  "y_size": deuxième dimension hors tout en mm (nombre),
  "weight": masse en kg (nombre),
  "qty": quantité à fabriquer indiquée sur le plan (nombre),
  "revision": "indice de révision",
  "finishing": "traitement de surface ou finition",
  "notes": "autres exigences utiles au chiffrage (tolérance générale, soudure, pliage…), en une phrase"
}
PROMPT;
    }
}
