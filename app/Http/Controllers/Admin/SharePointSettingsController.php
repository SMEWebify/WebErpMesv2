<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrations\SharePointMirrorItem;
use App\Models\Integrations\SharePointSetting;
use App\Services\Integrations\SharePoint\GraphClient;
use App\Services\Integrations\SharePoint\SharePointException;
use App\Services\Integrations\SharePoint\SharePointMirror;
use App\Services\Integrations\SharePoint\SharePointNames;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Recopie de la GED vers SharePoint — écran d'admin unique (Intégrations → SharePoint).
 *
 * Le secret de l'application Entra ID est chiffré en base et jamais réaffiché : un champ
 * laissé vide le conserve. L'enregistrement teste la connexion et garde le site et la
 * bibliothèque résolus.
 */
class SharePointSettingsController extends Controller
{
    public function __construct(private readonly SharePointMirror $mirror) {}

    public function index(): View
    {
        $setting = SharePointSetting::current();

        return view('integrations.sharepoint', [
            'setting'    => $setting,
            'has_secret' => (bool) $setting?->hasSecret(),
            'stats'      => $this->mirror->stats(),
            'failures'   => SharePointMirrorItem::where('status', SharePointMirrorItem::STATUS_FAILED)
                ->latest('updated_at')
                ->limit(10)
                ->get(['id', 'file_id', 'fileable_type', 'fileable_id', 'last_error', 'updated_at']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = SharePointSetting::current() ?? new SharePointSetting(['status' => SharePointSetting::STATUS_NONE]);

        $validated = $request->validate([
            'tenant_id'     => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'client_id'     => ['required', 'uuid'],
            // Obligatoire à la première saisie, ensuite vide = conserver.
            'client_secret' => [Rule::requiredIf(! $setting->hasSecret()), 'nullable', 'string', 'max:255'],
            'site_url'      => ['required', 'url:https', 'max:255'],
            'library'       => ['nullable', 'string', 'max:255'],
            'root_folder'   => ['nullable', 'string', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $before = [$setting->tenant_id, $setting->client_id, $setting->site_url, $setting->library];

        $setting->fill([
            'tenant_id'   => trim($validated['tenant_id']),
            'client_id'   => trim($validated['client_id']),
            'site_url'    => rtrim(trim($validated['site_url']), '/'),
            'library'     => filled($validated['library'] ?? null) ? trim($validated['library']) : null,
            'root_folder' => $this->cleanRoot($validated['root_folder'] ?? null),
            'is_active'   => $request->boolean('is_active'),
        ]);

        if (filled($validated['client_secret'] ?? null)) {
            $setting->client_secret = trim($validated['client_secret']);
        }

        // Autre site ou autre bibliothèque : les dossiers déjà créés ne valent plus.
        if ($before !== [$setting->tenant_id, $setting->client_id, $setting->site_url, $setting->library]) {
            $setting->drive_id = null;
        }

        $setting->save();

        $result = $this->check($setting);

        return redirect()
            ->route('admin.integrations.sharepoint.index')
            ->with($result['ok'] ? 'success' : 'warning', __('sharepoint.saved') . ' ' . $result['message']);
    }

    public function test(): JsonResponse
    {
        $setting = SharePointSetting::current();
        if (! $setting?->isComplete()) {
            return response()->json(['ok' => false, 'message' => __('sharepoint.not_configured')]);
        }

        return response()->json($this->check($setting));
    }

    /**
     * Met en file les fichiers existants de la GED.
     */
    public function backfill(): RedirectResponse
    {
        if (! SharePointSetting::usable()) {
            return redirect()->route('admin.integrations.sharepoint.index')->with('warning', __('sharepoint.not_usable'));
        }

        $count = $this->mirror->backfill();

        return redirect()
            ->route('admin.integrations.sharepoint.index')
            ->with('success', trans_choice('sharepoint.backfill_queued', $count, ['count' => $count]));
    }

    public function retry(): RedirectResponse
    {
        $count = $this->mirror->retryFailed();

        return redirect()
            ->route('admin.integrations.sharepoint.index')
            ->with('success', trans_choice('sharepoint.retry_queued', $count, ['count' => $count]));
    }

    /**
     * Jeton, site, bibliothèque : garde les identifiants résolus et l'état.
     *
     * @return array{ok: bool, message: string}
     */
    private function check(SharePointSetting $setting): array
    {
        try {
            $resolved = (new GraphClient($setting))->resolveSiteAndDrive();
        } catch (SharePointException $e) {
            $setting->forceFill([
                'status' => SharePointSetting::STATUS_ERROR,
                'checked_at' => now(),
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            report($e);
            $setting->forceFill([
                'status' => SharePointSetting::STATUS_ERROR,
                'checked_at' => now(),
                'last_error' => __('sharepoint.error.unexpected'),
            ])->save();

            return ['ok' => false, 'message' => __('sharepoint.error.unexpected')];
        }

        $setting->forceFill([
            'site_id'       => $resolved['site_id'],
            'drive_id'      => $resolved['drive_id'],
            'drive_web_url' => $resolved['drive_web_url'],
            'status'        => SharePointSetting::STATUS_OK,
            'checked_at'    => now(),
            'last_error'    => null,
        ])->save();

        return [
            'ok' => true,
            'message' => __('sharepoint.test_ok', [
                'site' => $resolved['site_name'] ?? '—',
                'library' => $resolved['drive_name'] ?? '—',
            ]),
        ];
    }

    private function cleanRoot(?string $root): ?string
    {
        $parts = array_filter(explode('/', (string) $root), static fn (string $p) => trim($p) !== '');

        return $parts === []
            ? null
            : implode('/', array_map(static fn (string $p) => SharePointNames::clean($p), $parts));
    }
}
