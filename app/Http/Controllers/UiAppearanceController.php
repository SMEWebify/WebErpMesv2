<?php

namespace App\Http\Controllers;

use App\Support\UiTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UiAppearanceController extends Controller
{
    /**
     * Enregistre l'apparence choisie par l'utilisateur connecté (onglet
     * « Apparence » du profil). Un champ vide revient à la valeur par défaut.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ui_preset' => ['nullable', 'string', Rule::in(array_keys(UiTheme::PRESETS))],
            'ui_primary' => ['nullable', 'string', Rule::in(UiTheme::PRIMARIES)],
        ]);

        $user = $request->user();
        $user->ui_preset = $validated['ui_preset'] ?? null;
        $user->ui_primary = $validated['ui_primary'] ?? null;
        $user->save();

        return redirect()
            ->to(url()->previous().'#Appearance')
            ->with('success', __('appearance.saved'));
    }
}
