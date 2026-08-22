<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderConfirmations;
use App\Services\OrderConfirmationService;
use RuntimeException;

class OrderConfirmationsController extends Controller
{
    use NextPreviousTrait;

    public function __construct(
        protected OrderConfirmationService $orderConfirmationService,
    ) {}

    /**
     * Liste de tous les ARC, tous clients et toutes commandes confondus.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $base = rtrim(route('order.confirmations'), '/');

        $reactEndpoints = [
            'list' => route('order.confirmations.json.list'),
            'send' => $base . '/json/__ID__/send',
            'show' => $base . '/__ID__',
        ];

        $reactTrans = [
            'code'             => __('general_content.external_id_trans_key'),
            'label'            => __('general_content.label_trans_key'),
            'order'            => __('general_content.orders_trans_key'),
            'customer'         => __('general_content.companie_name_trans_key'),
            'revision'         => __('general_content.revision_trans_key'),
            'total'            => __('general_content.total_trans_key'),
            'status'           => __('general_content.status_trans_key'),
            'created_at'       => __('general_content.date_trans_key'),
            'sent_at'          => __('general_content.send_trans_key'),
            'actions'          => __('general_content.action_trans_key'),
            'view'             => __('general_content.show_trans_key'),
            'send'             => __('general_content.send_trans_key'),
            'status_draft'     => __('general_content.arc_status_in_progress_trans_key'),
            'status_sent'      => __('general_content.arc_status_sent_trans_key'),
            'status_accepted'  => __('general_content.arc_status_accepted_trans_key'),
            'status_superseded'=> __('general_content.arc_status_superseded_trans_key'),
            'search'           => __('general_content.search_trans_key'),
            'all'              => __('general_content.all_trans_key'),
            'no_data'          => __('general_content.no_data_trans_key'),
            'loading'          => __('general_content.notif_loading_trans_key'),
        ];

        return view('workflow.order-confirmations-index', compact('reactEndpoints', 'reactTrans'));
    }

    /**
     * @param OrderConfirmations $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(OrderConfirmations $id)
    {
        $id->load([
            'Order',
            'OrderConfirmationLines',
            'companie',
            'contact',
            'adresse',
            'UserManagement',
            'payment_condition',
            'payment_method',
            'delevery_method',
            'supersedes',
        ]);

        list($previousUrl, $nextUrl) = $this->getNextPrevious(new OrderConfirmations(), $id->id);

        // Les écarts ne concernent que l'ARC qui fait foi : comparer une commande à
        // un indice périmé n'aurait pas de sens.
        $diff = $id->is_current && $id->Order
            ? $this->orderConfirmationService->diffWithOrder($id->Order)
            : ['added' => [], 'removed' => [], 'modified' => []];

        $revisions = OrderConfirmations::where('order_id', $id->order_id)
            ->orderBy('id')
            ->get();

        return view('workflow.order-confirmations-show', [
            'Confirmation' => $id,
            'diff'         => $diff,
            'revisions'    => $revisions,
            'previousUrl'  => $previousUrl,
            'nextUrl'      => $nextUrl,
        ]);
    }

    /**
     * Émet l'ARC d'une commande depuis la fiche commande.
     *
     * Reprend le brouillon existant s'il y en a un — l'ARC est une photo de la
     * commande, on la refait plutôt que d'empiler les brouillons. Après un envoi,
     * le même bouton ouvre l'indice suivant.
     *
     * @param Orders $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromOrder(Orders $order)
    {
        $confirmation = $this->orderConfirmationService->createFromOrder($order, auth()->id());

        return redirect()
            ->route('order.confirmations.show', ['id' => $confirmation->id])
            ->with('success', __('general_content.arc_created_trans_key'));
    }

    // -------------------------------------------------------------------------
    // JSON endpoints
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search       = $request->get('search', '');
        $statusFilter = $request->filled('status') ? (int) $request->get('status') : null;
        $sortField    = in_array($request->get('sort', 'created_at'), ['code', 'label', 'created_at', 'statu', 'revision'], true)
            ? $request->get('sort', 'created_at')
            : 'created_at';
        $sortAsc      = $request->boolean('asc', false);

        $confirmations = OrderConfirmations::with(['Order:id,code', 'companie:id,label', 'OrderConfirmationLines'])
            ->when($search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('code', 'like', '%'.$search.'%')
                   ->orWhere('label', 'like', '%'.$search.'%')
                   ->orWhere('customer_reference', 'like', '%'.$search.'%')
            ))
            ->when($statusFilter !== null, fn ($q) => $q->where('statu', $statusFilter))
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate(15);

        return response()->json([
            'data' => $confirmations->map(fn ($c) => [
                'id'         => $c->id,
                'code'       => $c->code,
                'label'      => $c->label,
                'revision'   => $c->revision,
                'statu'      => (int) $c->statu,
                'is_current' => (bool) $c->is_current,
                'order'      => $c->Order ? ['id' => $c->Order->id, 'code' => $c->Order->code] : null,
                'order_url'  => $c->Order ? route('orders.show', ['id' => $c->Order->id]) : null,
                'customer'   => $c->companie?->label,
                'total'      => $c->formatted_total_price,
                'created_at' => $c->created_at?->format('d/m/Y'),
                'sent_at'    => $c->sent_at?->format('d/m/Y'),
                'url'        => route('order.confirmations.show', ['id' => $c->id]),
            ]),
            'meta' => [
                'total'        => $confirmations->total(),
                'per_page'     => $confirmations->perPage(),
                'current_page' => $confirmations->currentPage(),
                'last_page'    => $confirmations->lastPage(),
            ],
        ]);
    }

    /**
     * Envoi de l'ARC. Appelé en JSON depuis la liste React et en POST classique
     * depuis la fiche, d'où la double réponse.
     */
    public function sendJson(Request $request, int $id)
    {
        $confirmation = OrderConfirmations::findOrFail($id);

        try {
            $this->orderConfirmationService->send($confirmation);
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->route('order.confirmations.show', ['id' => $id])->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('general_content.arc_sent_trans_key')]);
        }

        return redirect()->route('order.confirmations.show', ['id' => $id])->with('success', __('general_content.arc_sent_trans_key'));
    }
}
