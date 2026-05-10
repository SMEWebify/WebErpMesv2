<?php

namespace App\Http\Controllers;

use App\Services\RgpdSelfServiceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RgpdSelfServiceController extends Controller
{
    public function __construct(private RgpdSelfServiceService $service)
    {
    }

    public function index()
    {
        return view('rgpd.self-service', ['guard' => 'web']);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $payload = $this->service->exportUser($user);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'rgpd_user_'.$user->id.'_'.now()->format('Ymd_His').'.json';

        return response()->streamDownload(
            function () use ($json) { echo $json; },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function eraseRequest(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->submitEraseRequest('user', $user->id, $user->email, $validated['reason'] ?? null);

        return redirect()
            ->route('me.rgpd.index')
            ->with('success', __('Votre demande a été transmise. Un administrateur vous contactera sous 1 mois (délai légal art. 12 RGPD).'));
    }
}
