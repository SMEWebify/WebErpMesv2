<?php

namespace App\Services\Integrations\Nest2Bend;

use App\Models\Integrations\Nest2BendSetting;
use App\Services\Integrations\Nest2Bend\Data\AccountState;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;

/**
 * État de la liaison NEST2BEND, tel que les écrans l'affichent.
 *
 * L'ERP ne décide rien de l'abonnement : il interprète GET /api/v1/compte. Le dernier état
 * lu est gardé en base (Nest2BendSetting) et rafraîchi à l'enregistrement et au test de la
 * carte, une fois par jour (`wem:nest2bend:refresh`) et quand embed-sessions répond 403.
 * Aucun écran n'appelle NEST2BEND pour s'afficher.
 *
 * | Statut   | Écran commercial | Boutons « Simuler »            |
 * |----------|------------------|--------------------------------|
 * | none     | oui              | masqués                        |
 * | unknown  | oui              | masqués (clé jamais vérifiée)  |
 * | invalid  | oui              | masqués, carte en erreur       |
 * | expired  | non              | désactivés, lien vers NEST2BEND |
 * | active   | non              | actifs ; avertissement + échéance si essai / grâce |
 */
class Nest2BendAccess
{
    /** États NEST2BEND actifs mais à surveiller (avertissement avec l'échéance). */
    public const WARNING_STATES = ['essai', 'grace'];

    public function __construct(private readonly Nest2BendGatewayFactory $factory) {}

    public function setting(): ?Nest2BendSetting
    {
        return Nest2BendSetting::current();
    }

    /**
     * @return array{status: string, configured: bool, show_commercial: bool, can_simulate: bool,
     *               expired: bool, warning: bool, etat: ?string, echeance: ?\Illuminate\Support\Carbon,
     *               societe: ?string, modules: array, presses_max: ?int, checked_at: ?\Illuminate\Support\Carbon,
     *               last_error: ?string, app_url: ?string, origin: ?string}
     */
    public function state(?Nest2BendSetting $setting = null): array
    {
        $setting ??= $this->setting();
        $status = $setting?->hasKey() ? ($setting->status ?: Nest2BendSetting::STATUS_UNKNOWN) : Nest2BendSetting::STATUS_NONE;

        $active = $status === Nest2BendSetting::STATUS_ACTIVE;

        return [
            'status'          => $status,
            'configured'      => (bool) $setting?->hasKey(),
            'show_commercial' => in_array($status, [Nest2BendSetting::STATUS_NONE, Nest2BendSetting::STATUS_UNKNOWN, Nest2BendSetting::STATUS_INVALID], true),
            'can_simulate'    => $active,
            'expired'         => $status === Nest2BendSetting::STATUS_EXPIRED,
            'warning'         => $active && in_array($setting?->etat, self::WARNING_STATES, true),
            'etat'            => $setting?->etat,
            'echeance'        => $setting?->echeance,
            'societe'         => $setting?->societe,
            'modules'         => $setting?->modules ?? [],
            'presses_max'     => $setting?->presses_max,
            'checked_at'      => $setting?->checked_at,
            'last_error'      => $setting?->last_error,
            'app_url'         => $setting?->origin(),
            'origin'          => $setting?->origin(),
        ];
    }

    public function canSimulate(): bool
    {
        return $this->state()['can_simulate'];
    }

    /**
     * Relit /compte et enregistre l'état. Une panne technique (réseau, 5xx) garde le
     * dernier état connu : une coupure de NEST2BEND ne doit pas griser les boutons.
     *
     * @return array{ok: bool, message: string}
     */
    public function refresh(?Nest2BendSetting $setting = null): array
    {
        $setting ??= $this->setting();

        if (! $setting?->hasKey()) {
            $setting?->forceFill(['status' => Nest2BendSetting::STATUS_NONE, 'last_error' => null])->save();

            return ['ok' => false, 'message' => __('nest2bend.state.none')];
        }

        try {
            $account = $this->factory->make($setting)->account();
        } catch (Nest2BendException $e) {
            if ($e->is(Nest2BendException::UNAUTHORIZED)) {
                $setting->forceFill([
                    'status'      => Nest2BendSetting::STATUS_INVALID,
                    'peut_ecrire' => false,
                    'checked_at'  => now(),
                    'last_error'  => null,
                ])->save();

                return ['ok' => false, 'message' => __('nest2bend.state.invalid')];
            }

            $setting->forceFill([
                'checked_at' => now(),
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $this->apply($setting, $account);

        return [
            'ok'      => $account->peutEcrire,
            'message' => $account->peutEcrire
                ? __('nest2bend.test_ok', ['societe' => $account->societe ?? '—'])
                : __('nest2bend.state.expired'),
        ];
    }

    /**
     * Enregistre des droits lus sur NEST2BEND (/compte, ou `droits` d'un 403 d'embed-sessions).
     * La société n'est remplacée que si la réponse la porte (le 403 ne la donne pas).
     */
    public function apply(Nest2BendSetting $setting, AccountState $account): void
    {
        $setting->forceFill([
            'status'      => $account->peutEcrire ? Nest2BendSetting::STATUS_ACTIVE : Nest2BendSetting::STATUS_EXPIRED,
            'etat'        => $account->etat,
            'peut_ecrire' => $account->peutEcrire,
            'echeance'    => $account->echeance,
            'societe'     => $account->societe ?? $setting->societe,
            'modules'     => $account->modules,
            'presses_max' => $account->pressesMax,
            'checked_at'  => now(),
            'last_error'  => null,
        ])->save();
    }
}
