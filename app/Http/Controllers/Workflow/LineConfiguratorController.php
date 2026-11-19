<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingVat;
use App\Models\Integrations\ConfiguratorSession;
use App\Models\Integrations\ExternalConfigurator;
use App\Models\Products\Products;
use App\Models\Workflow\Orders;
use App\Services\Configurators\ConfiguratorException;
use App\Services\Configurators\ConfiguratorService;
use App\Services\Receivables\CreditLimitGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Configurateur externe depuis les lignes d'un devis ou d'une commande :
 * ouverture de la session, aperçu du résultat relu par le serveur, création des
 * lignes après confirmation (docs/conception/configurateurs-externes.md § 3).
 */
class LineConfiguratorController extends Controller
{
    public function __construct(private readonly ConfiguratorService $service) {}

    public function open(Request $request, string $type, int $documentId): JsonResponse
    {
        abort_unless(config('configurators.enabled'), 404);

        $validated = $request->validate([
            'configurator_id' => 'required|integer',
            'product_id'      => 'nullable|integer|exists:products,id',
            'quantity'        => 'nullable|numeric|min:0',
        ]);

        $configurator = ExternalConfigurator::availableFor($type)->firstWhere('id', (int) $validated['configurator_id']);
        abort_if($configurator === null, 404);

        try {
            $document = ConfiguratorService::document($type, $documentId);

            return response()->json($this->service->open(
                $configurator,
                $type,
                $document,
                $request->user(),
                isset($validated['product_id']) ? Products::find($validated['product_id']) : null,
                (float) ($validated['quantity'] ?? 1) ?: 1,
                $request->getSchemeAndHttpHost(),
            ));
        } catch (ConfiguratorException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Aperçu : relit le résultat chez le configurateur, n'écrit aucune ligne. */
    public function result(Request $request, string $session): JsonResponse
    {
        $validated = $request->validate(['result_id' => 'required|string|max:255']);
        $model = $this->session($request, $session);

        try {
            return response()->json(['result' => $this->service->preview($model, $validated['result_id'])]);
        } catch (ConfiguratorException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function apply(Request $request, string $session): JsonResponse
    {
        $model = $this->session($request, $session);

        // Commande : les lignes ajoutées passent par le plafond d'encours, comme une saisie.
        $guard = null;
        $check = null;
        if ($model->document_type === 'order' && is_array($model->result)) {
            $order = Orders::findOrFail($model->document_id);
            $rate = (float) (AccountingVat::getDefault()?->rate ?? 0);
            $additional = collect($model->result['lines'] ?? [])->sum(fn ($l) => $l['net_total'] * (1 + $rate / 100));

            $guard = app(CreditLimitGuard::class);
            $check = $guard->check((int) $order->type === 1 ? (int) $order->companies_id : null, (float) $additional);
            if ($refusal = $guard->guardJson($request, $check)) {
                return $refusal;
            }
        }

        try {
            $written = $this->service->apply($model);
        } catch (ConfiguratorException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        if ($guard && isset($order)) {
            $guard->recordOverride($check, $order, $order->companies_id);
        }

        return response()->json([
            'created'  => count($written['lines']),
            'warnings' => $written['warnings'],
            'message'  => trans_choice('configurators.created', count($written['lines']), ['count' => count($written['lines'])]),
        ], 201);
    }

    public function cancel(Request $request, string $session): JsonResponse
    {
        $this->service->cancel($this->session($request, $session));

        return response()->json(['ok' => true]);
    }

    /**
     * Retour d'un configurateur ouvert dans un onglet : prévient l'écran d'origine
     * (même origine que l'ERP) puis referme l'onglet. Ne lit ni n'écrit rien.
     */
    public function returnPage(Request $request, string $session)
    {
        $model = $this->session($request, $session);
        $resultId = mb_substr((string) $request->query('result_id', ''), 0, 255);

        return view('configurators.return', [
            'session'  => $model->uuid,
            'resultId' => $resultId,
        ]);
    }

    /** Session de l'utilisateur connecté ; celle d'un autre n'existe pas pour lui. */
    private function session(Request $request, string $uuid): ConfiguratorSession
    {
        return ConfiguratorSession::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
