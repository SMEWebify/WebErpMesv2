<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Crm\InboundLeadService;
use Illuminate\Http\Request;

/**
 * Entrée des demandes clients pour un outil d'automatisation (n8n…) qui relève
 * la boîte mail : un appel = un e-mail = une piste. Le rapprochement avec le
 * client, la GED et la notification restent dans l'ERP (InboundLeadService).
 *
 * Jeton Sanctum de l'utilisateur d'intégration ; un jeton restreint doit porter
 * la capacité « leads:inbound ». Un appel rejoué (même Message-ID) rend la piste
 * existante avec un 200 au lieu d'un 201.
 */
class InboundLeadController extends Controller
{
    public function store(Request $request, InboundLeadService $inbound)
    {
        abort_unless($request->user()?->tokenCan('leads:inbound'), 403);

        $maxKb = (int) config('leads.inbound.max_kilobytes', 20480);

        $validated = $request->validate([
            'from_email'    => 'required|email|max:255',
            'from_name'     => 'nullable|string|max:255',
            'subject'       => 'nullable|string|max:1000',
            'received_at'   => 'nullable|date',
            'body'          => 'nullable|string',
            'message_id'    => 'nullable|string|max:1000',
            'owner_email'   => 'nullable|email',
            'original'      => ['nullable', 'file', 'extensions:eml,msg', 'max:' . $maxKb],
            'attachments'   => 'nullable|array|max:20',
            'attachments.*' => 'file|max:' . $maxKb,
        ]);

        $ownerEmail = $validated['owner_email'] ?? config('leads.inbound.default_owner_email');
        $ownerId = $ownerEmail ? User::where('email', $ownerEmail)->value('id') : null;

        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'data' => (string) file_get_contents($file->getRealPath()),
            ];
        }

        $original = null;
        if ($request->hasFile('original')) {
            $file = $request->file('original');
            $original = [
                'name' => $file->getClientOriginalName(),
                'mime' => mb_strtolower($file->getClientOriginalExtension()) === 'msg' ? 'application/vnd.ms-outlook' : 'message/rfc822',
                'data' => (string) file_get_contents($file->getRealPath()),
            ];
        }

        [$lead, $created] = $inbound->receive($validated, $attachments, $original, $ownerId);

        return response()->json([
            'id'      => $lead->id,
            'created' => $created,
            'url'     => route('leads.show', ['id' => $lead->id]),
        ], $created ? 201 : 200);
    }
}
