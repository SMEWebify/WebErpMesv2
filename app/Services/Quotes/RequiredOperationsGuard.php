<?php

namespace App\Services\Quotes;

use App\Models\User;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\RequiredOperationsOverride;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôle des opérations obligatoires à la conversion d'un devis en commande.
 *
 *  - rien ne manque : la conversion continue ;
 *  - il manque des opérations : refus 422 avec la liste, sauf passe-droit
 *    motivé d'un porteur de `required-operations-override`, enregistré avec
 *    la commande créée (même schéma que CreditLimitGuard).
 *
 * Sur le devis lui-même, l'absence n'est qu'un avertissement.
 */
class RequiredOperationsGuard
{
    public const OVERRIDE_PERMISSION = 'required-operations-override';

    /** Motif retenu après un passe-droit accepté, à tracer avec le document créé. */
    private ?string $overrideReason = null;

    /** @var array<int, array> lignes et opérations manquantes du dernier contrôle */
    private array $missing = [];

    public function __construct(private readonly RequiredOperationsChecker $checker) {}

    /**
     * Point de contrôle de l'écran React. Null : continuer. Sinon, la réponse
     * à renvoyer telle quelle.
     *
     * @param  int[]  $lineIds  lignes converties
     */
    public function guardJson(Request $request, Quotes $quote, array $lineIds): ?JsonResponse
    {
        $this->overrideReason = null;
        $this->missing        = $this->checker->missing($quote, $lineIds);

        if ($this->missing === []) {
            return null;
        }

        $refusal = $this->decide($request);
        if ($refusal === null) {
            return null;
        }

        return response()->json([
            'message'             => $refusal['message'],
            'error'               => $refusal['message'],
            'errors'              => $refusal['errors'] ?? null,
            'required_operations' => $this->payload($request->user()),
        ], $refusal['status']);
    }

    /** Trace le passe-droit, s'il y en a eu un, avec la commande créée. */
    public function recordOverride(Quotes $quote, Model $document): void
    {
        if ($this->overrideReason === null) {
            return;
        }

        RequiredOperationsOverride::create([
            'quotes_id'          => $quote->id,
            'document_type'      => $document->getMorphClass(),
            'document_id'        => $document->getKey(),
            'affair_category_id' => $quote->affair_category_id,
            'user_id'            => auth()->id(),
            'reason'             => $this->overrideReason,
            'missing'            => $this->missing,
        ]);

        $this->overrideReason = null;
    }

    /**
     * Données de la fenêtre de passe-droit, textes compris (comme la fenêtre
     * d'encours, elle n'a pas les traductions de l'écran qui l'ouvre).
     */
    public function payload(?User $user): array
    {
        return [
            'missing'      => $this->missing,
            'can_override' => (bool) $user?->can(self::OVERRIDE_PERMISSION),
            'texts'        => [
                'title'         => __('affairs.required_operations.blocked_title'),
                'message'       => __('affairs.required_operations.blocked'),
                'line'          => __('affairs.required_operations.line'),
                'missing'       => __('affairs.required_operations.missing'),
                'no_permission' => __('affairs.required_operations.no_permission'),
                'reason_label'  => __('affairs.required_operations.override_reason'),
                'reason_help'   => __('affairs.required_operations.override_reason_help'),
                'override'      => __('affairs.required_operations.override'),
                'cancel'        => __('general_content.cancel_trans_key'),
            ],
        ];
    }

    /**
     * @return array{status:int, message:string, errors?:array}|null
     */
    private function decide(Request $request): ?array
    {
        if (! $request->boolean('required_operations_override')) {
            return ['status' => 422, 'message' => __('affairs.required_operations.blocked')];
        }

        if (! $request->user()?->can(self::OVERRIDE_PERMISSION)) {
            return ['status' => 403, 'message' => __('affairs.required_operations.no_permission')];
        }

        $reason = trim((string) $request->input('required_operations_override_reason'));
        if (mb_strlen($reason) < 3) {
            $message = __('affairs.required_operations.override_reason_required');

            return ['status' => 422, 'message' => $message, 'errors' => ['required_operations_override_reason' => [$message]]];
        }

        $this->overrideReason = mb_substr($reason, 0, 2000);

        return null;
    }
}
