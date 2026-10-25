<?php

namespace App\Services\Inspection;

use App\Http\Controllers\Inspection\InspectionPlanPointController;
use App\Services\AI\AISettingsResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Lit la cote d'une zone de plan (image découpée par le bulleur) avec le
 * provider IA choisi dans /admin/integrations/ai.
 *
 * Rien n'est enregistré ici : la proposition pré-remplit la fiche de la cote,
 * que l'utilisateur relit. Seule la petite image de la zone part chez le
 * provider, jamais le plan entier.
 */
class CharacteristicReader
{
    public function __construct(private readonly AISettingsResolver $settings)
    {
    }

    /** Un provider est-il configuré ? Sans lui, le bulleur ne propose pas la lecture IA. */
    public function available(): bool
    {
        $provider = $this->settings->activeProvider();

        return $provider === 'claude'
            ? ! empty($this->settings->claude()['api_key'])
            : ! empty($this->settings->openAiCompatible($provider)['base_url']);
    }

    /**
     * @param  string  $png  octets de l'image de la zone
     * @return array<string, mixed> champs de la cote (seuls ceux qui ont été lus)
     *
     * @throws RuntimeException message affichable tel quel
     */
    public function read(string $png): array
    {
        if (! $this->available()) {
            throw new RuntimeException(__('inspection.ai_missing'));
        }

        $provider = $this->settings->activeProvider();
        $data = base64_encode($png);

        $text = $provider === 'claude'
            ? $this->viaClaude($data)
            : $this->viaOpenAiCompatible($provider, $data);

        return $this->toCharacteristic($text);
    }

    private function prompt(): string
    {
        $symbols = implode(' | ', InspectionPlanPointController::GDT_SYMBOLS);

        return <<<PROMPT
Cette image est une petite zone découpée dans un plan de définition mécanique (ISO ou ASME).
Elle contient une cote, une tolérance géométrique (cadre de tolérance), un filetage, une rugosité
ou une note. Lis-la et réponds UNIQUEMENT par un objet JSON, sans texte autour :

{
  "type": "dimension | diameter | radius | angle | gdt | thread | roughness | note",
  "text": "le texte lu, tel qu'écrit",
  "count": entier,
  "nominal": nombre ou null,
  "tol_minus": nombre ou null,
  "tol_plus": nombre ou null,
  "unit": "mm | in | ° | µm ou null",
  "gdt_symbol": "un de : {$symbols} ou null",
  "tolerance_zone": nombre ou null,
  "diameter_zone": true ou false,
  "modifier": "M | L ou null",
  "datums": "A,B,C ou null"
}

Règles :
- N'invente rien : ce qui est absent ou illisible vaut null.
- count : le multiplicateur écrit devant la cote (« 2X », « 4x ») ; 1 s'il n'y en a pas.
- tol_minus et tol_plus sont des écarts signés par rapport au nominal : « ±0,1 » → -0.1 et 0.1 ;
  « +0,2/-0,1 » → -0.1 et 0.2. Des limites « 12,1 / 11,9 » → nominal au milieu, écarts calculés.
  Sans tolérance écrite (tolérance générale du cartouche), laisse-les à null.
- Cote double (mm et pouces) : garde les millimètres.
- diameter : symbole ⌀ ou Ø ; radius : R ; angle : valeur en degrés, unit « ° » ;
  thread : M8, M10x1.25… (nominal = diamètre) ; roughness : Ra / Rz (nominal = valeur).
- gdt : cadre de tolérance. gdt_symbol = la caractéristique, tolerance_zone = la valeur de la zone,
  diameter_zone = true si la zone commence par ⌀, modifier = Ⓜ (M) ou Ⓛ (L) après la zone,
  datums = les lettres des références dans l'ordre, séparées par des virgules. nominal, tol_minus
  et tol_plus restent null.
- Nombres avec un point décimal.
PROMPT;
    }

    private function viaClaude(string $data): string
    {
        $config = $this->settings->claude();

        try {
            $response = Http::withHeaders([
                'x-api-key' => $config['api_key'],
                'anthropic-version' => $this->settings->claudeApiVersion(),
            ])
                ->acceptJson()
                ->timeout(max(30, (int) $config['timeout']))
                ->post($this->settings->claudeEndpoint(), [
                    'model' => $config['model'],
                    'max_tokens' => 400,
                    'temperature' => 0,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => $data]],
                            ['type' => 'text', 'text' => $this->prompt()],
                        ],
                    ]],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Characteristic reading unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('inspection.ai_failed'));
        }

        if ($response->failed()) {
            Log::warning('Characteristic reading failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            throw new RuntimeException(__('inspection.ai_failed'));
        }

        return (string) $response->json('content.0.text', '');
    }

    private function viaOpenAiCompatible(string $provider, string $data): string
    {
        $config = $this->settings->openAiCompatible($provider);

        try {
            $http = Http::acceptJson()->timeout(max(30, (int) $config['timeout']));
            if (! empty($config['api_key'])) {
                $http = $http->withToken($config['api_key']);
            }

            $response = $http->post($config['base_url'] . '/chat/completions', [
                'model' => $config['model'],
                'max_tokens' => 400,
                'temperature' => 0,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . $data]],
                        ['type' => 'text', 'text' => $this->prompt()],
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Characteristic reading unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('inspection.ai_failed'));
        }

        if ($response->failed()) {
            Log::warning('Characteristic reading failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            throw new RuntimeException(__('inspection.ai_failed'));
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    /**
     * Ramène la réponse du modèle aux colonnes de la cote. Ce qui ne se lit pas
     * proprement est omis plutôt que deviné.
     */
    public function toCharacteristic(string $text): array
    {
        if (! preg_match('/\{.*\}/s', $text, $match) || ! is_array($data = json_decode($match[0], true))) {
            throw new RuntimeException(__('inspection.ai_failed'));
        }

        $type = in_array($data['type'] ?? null, InspectionPlanPointController::TYPES, true) ? $data['type'] : 'dimension';
        $label = is_string($data['text'] ?? null) ? mb_substr(trim($data['text']), 0, 255) : null;
        $count = is_numeric($data['count'] ?? null) && (int) $data['count'] >= 1 ? min(999, (int) $data['count']) : 1;

        $out = ['characteristic_type' => $type, 'count' => $count];
        if ($label !== null && $label !== '') {
            $out['label'] = $label;
        }

        if ($type === 'gdt') {
            $symbol = $data['gdt_symbol'] ?? null;
            $datums = is_string($data['datums'] ?? null) ? strtoupper(preg_replace('/[^A-Za-z,]/', '', $data['datums'])) : null;

            return $out + [
                'gdt_symbol'        => in_array($symbol, InspectionPlanPointController::GDT_SYMBOLS, true) ? $symbol : null,
                'nominal_value'     => 0,
                'tol_min'           => 0,
                'tol_max'           => $this->number($data['tolerance_zone'] ?? null),
                'diameter_zone'     => (bool) ($data['diameter_zone'] ?? false),
                'material_modifier' => in_array($data['modifier'] ?? null, ['M', 'L'], true) ? $data['modifier'] : null,
                'datums'            => $datums && preg_match('/^[A-Z](,[A-Z]){0,4}$/', trim($datums, ',')) ? trim($datums, ',') : null,
            ];
        }

        $unit = is_string($data['unit'] ?? null) ? mb_substr(trim($data['unit']), 0, 10) : null;

        return $out + array_filter([
            'nominal_value' => $this->number($data['nominal'] ?? null),
            'tol_min'       => $this->number($data['tol_minus'] ?? null),
            'tol_max'       => $this->number($data['tol_plus'] ?? null),
            'unit'          => $unit !== '' ? $unit : null,
        ], fn ($v) => $v !== null);
    }

    private function number(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace([' ', ','], ['', '.'], $value);
        }

        return is_numeric($value) ? round((float) $value, 4) : null;
    }
}
