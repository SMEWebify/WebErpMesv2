<?php

namespace App\Http\Controllers;

use App\Support\SafeHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailSignatureController extends Controller
{
    /**
     * Enregistre la signature e-mail de l'utilisateur connecté (onglet
     * « E-mail » du profil). Vide = plus de signature.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Une image collée part en base64 : la borne évite une ligne énorme.
            'email_signature' => ['nullable', 'string', 'max:60000'],
        ]);

        $signature = trim(SafeHtml::clean($validated['email_signature'] ?? ''));
        // Quill laisse « <p><br></p> » dans un éditeur vidé.
        if (trim(strip_tags($signature, '<img>')) === '') {
            $signature = null;
        }

        $user = $request->user();
        $user->email_signature = $signature;
        $user->save();

        return redirect()
            ->to(url()->previous().'#EmailSignature')
            ->with('success', __('mail_personalization.signature_saved'));
    }
}
