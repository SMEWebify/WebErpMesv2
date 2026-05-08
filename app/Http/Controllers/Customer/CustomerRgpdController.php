<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Services\RgpdSelfServiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerRgpdController extends Controller
{
    public function __construct(private RgpdSelfServiceService $service)
    {
    }

    public function index()
    {
        return view('customer.rgpd-self-service');
    }

    public function export(): StreamedResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 401);

        $contact = CompaniesContacts::with('companie')->findOrFail($customer->id);

        $payload = $this->service->exportContact($contact);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'rgpd_contact_'.$contact->id.'_'.now()->format('Ymd_His').'.json';

        return response()->streamDownload(
            function () use ($json) { echo $json; },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function eraseRequest(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 401);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->submitEraseRequest('contact', $customer->id, $customer->mail, $validated['reason'] ?? null);

        return redirect()
            ->route('customer.rgpd.index')
            ->with('success', __('Votre demande a été transmise. Un administrateur vous contactera sous 1 mois (délai légal art. 12 RGPD).'));
    }
}
