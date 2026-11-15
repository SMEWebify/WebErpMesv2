<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\TextRewriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * « Réécrire sans fautes » : renvoie une proposition, n'enregistre rien.
 * Le texte vient d'un champ que l'utilisateur est en train de saisir.
 */
class TextRewriteController extends Controller
{
    public function rewrite(Request $request, TextRewriter $rewriter): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:' . TextRewriter::MAX_INPUT,
        ]);

        try {
            $text = $rewriter->rewrite($validated['text']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['text' => $text]);
    }
}
