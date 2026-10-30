<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\OrderAmendment;
use App\Models\Workflow\Orders;
use App\Services\Workflow\ContractReview;
use App\Services\Workflow\ContractReviewException;
use App\Services\Workflow\OrderAmendments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onglet « Revue de contrat » de la fiche commande : préparation, visa et
 * avenants (règles dans App\Services\Workflow\ContractReview).
 */
class OrderContractReviewController extends Controller
{
    public function __construct(
        private ContractReview $review,
        private OrderAmendments $amendments,
    ) {
    }

    public function show(Orders $id): JsonResponse
    {
        return response()->json(['state' => $this->review->state($id, auth()->user())]);
    }

    public function update(Request $request, Orders $id): JsonResponse
    {
        $data = $this->validateDraft($request);

        return $this->respond(fn () => $this->review->saveDraft($id, $data));
    }

    public function decide(Request $request, Orders $id): JsonResponse
    {
        $data = $this->validateDraft($request) + $request->validate([
            'decision' => 'required|in:' . implode(',', ContractReview::DECISIONS),
        ]);

        return $this->respond(fn () => $this->review->decide($id, $data['decision'], $request->user(), $data));
    }

    /**
     * Avenant : possible même revue figée (il arrive justement après la revue), il la rouvre.
     * Remplace l'ancienne demande de changement (colonnes change_* d'orders, lues seulement).
     */
    public function storeAmendment(Request $request, Orders $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:10000',
            'requested_by' => 'nullable|exists:users,id',
            'amendment_date' => 'nullable|date',
        ]);

        return $this->respond(function () use ($id, $data, $request) {
            $this->amendments->create($id, $data, $request->user());

            return $id->fresh();
        });
    }

    public function cancelAmendment(Request $request, Orders $id, OrderAmendment $amendment): JsonResponse
    {
        abort_unless((int) $amendment->order_id === (int) $id->id, 404);

        return $this->respond(function () use ($id, $amendment, $request) {
            $this->amendments->cancel($amendment, $request->user());

            return $id->fresh();
        });
    }

    private function validateDraft(Request $request): array
    {
        $rules = [
            'review_gaps' => 'sometimes|nullable|string|max:10000',
            'review_checklist' => 'sometimes|array',
        ];
        foreach (ContractReview::items() as $key) {
            $rules['review_checklist.' . $key] = 'sometimes|boolean';
        }

        return $request->validate($rules);
    }

    private function respond(callable $action): JsonResponse
    {
        try {
            $order = $action();
        } catch (ContractReviewException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json(['state' => $this->review->state($order, auth()->user())]);
    }
}
