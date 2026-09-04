<?php

namespace App\Http\Controllers;

use App\Support\ThemeMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeModeController extends Controller
{
    /**
     * Persiste en session le mode d'affichage choisi depuis la navbar.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:'.implode(',', ThemeMode::MODES)],
        ]);

        return response()->json([
            'mode' => ThemeMode::set($validated['mode']),
        ]);
    }
}
