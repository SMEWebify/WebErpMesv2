<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrations\ESignatureSetting;
use App\Services\Integrations\Signature\Drivers\DocuSignGateway;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use App\Services\Integrations\Signature\QuoteSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Configuration de la signature électronique — écran d'admin unique.
 *
 * Tout se saisit ici, rien dans le .env : la clé privée RSA et la clé HMAC sont
 * chiffrées en base et ne sont jamais réaffichées (laisser le champ vide = garder).
 */
class ESignatureSettingsController extends Controller
{
    public function __construct(private readonly QuoteSignatureService $service) {}

    public function index(): View
    {
        $setting = ESignatureSetting::current();
        $redirectUri = route('admin.integrations.esignature.index');

        return view('integrations.esignature', [
            'setting'         => $setting,
            'has_private_key' => $setting && filled($setting->getRawOriginal('private_key')),
            'has_hmac_key'    => $setting && filled($setting->getRawOriginal('hmac_key')),
            'redirect_uri'    => $redirectUri,
            'consent_url'     => $setting && filled($setting->integration_key)
                ? (new DocuSignGateway($setting))->consentUrl($redirectUri)
                : null,
            'webhook_url'     => $this->service->webhookUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = ESignatureSetting::current() ?? new ESignatureSetting(['provider' => 'docusign']);

        $validated = $request->validate([
            'environment'     => ['required', Rule::in([ESignatureSetting::ENV_DEMO, ESignatureSetting::ENV_PRODUCTION])],
            'integration_key' => 'required|string|max:255',
            'api_user_id'     => 'required|string|max:255',
            'account_id'      => 'required|string|max:255',
            // Laissée vide → on garde la clé existante ; obligatoire à la première saisie.
            'private_key'     => [Rule::requiredIf(! filled($setting->getRawOriginal('private_key'))), 'nullable', 'string', 'max:10000'],
            'hmac_key'        => 'nullable|string|max:255',
            'clear_hmac_key'  => 'nullable|boolean',
            'signing_mode'    => ['required', Rule::in([ESignatureSetting::MODE_EMBEDDED, ESignatureSetting::MODE_EMAIL])],
            'is_active'       => 'nullable|boolean',
        ]);

        $setting->fill([
            'environment'     => $validated['environment'],
            'integration_key' => trim($validated['integration_key']),
            'api_user_id'     => trim($validated['api_user_id']),
            'account_id'      => trim($validated['account_id']),
            'signing_mode'    => $validated['signing_mode'],
            'is_active'       => (bool) ($validated['is_active'] ?? false),
        ]);

        if (filled($validated['private_key'] ?? null)) {
            $setting->private_key = trim($validated['private_key']);
        }

        if ($request->boolean('clear_hmac_key')) {
            $setting->hmac_key = null;
        } elseif (filled($validated['hmac_key'] ?? null)) {
            $setting->hmac_key = trim($validated['hmac_key']);
        }

        $setting->save();

        return redirect()
            ->route('admin.integrations.esignature.index')
            ->with('success', __('esignature.saved'));
    }

    /**
     * Obtient un jeton et lit le compte : valide d'un coup la clé, le consentement
     * et l'Account ID. Renvoie le lien de consentement quand c'est lui qui manque.
     */
    public function test(): JsonResponse
    {
        try {
            $account = $this->service->gateway()->testConnection();

            return response()->json(['ok' => true, 'message' => __('esignature.test_ok', ['account' => $account])]);
        } catch (SignatureException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage(), 'consent_url' => $e->consentUrl]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}
