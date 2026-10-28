<?php

namespace App\Http\Controllers\Purchases;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Purchases\PurchaseReceipt;
use App\Services\Purchases\SupplierReceiptRating;

/**
 * Notation rapide du fournisseur à la réception : fenêtre facultative de la fiche réception
 * (resources/js/components/purchases/ReceiptSupplierRating.jsx).
 */
class PurchaseReceiptRatingController extends Controller
{
    public function __construct(private SupplierReceiptRating $ratings)
    {
    }

    public function store(Request $request, PurchaseReceipt $receipt): JsonResponse
    {
        $validated = $request->validate([
            'conformity' => 'required|integer|between:1,5',
            'delay'      => 'required|integer|between:1,5',
            'documents'  => 'required|integer|between:1,5',
            'comment'    => 'nullable|string|max:2000',
        ]);

        try {
            $this->ratings->record($receipt, $validated, $validated['comment'] ?? null);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('supplier_rating.saved'),
            'state'   => self::state($this->ratings, $receipt->fresh()),
        ]);
    }

    /**
     * Données de la fenêtre pour une réception : note actuelle, délai proposé, NC, note glissante.
     */
    public static function state(SupplierReceiptRating $ratings, PurchaseReceipt $receipt): array
    {
        $rating = $ratings->ratingFor($receipt);

        return [
            'rated'           => $rating !== null,
            'scores'          => $rating ? $ratings->scoresOf($rating) : null,
            'comment'         => $rating?->comment,
            'rated_at'        => $rating?->updated_at?->format('d/m/Y'),
            'can_rate'        => $receipt->PurchaseReceiptLines()->exists(),
            'days_late'       => $ratings->daysLate($receipt),
            'suggested_delay' => $ratings->suggestedDelayScore($receipt),
            'has_nc'          => $ratings->hasNonConformity($receipt),
            'nc_quality_score' => (int) config('supplier_rating.nc_quality_score', 1),
            'rolling'         => $ratings->rollingFor((int) $receipt->companies_id),
        ];
    }

    /** Textes de la fenêtre (resources/lang/{fr,en}/supplier_rating.php). */
    public static function texts(): array
    {
        return __('supplier_rating.window');
    }
}
