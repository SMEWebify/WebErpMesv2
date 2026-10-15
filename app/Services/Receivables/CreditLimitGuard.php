<?php

namespace App\Services\Receivables;

use App\Enums\CreditBlockLevel;
use App\Models\Companies\CreditLimitOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Contrôle du plafond d'encours à la création d'une commande ou d'un BL.
 *
 *  - pas de plafond, ou niveau « aucun » : rien ne change ;
 *  - alerte : le document est créé, un avertissement suit sur la page suivante ;
 *  - blocage : refus (409), sauf passe-droit motivé d'un porteur de
 *    `credit-limit-override`, enregistré avec le document créé.
 */
class CreditLimitGuard
{
    public const OVERRIDE_PERMISSION = 'credit-limit-override';

    public function __construct(private readonly ReceivablesService $receivables) {}

    /**
     * @param float $additional montant TTC que le document ajoute à l'encours
     *        (une commande convertie d'un devis) ; 0 pour un BL, déjà compté
     *        dans la commande.
     */
    public function check(?int $companyId, float $additional = 0.0): CreditCheck
    {
        if (! $companyId) {
            return CreditCheck::clear();
        }

        // Lecture légère d'abord : aucun calcul pour une société sans plafond.
        $company = DB::table('companies')->where('id', $companyId)->first(['credit_limit', 'credit_block_level']);
        if ($company === null || $company->credit_limit === null
            || CreditBlockLevel::fromColumn($company->credit_block_level) === CreditBlockLevel::None) {
            return CreditCheck::clear();
        }

        return new CreditCheck($this->receivables->exposure($companyId), round($additional, 2));
    }

    /**
     * Point de contrôle des écrans React. Null : continuer (avec, en mode
     * alerte, un avertissement posé en session pour la page suivante).
     * Sinon, la réponse à renvoyer telle quelle.
     */
    public function guardJson(Request $request, CreditCheck $check): ?JsonResponse
    {
        $refusal = $this->decide($request, $check);
        if ($refusal === null) {
            return null;
        }

        return response()->json(
            ['message' => $refusal['message'], 'errors' => $refusal['errors'] ?? null, 'credit_limit' => $this->payload($check, $request->user())],
            $refusal['status']
        );
    }

    /**
     * Même contrôle pour un formulaire Blade : null pour continuer, sinon le
     * message d'erreur à afficher au retour sur le formulaire.
     */
    public function guardForm(Request $request, CreditCheck $check): ?string
    {
        return $this->decide($request, $check)['message'] ?? null;
    }

    /** Trace le passe-droit, s'il y en a eu un, sur le document créé. */
    public function recordOverride(CreditCheck $check, Model $document, ?int $companyId): void
    {
        if ($check->overrideReason === null || $check->exposure === null || ! $companyId) {
            return;
        }

        CreditLimitOverride::create([
            'companies_id'  => $companyId,
            'document_type' => $document->getMorphClass(),
            'document_id'   => $document->getKey(),
            'user_id'       => auth()->id(),
            'reason'        => $check->overrideReason,
            'outstanding'   => $check->exposure->total(),
            'credit_limit'  => $check->exposure->limit,
            'projected'     => $check->projected(),
        ]);
    }

    /**
     * Données de la fenêtre de dépassement côté écran, textes compris : la
     * fenêtre s'ouvre depuis plusieurs écrans qui n'ont pas les mêmes
     * traductions.
     */
    public function payload(CreditCheck $check, ?User $user): array
    {
        return [
            'level'        => $check->level()->value,
            'outstanding'  => $check->exposure?->total(),
            'limit'        => $check->exposure?->limit,
            'additional'   => $check->additional,
            'projected'    => $check->projected(),
            'can_override' => (bool) $user?->can(self::OVERRIDE_PERMISSION),
            'currency'     => app('Factory')->curency ?? 'EUR',
            'texts'        => [
                'outstanding'    => __('receivables.credit.outstanding'),
                'document'       => __('receivables.credit.this_document'),
                'projected'      => __('receivables.credit.projected'),
                'limit'          => __('receivables.credit.limit'),
                'title'          => __('receivables.credit.blocked_title'),
                'message'        => $this->message($check, 'receivables.credit.blocked'),
                'no_permission'  => __('receivables.credit.no_permission'),
                'reason_label'   => __('receivables.credit.override_reason'),
                'reason_help'    => __('receivables.credit.override_reason_help'),
                'override'       => __('receivables.credit.override'),
                'cancel'         => __('general_content.cancel_trans_key'),
            ],
        ];
    }

    /**
     * @return array{status:int, message:string, errors?:array}|null
     */
    private function decide(Request $request, CreditCheck $check): ?array
    {
        if (! $check->exceeded()) {
            return null;
        }

        if ($check->level() === CreditBlockLevel::Warn) {
            session()->flash('warning', $this->message($check, 'receivables.credit.warning'));

            return null;
        }

        if (! $request->boolean('credit_override')) {
            return ['status' => 409, 'message' => $this->message($check, 'receivables.credit.blocked')];
        }

        if (! $request->user()?->can(self::OVERRIDE_PERMISSION)) {
            return ['status' => 403, 'message' => __('receivables.credit.no_permission')];
        }

        $reason = trim((string) $request->input('credit_override_reason'));
        if (mb_strlen($reason) < 3) {
            $message = __('receivables.credit.override_reason_required');

            return ['status' => 422, 'message' => $message, 'errors' => ['credit_override_reason' => [$message]]];
        }

        $check->overrideReason = mb_substr($reason, 0, 2000);

        return null;
    }

    private function message(CreditCheck $check, string $key): string
    {
        return __($key, [
            'outstanding' => $this->money($check->exposure?->total() ?? 0),
            'projected'   => $this->money($check->projected()),
            'limit'       => $this->money($check->exposure?->limit ?? 0),
        ]);
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, app('Factory')->curency ?? 'EUR', config('app.locale'));
    }
}
