<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrations\AISetting;
use App\Services\AI\AISettingsResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

/**
 * Configuration de l'assistant IA — écran d'admin unique.
 *
 * Volontairement sans multi-tenant : une instance = une clé. La logique
 * multi-provider est déjà cadrée en base (colonne `provider`) mais seul
 * Claude est câblé pour l'instant.
 */
class AISettingsController extends Controller
{
    /** Providers connus. Seuls ceux marqués enabled=true sont sélectionnables. */
    private const PROVIDERS = [
        'claude'  => ['label' => 'Anthropic Claude', 'enabled' => true,  'default_model' => 'claude-haiku-4-5-20251001'],
        'openai'  => ['label' => 'OpenAI (GPT-4o…)', 'enabled' => false, 'default_model' => 'gpt-4o-mini'],
        'mistral' => ['label' => 'Mistral (La Plateforme)', 'enabled' => false, 'default_model' => 'mistral-small-latest'],
        'ollama'  => ['label' => 'Ollama (auto-hébergé)', 'enabled' => false, 'default_model' => 'llama3.1:8b'],
    ];

    public function __construct(private readonly AISettingsResolver $resolver) {}

    public function index(): View
    {
        $setting  = AISetting::current();
        $envKey   = (string) config('ai.providers.claude.api_key', '');
        $resolved = $this->resolver->claude();

        return view('integrations.ai', [
            'setting'       => $setting,
            'providers'     => self::PROVIDERS,
            'source'        => $resolved['source'],           // 'db' | 'env'
            'env_key_set'   => $envKey !== '',
            'has_key'       => ! empty($resolved['api_key']),
            'default_model' => $resolved['model'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider'        => 'required|string|in:' . implode(',', array_keys(self::PROVIDERS)),
            // Optionnel : on autorise à ne pas retaper la clé pour ne changer que le modèle.
            'api_key'         => 'nullable|string|max:512',
            'model'           => 'nullable|string|max:120',
            'max_tokens'      => 'required|integer|min:256|max:8192',
            'timeout_seconds' => 'required|integer|min:5|max:300',
            'base_url'        => 'nullable|url|max:255',
            'is_active'       => 'nullable|boolean',
        ]);

        // Provider grisé côté UI mais quelqu'un pourrait POSTer directement : refuser proprement.
        if (! self::PROVIDERS[$validated['provider']]['enabled']) {
            return back()->withErrors(['provider' => 'Ce provider n\'est pas encore branché.'])->withInput();
        }

        $setting = AISetting::current() ?? new AISetting();
        $setting->provider        = $validated['provider'];
        $setting->model           = $validated['model'] ?: null;
        $setting->max_tokens      = $validated['max_tokens'];
        $setting->timeout_seconds = $validated['timeout_seconds'];
        $setting->base_url        = $validated['base_url'] ?: null;
        $setting->is_active       = (bool) ($validated['is_active'] ?? true);

        // On ne remplace la clé que si l'utilisateur en a saisi une nouvelle.
        if (! empty($validated['api_key'])) {
            $setting->api_key = $validated['api_key'];
        }

        $setting->save();
        $this->resolver->forget();

        return redirect()
            ->route('admin.integrations.ai.index')
            ->with('success', 'Configuration IA enregistrée.');
    }

    /**
     * Import 1-clic : recopie ANTHROPIC_API_KEY du .env vers la base et
     * l'y chiffre. Permet de dépeupler le .env sans double saisie.
     */
    public function importFromEnv(): RedirectResponse
    {
        $envKey = (string) config('ai.providers.claude.api_key', '');

        if ($envKey === '') {
            return back()->withErrors(['api_key' => 'Aucune ANTHROPIC_API_KEY trouvée dans le .env.']);
        }

        $setting = AISetting::current() ?? new AISetting();
        $setting->fill([
            'provider'        => 'claude',
            'api_key'         => $envKey,
            'model'           => $setting->model ?: config('ai.providers.claude.default_model'),
            'max_tokens'      => $setting->max_tokens ?: (int) config('ai.providers.claude.max_tokens', 2048),
            'timeout_seconds' => $setting->timeout_seconds ?: (int) config('ai.providers.claude.timeout', 60),
            'is_active'       => true,
        ]);
        $setting->save();
        $this->resolver->forget();

        return redirect()
            ->route('admin.integrations.ai.index')
            ->with('success', 'Clé importée depuis le .env — vous pouvez maintenant la retirer du fichier.');
    }

    /**
     * Appel réel à l'API Claude avec un prompt minimal pour valider la clé.
     * Renvoie du JSON pour un test asynchrone côté page.
     */
    public function test(): JsonResponse
    {
        $config = $this->resolver->claude();

        if (empty($config['api_key'])) {
            return response()->json([
                'ok'      => false,
                'message' => 'Aucune clé API configurée.',
            ]);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $config['api_key'],
                'anthropic-version' => $this->resolver->claudeApiVersion(),
                'content-type'      => 'application/json',
            ])
                ->timeout(10)
                ->post($this->resolver->claudeEndpoint(), [
                    'model'      => $config['model'] ?? 'claude-haiku-4-5-20251001',
                    'max_tokens' => 20,
                    'messages'   => [['role' => 'user', 'content' => 'Réponds uniquement par : OK']],
                ]);

            if ($response->failed()) {
                return response()->json([
                    'ok'      => false,
                    'status'  => $response->status(),
                    'message' => $response->json('error.message') ?? 'Erreur API.',
                ]);
            }

            $data  = $response->json();
            $reply = $data['content'][0]['text'] ?? '';

            return response()->json([
                'ok'      => true,
                'message' => 'Connexion réussie.',
                'model'   => $data['model']  ?? null,
                'reply'   => mb_substr($reply, 0, 80),
                'source'  => $config['source'],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
