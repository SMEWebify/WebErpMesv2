<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrations\Nest2BendSetting;
use App\Services\Integrations\Nest2Bend\Nest2BendAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Liaison NEST2BEND — écran d'admin unique (Intégrations → NEST2BEND).
 *
 * La clé API est chiffrée en base et jamais réaffichée : l'écran n'en montre que le
 * préfixe et les 4 derniers caractères, un champ laissé vide la conserve.
 */
class Nest2BendSettingsController extends Controller
{
    public function __construct(private readonly Nest2BendAccess $access) {}

    public function index(Request $request): View
    {
        $setting = Nest2BendSetting::current();

        return view('integrations.nest2bend', [
            'setting'    => $setting,
            'masked_key' => $setting?->maskedKey(),
            'state'      => $this->access->state($setting),
            // Origine que NEST2BEND doit autoriser (sinon 422, et l'iframe est refusée).
            'erp_origin' => $request->getSchemeAndHttpHost(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = Nest2BendSetting::current() ?? new Nest2BendSetting(['status' => Nest2BendSetting::STATUS_NONE]);

        $validated = $request->validate([
            'base_url'  => ['required', 'url:https,http', 'max:255'],
            // Laissée vide → on garde la clé existante.
            'api_key'   => ['nullable', 'string', 'starts_with:n2b_', 'max:255'],
            'clear_key' => ['nullable', 'boolean'],
        ]);

        $setting->base_url = rtrim(trim($validated['base_url']), '/');

        if ($request->boolean('clear_key')) {
            $setting->api_key = null;
        } elseif (filled($validated['api_key'] ?? null)) {
            $setting->api_key = trim($validated['api_key']);
            $setting->status = Nest2BendSetting::STATUS_UNKNOWN;
        }

        $setting->save();

        if (! $setting->hasKey()) {
            $setting->forceFill([
                'status' => Nest2BendSetting::STATUS_NONE, 'etat' => null, 'peut_ecrire' => false,
                'echeance' => null, 'societe' => null, 'modules' => null, 'presses_max' => null,
                'checked_at' => null, 'last_error' => null,
            ])->save();

            return redirect()->route('admin.integrations.nest2bend.index')->with('success', __('nest2bend.saved'));
        }

        $result = $this->access->refresh($setting);

        return redirect()
            ->route('admin.integrations.nest2bend.index')
            ->with($result['ok'] ? 'success' : 'warning', __('nest2bend.saved') . ' ' . $result['message']);
    }

    /**
     * Relit GET /api/v1/compte et met l'état de la carte à jour.
     */
    public function test(): JsonResponse
    {
        $setting = Nest2BendSetting::current();
        $result = $this->access->refresh($setting);
        $state = $this->access->state($setting?->fresh());

        return response()->json([
            'ok'      => $result['ok'],
            'message' => $result['message'],
            'status'  => $state['status'],
            'societe' => $state['societe'],
            'warning' => $state['warning']
                ? __('nest2bend.state.warning', [
                    'etat'     => __('nest2bend.etats.' . $state['etat']),
                    'echeance' => $state['echeance']?->format('d/m/Y') ?? '—',
                ])
                : null,
        ]);
    }
}
