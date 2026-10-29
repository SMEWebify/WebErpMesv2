<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Orders;
use App\Services\Workflow\ContractReview;
use App\Services\Workflow\ContractReviewException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onglet « Revue de contrat » de la fiche commande : préparation, visa et
 * demande de changement (règles dans App\Services\Workflow\ContractReview).
 */
class OrderContractReviewController extends Controller
{
    public function __construct(private ContractReview $review)
    {
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
     * Demande de changement : hors visa, modifiable même revue figée
     * (un changement arrive justement après la revue).
     */
    public function change(Request $request, Orders $id): JsonResponse
    {
        $data = $request->validate([
            'change_requested_by' => 'nullable|exists:users,id',
            'change_reason' => 'nullable|string',
            'change_approved_at' => 'nullable|date',
        ]);

        $id->update($data);

        return response()->json(['state' => $this->review->state($id->fresh(), $request->user())]);
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
