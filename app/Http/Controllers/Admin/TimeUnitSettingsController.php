<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use App\Support\TimeDisplay;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réglage société « unité d'affichage des temps opératoires » (heures / minutes).
 * Ne touche ni le stockage ni l'API : voir App\Support\TimeDisplay.
 */
class TimeUnitSettingsController extends Controller
{
    public function index()
    {
        return view('admin.time-unit-settings', ['unit' => TimeDisplay::unit()]);
    }

    public function update(Request $request, SettingsService $settings)
    {
        $data = $request->validate([
            'time_unit' => ['required', Rule::in([TimeDisplay::HOURS, TimeDisplay::MINUTES])],
        ]);

        $settings->set(TimeDisplay::SETTING_KEY, $data['time_unit']);

        return redirect()
            ->route('admin.time.unit.settings')
            ->with('success', __('general_content.time_unit_saved_trans_key'));
    }
}
