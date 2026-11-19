<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrations\ExternalConfigurator;
use App\Models\Methods\MethodsFamilies;
use App\Services\Configurators\ConfiguratorClient;
use App\Services\Configurators\ConfiguratorException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Intégrations → Configurateurs : liaisons avec des configurateurs externes.
 * La clé API est chiffrée et jamais réaffichée (champ vide = conserver).
 */
class ConfiguratorSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $editing = $request->filled('edit') ? ExternalConfigurator::find((int) $request->query('edit')) : null;

        return view('integrations.configurators', [
            'configurators' => ExternalConfigurator::orderBy('label')->get(),
            'editing'       => $editing,
            'families'      => MethodsFamilies::orderBy('label')->get(['id', 'code', 'label']),
            'erp_origin'    => $request->getSchemeAndHttpHost(),
            'enabled'       => (bool) config('configurators.enabled'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $configurator = new ExternalConfigurator();
        $this->fill($request, $configurator);

        return $this->checkAndRedirect($configurator);
    }

    public function update(Request $request, ExternalConfigurator $configurator): RedirectResponse
    {
        $this->fill($request, $configurator);

        return $this->checkAndRedirect($configurator);
    }

    /** Appelle GET /api/v1/ping et garde le résultat sur la liaison. */
    public function test(ExternalConfigurator $configurator): JsonResponse
    {
        $result = $this->check($configurator);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    private function fill(Request $request, ExternalConfigurator $configurator): void
    {
        $validated = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('external_configurators', 'code')->ignore($configurator->id)],
            'label'            => 'required|string|max:255',
            'base_url'         => 'required|url:https,http|max:255',
            'api_key'          => 'nullable|string|max:500',
            'display'          => ['required', Rule::in([ExternalConfigurator::DISPLAY_IFRAME, ExternalConfigurator::DISPLAY_TAB])],
            'document_types'   => 'required|array|min:1',
            'document_types.*' => [Rule::in(ExternalConfigurator::DOCUMENT_TYPES)],
            'families'         => 'nullable|array',
            'families.*'       => 'integer|exists:methods_families,id',
            'price_mode'       => ['required', Rule::in([ExternalConfigurator::PRICE_GROSS, ExternalConfigurator::PRICE_NET])],
        ]);

        $configurator->fill([
            'code'           => $validated['code'],
            'label'          => $validated['label'],
            'base_url'       => rtrim(trim($validated['base_url']), '/'),
            'display'        => $validated['display'],
            'document_types' => array_values($validated['document_types']),
            'families'       => array_values(array_map('intval', $validated['families'] ?? [])),
            'price_mode'     => $validated['price_mode'],
            'active'         => $request->boolean('active'),
        ]);

        // Laissée vide : la clé existante est conservée.
        if (filled($validated['api_key'] ?? null)) {
            $configurator->api_key = trim($validated['api_key']);
        }

        $configurator->save();
    }

    private function checkAndRedirect(ExternalConfigurator $configurator): RedirectResponse
    {
        $result = $configurator->hasKey() ? $this->check($configurator) : ['ok' => false, 'message' => __('configurators.admin.no_key')];

        return redirect()
            ->route('admin.integrations.configurators.index')
            ->with($result['ok'] ? 'success' : 'warning', __('configurators.admin.saved') . ' ' . $result['message']);
    }

    /** @return array{ok: bool, message: string} */
    private function check(ExternalConfigurator $configurator): array
    {
        try {
            $info = (new ConfiguratorClient($configurator))->ping();
            $configurator->forceFill(['last_check_at' => now(), 'last_error' => null])->save();

            return ['ok' => true, 'message' => __('configurators.admin.test_ok', [
                'name'    => (string) ($info['name'] ?? $configurator->label),
                'version' => (string) ($info['version'] ?? '?'),
            ])];
        } catch (ConfiguratorException $e) {
            $configurator->forceFill(['last_check_at' => now(), 'last_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
