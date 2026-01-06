<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\N2PSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class N2PSettingsController extends Controller
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function edit(): View
    {
        $defaults = $this->defaults();
        $settings = $this->settings->getMany(array_keys($defaults), $defaults);

        return view('integrations.n2p-settings', [
            'settings' => $settings,
        ]);
    }

    public function update(N2PSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $defaults = $this->defaults();

        foreach (['n2p_enabled', 'n2p_send_tasks', 'n2p_verify_ssl'] as $booleanKey) {
            $validated[$booleanKey] = (bool) ($validated[$booleanKey] ?? false);
        }

        $payload = array_merge($defaults, $validated);

        $this->settings->setMany($payload);

        return redirect()
            ->route('admin.integrations.n2p')
            ->with('success', __('general_content.success_trans_key'));
    }

    private function defaults(): array
    {
        return [
            'n2p_enabled' => false,
            'n2p_base_url' => '',
            'n2p_api_token' => '',
            'n2p_send_on_order_status_from' => 'OPEN',
            'n2p_send_on_order_status_to' => 'IN_PROGRESS',
            'n2p_job_status_on_send' => 'released',
            'n2p_priority_default' => 3,
            'n2p_send_tasks' => true,
            'n2p_verify_ssl' => true,
        ];
    }
}
