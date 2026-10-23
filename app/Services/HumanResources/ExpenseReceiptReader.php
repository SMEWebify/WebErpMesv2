<?php

namespace App\Services\HumanResources;

use App\Models\File;
use App\Models\UserExpenseCategory;
use App\Services\AI\AISettingsResolver;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Lit un justificatif de note de frais (photo de ticket, facture PDF) avec le
 * provider IA choisi dans /admin/integrations/ai, et propose les champs de la
 * dépense.
 *
 * Rien n'est enregistré ici : la proposition pré-remplit le formulaire, que le
 * salarié relit avant de valider. Le justificatif part chez le provider le
 * temps de l'appel ; il n'est conservé que dans la GED.
 */
class ExpenseReceiptReader
{
    // Côté le plus long envoyé au modèle : un ticket reste lisible, et une
    // photo de téléphone (12 Mpx) ne dépasse plus les limites des API.
    private const MAX_IMAGE_SIDE = 1600;

    public function __construct(
        private readonly AISettingsResolver $settings,
        private readonly FileStorageService $storage,
    ) {
    }

    /**
     * @return array{expense_date: ?string, amount: ?float, tax: ?float, location: ?string,
     *               description: ?string, category_id: ?int}
     *
     * @throws RuntimeException message affichable tel quel à l'utilisateur
     */
    public function read(File $file): array
    {
        $located = $this->storage->locate($file);
        if ($located === null) {
            throw new RuntimeException(__('general_content.expense_receipt_unreadable_trans_key'));
        }

        [$disk, $path] = $located;
        $extension = FileKindResolver::extensionOf($path);
        $bytes = Storage::disk($disk)->get($path);

        $categories = UserExpenseCategory::query()->orderBy('label')->get(['id', 'label']);

        $provider = $this->settings->activeProvider();
        $attachment = $this->attachment($extension, $bytes, $provider);

        $text = $provider === 'claude'
            ? $this->viaClaude($attachment, $this->prompt($categories->pluck('label')->all()))
            : $this->viaOpenAiCompatible($provider, $attachment, $this->prompt($categories->pluck('label')->all()));

        return $this->toExpense($text, $categories);
    }

    /**
     * @return array{type: 'image'|'pdf', media_type: string, data: string}
     */
    private function attachment(string $extension, string $bytes, string $provider): array
    {
        if ($extension === 'pdf') {
            // Les API compatibles OpenAI n'acceptent que des images.
            if ($provider !== 'claude') {
                throw new RuntimeException(__('general_content.expense_receipt_pdf_unsupported_trans_key'));
            }

            return ['type' => 'pdf', 'media_type' => 'application/pdf', 'data' => base64_encode($bytes)];
        }

        if (FileKindResolver::fromExtension($extension) !== FileKindResolver::KIND_IMAGE) {
            throw new RuntimeException(__('general_content.expense_receipt_unreadable_trans_key'));
        }

        return ['type' => 'image', 'media_type' => 'image/jpeg', 'data' => base64_encode($this->shrink($bytes))];
    }

    /**
     * Réduit et redresse la photo, ré-encodée en JPEG.
     */
    private function shrink(string $bytes): string
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException(__('general_content.expense_receipt_unreadable_trans_key'));
        }

        // Une photo de téléphone est souvent stockée couchée, avec l'orientation en EXIF.
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
            $angle = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($angle !== 0) {
                $image = imagerotate($image, $angle, 0);
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = self::MAX_IMAGE_SIDE / max($width, $height);

        if ($ratio < 1) {
            $image = imagescale($image, (int) round($width * $ratio), (int) round($height * $ratio));
        }

        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    /**
     * @param  array<int, string>  $categories
     */
    private function prompt(array $categories): string
    {
        $list = $categories === [] ? '(aucune)' : implode(' | ', $categories);

        return <<<PROMPT
Ce document est le justificatif d'une note de frais (ticket de caisse, facture, reçu).
Extrais-en les informations et réponds UNIQUEMENT par un objet JSON, sans texte autour :

{
  "date": "AAAA-MM-JJ ou null",
  "total_ttc": nombre ou null,
  "vat_rate": nombre ou null,
  "merchant": "texte ou null",
  "city": "texte ou null",
  "description": "texte court ou null",
  "category": "une valeur de la liste ou null"
}

Règles :
- N'invente rien : une information absente ou illisible vaut null.
- total_ttc : le montant total payé, toutes taxes comprises, nombre avec un point décimal.
- vat_rate : le taux de TVA en pourcentage (20, 10, 5.5…). S'il y en a plusieurs, celui qui porte
  le plus gros montant.
- description : quelques mots sur la nature de la dépense (ex. « Déjeuner client », « Carburant »).
- category : choisis dans cette liste, recopiée à l'identique : {$list}
PROMPT;
    }

    /**
     * @param  array{type: string, media_type: string, data: string}  $attachment
     */
    private function viaClaude(array $attachment, string $prompt): string
    {
        $config = $this->settings->claude();

        if (empty($config['api_key'])) {
            throw new RuntimeException(__('general_content.expense_receipt_ai_missing_trans_key'));
        }

        $block = $attachment['type'] === 'pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $attachment['data']]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $attachment['media_type'], 'data' => $attachment['data']]];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $config['api_key'],
                'anthropic-version' => $this->settings->claudeApiVersion(),
            ])
                ->acceptJson()
                ->timeout(max(60, (int) $config['timeout']))
                ->post($this->settings->claudeEndpoint(), [
                    'model' => $config['model'],
                    'max_tokens' => 600,
                    'temperature' => 0,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [$block, ['type' => 'text', 'text' => $prompt]],
                    ]],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Expense receipt unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('general_content.expense_receipt_failed_trans_key'));
        }

        if ($response->failed()) {
            Log::warning('Expense receipt failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException(__('general_content.expense_receipt_failed_trans_key'));
        }

        return (string) $response->json('content.0.text', '');
    }

    /**
     * Appel direct sans outils, comme le compte rendu de visite.
     *
     * @param  array{type: string, media_type: string, data: string}  $attachment
     */
    private function viaOpenAiCompatible(string $provider, array $attachment, string $prompt): string
    {
        $config = $this->settings->openAiCompatible($provider);

        if (empty($config['base_url'])) {
            throw new RuntimeException(__('general_content.expense_receipt_ai_missing_trans_key'));
        }

        try {
            $http = Http::acceptJson()->timeout(max(60, (int) $config['timeout']));
            if (! empty($config['api_key'])) {
                $http = $http->withToken($config['api_key']);
            }

            $response = $http->post($config['base_url'] . '/chat/completions', [
                'model' => $config['model'],
                'max_tokens' => 600,
                'temperature' => 0,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $attachment['media_type'] . ';base64,' . $attachment['data']]],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Expense receipt unreachable', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('general_content.expense_receipt_failed_trans_key'));
        }

        if ($response->failed()) {
            Log::warning('Expense receipt failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException(__('general_content.expense_receipt_failed_trans_key'));
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    /**
     * Ramène la réponse du modèle aux champs du formulaire. Tout ce qui ne se
     * lit pas proprement est laissé vide plutôt que deviné.
     *
     * @param  \Illuminate\Support\Collection<int, UserExpenseCategory>  $categories
     */
    private function toExpense(string $text, $categories): array
    {
        // Le modèle entoure parfois le JSON d'un bloc ```json … ```.
        if (! preg_match('/\{.*\}/s', $text, $match)) {
            throw new RuntimeException(__('general_content.expense_receipt_failed_trans_key'));
        }

        $data = json_decode($match[0], true);
        if (! is_array($data)) {
            throw new RuntimeException(__('general_content.expense_receipt_failed_trans_key'));
        }

        $date = is_string($data['date'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])
            && strtotime($data['date']) !== false
            ? $data['date']
            : null;

        $category = is_string($data['category'] ?? null)
            ? $categories->first(fn ($c) => mb_strtolower(trim($c->label)) === mb_strtolower(trim($data['category'])))
            : null;

        $location = trim(implode(' - ', array_filter([
            is_string($data['merchant'] ?? null) ? trim($data['merchant']) : null,
            is_string($data['city'] ?? null) ? trim($data['city']) : null,
        ])));

        return [
            'expense_date' => $date,
            'amount' => $this->number($data['total_ttc'] ?? null),
            'tax' => $this->number($data['vat_rate'] ?? null),
            'location' => $location !== '' ? $location : null,
            'description' => is_string($data['description'] ?? null) && trim($data['description']) !== ''
                ? trim($data['description'])
                : null,
            'category_id' => $category?->id,
        ];
    }

    private function number(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace([' ', ','], ['', '.'], $value);
        }

        return is_numeric($value) && (float) $value >= 0 ? round((float) $value, 2) : null;
    }
}
