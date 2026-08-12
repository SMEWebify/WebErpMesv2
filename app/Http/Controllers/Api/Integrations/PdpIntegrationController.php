<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Integrations\PdpInvoiceSubmission;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\PdpInvoiceService;
use App\Services\Integrations\Pdp\PdpManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dépôt et suivi d'une facture auprès de la PDP active, **quelle qu'elle soit**.
 *
 * Ces actions existaient sous le préfixe /integrations/qonto alors qu'elles
 * n'ont jamais rien eu de spécifique à Qonto : elles délèguent à
 * PdpInvoiceService, qui résout le driver configuré. Les voici à leur place.
 */
class PdpIntegrationController extends Controller
{
    public function __construct(
        private PdpInvoiceService $pdpInvoiceService,
        private PdpManager $manager,
    ) {}

    public function submitInvoice(Request $request, int $invoiceId): JsonResponse
    {
        $tenantId = auth('api')->id();
        $invoice  = Invoices::where('user_id', $tenantId)->findOrFail($invoiceId);

        abort_if(
            $invoice->invoice_type !== 1,
            422,
            'Seules les factures (type 1) peuvent être déposées sur la plateforme.'
        );

        abort_if(
            (int) $invoice->statu === 1,
            422,
            __('general_content.invoice_draft_no_facturx_trans_key')
        );

        try {
            $submission = $this->pdpInvoiceService->submit($invoice);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Aucune connexion active à la plateforme : configurez-la avant de déposer une facture.',
            ], 422);
        } catch (\RuntimeException $e) {
            // Données manquantes, document non conforme ou refus de la plateforme :
            // le message est rédigé pour l'utilisateur.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission]);
    }

    public function pollInvoice(Request $request, int $invoiceId): JsonResponse
    {
        $tenantId   = auth('api')->id();
        $submission = PdpInvoiceSubmission::where('tenant_id', $tenantId)
            ->where('invoice_id', $invoiceId)
            ->firstOrFail();

        $submission = $this->pdpInvoiceService->poll($submission);

        return response()->json(['submission' => $submission]);
    }

    /** État de l'intégration : driver actif et disponibilité. */
    public function status(): JsonResponse
    {
        return response()->json([
            'provider' => config('services.pdp.default'),
            'enabled'  => $this->manager->isEnabled(),
            'drivers'  => $this->manager->available(),
        ]);
    }
}
