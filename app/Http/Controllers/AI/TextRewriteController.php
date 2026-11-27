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
        // `html` : contenu d'un éditeur riche (e-mail, commentaire), corrigé sans toucher à sa mise en forme.
        $validated = $request->validate([
            'text' => 'required_without:html|nullable|string|max:' . TextRewriter::MAX_INPUT,
            'html' => 'required_without:text|nullable|string|max:' . TextRewriter::MAX_HTML,
        ]);

        try {
            if (isset($validated['html'])) {
                return response()->json($rewriter->rewriteHtml($validated['html']));
            }
            $text = $rewriter->rewrite($validated['text']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['text' => $text]);
    }
}
