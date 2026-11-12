<?php

namespace App\Services\Integrations\Nest2Bend;

use App\Models\Integrations\Nest2BendSetting;
use App\Models\User;
use App\Models\Workflow\BendingSimulation;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Services\Integrations\Nest2Bend\Data\BendingResult;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;
use Illuminate\Support\Facades\DB;

/**
 * Bouton « Simuler » d'une ligne de devis.
 *
 *   start()    POST /api/v1/embed-sessions avec le STEP de la GED, côté serveur (la clé ne
 *              sort pas) ; trace une session `pending`. L'URL d'iframe (code 60 s, usage
 *              unique) part au navigateur et n'est jamais stockée.
 *   complete() reçoit le resultId signalé par l'iframe (postMessage), relit
 *              GET /api/v1/results/{id} et n'accepte le résultat que s'il correspond à une
 *              session ouverte ici, pour cette ligne et cet utilisateur. Le temps retenu est
 *              ajouté au commentaire interne de la ligne (rien n'est écrasé).
 */
class BendingSimulationService
{
    public const POLICY = 'calcule';

    public function __construct(
        private readonly Nest2BendAccess $access,
        private readonly Nest2BendGatewayFactory $factory,
        private readonly QuoteLineStepSource $steps,
    ) {}

    /**
     * État du bouton pour une ligne (rendu de la page).
     *
     * @return array{visible: bool, enabled: bool, reason: ?string, step: ?string, expired: bool,
     *               warning: ?string, app_url: ?string}
     */
    public function buttonState(QuoteLines $line): array
    {
        $state = $this->access->state();
        $step = $state['show_commercial'] ? null : $this->steps->find($line);

        $reason = match (true) {
            $state['expired'] => __('nest2bend.state.expired'),
            $step === null => __('nest2bend.button.no_step'),
            default => null,
        };

        return [
            'visible' => ! $state['show_commercial'],
            'enabled' => $state['can_simulate'] && $step !== null,
            'reason'  => $reason,
            'step'    => $step?->original_file_name ?: $step?->name,
            'expired' => $state['expired'],
            'warning' => $state['warning']
                ? __('nest2bend.state.warning', [
                    'etat'     => __('nest2bend.etats.' . $state['etat']),
                    'echeance' => $state['echeance']?->format('d/m/Y') ?? '—',
                ])
                : null,
            'app_url' => $state['app_url'],
        ];
    }

    /**
     * @return array{url: string, origin: string, remarques: list<string>}
     *
     * @throws Nest2BendException
     */
    public function start(QuoteLines $line, User $user, string $erpOrigin, string $locale): array
    {
        $setting = $this->access->setting();
        if (! $setting || ! $this->access->state($setting)['can_simulate']) {
            throw new Nest2BendException(Nest2BendException::INACTIVE, __('nest2bend.errors.inactive'), 403);
        }

        $file = $this->steps->find($line);
        if ($file === null) {
            throw new Nest2BendException(Nest2BendException::INVALID, __('nest2bend.button.no_step'), 422);
        }

        $line->loadMissing('quote');
        $reference = $this->reference($line);
        $quantity = max(1, (int) ceil((float) $line->qty));

        try {
            $session = $this->factory->make($setting)->createEmbedSession([
                'origine'           => $erpOrigin,
                'reference_externe' => $reference,
                'langue'            => $locale === 'fr' ? 'fr' : 'en',
                'step'              => $this->steps->payload($file),
                'outils'            => [],
                'temps'             => ['politique' => self::POLICY, 'quantite' => $quantity],
            ]);
        } catch (Nest2BendException $e) {
            // Abonnement coupé ou clé révoquée depuis le dernier contrôle : la carte le montre.
            if ($e->is(Nest2BendException::INACTIVE) && $e->account !== null) {
                $this->access->apply($setting, $e->account);
            } elseif ($e->is(Nest2BendException::INACTIVE) || $e->is(Nest2BendException::UNAUTHORIZED)) {
                $this->access->refresh($setting);
            }

            throw $e;
        }

        // Une URL d'iframe d'une autre origine que celle configurée n'est jamais ouverte.
        // NEST2BEND construit l'URL depuis son APP_URL, qui doit être l'URL saisie dans l'ERP.
        $origin = $setting->origin();
        $frameOrigin = Nest2BendSetting::originOf($session->url);
        if ($origin === null || $frameOrigin !== $origin) {
            throw new Nest2BendException(Nest2BendException::UNEXPECTED, __('nest2bend.errors.origin_mismatch', [
                'received'   => $frameOrigin ?? '?',
                'configured' => $origin ?? '?',
            ]));
        }

        BendingSimulation::create([
            'quote_lines_id'    => $line->id,
            'user_id'           => $user->id,
            'file_id'           => $file->id,
            'reference_externe' => $reference,
            'session_id'        => $session->sessionId,
            'status'            => BendingSimulation::STATUS_PENDING,
            'politique'         => self::POLICY,
            'quantite'          => $quantity,
        ]);

        return ['url' => $session->url, 'origin' => $origin, 'remarques' => $session->remarques];
    }

    /**
     * @return array{internal_comment: string, summary: string, already: bool}
     *
     * @throws Nest2BendException
     */
    public function complete(QuoteLines $line, User $user, string $resultId): array
    {
        $setting = $this->access->setting();
        if (! $setting?->hasKey()) {
            throw new Nest2BendException(Nest2BendException::INACTIVE, __('nest2bend.errors.inactive'), 403);
        }

        // Même resultId déjà enregistré pour cette ligne (double clic, message reçu deux fois).
        $done = BendingSimulation::where('result_id', $resultId)->first();
        if ($done !== null) {
            if ((int) $done->quote_lines_id !== (int) $line->id || (int) $done->user_id !== (int) $user->id) {
                throw new Nest2BendException(Nest2BendException::NOT_FOUND, __('nest2bend.errors.not_found'), 404);
            }

            return [
                'internal_comment' => (string) $line->QuoteLineDetails?->internal_comment,
                'summary'          => $this->summary($done->preparation_s, $done->unitaire_s, (int) $done->quantite, $done->compteurs ?? [], $done->section),
                'already'          => true,
            ];
        }

        $result = $this->factory->make($setting)->result($resultId);

        // Le resultId vient du navigateur : il n'est cru que s'il ferme une session ouverte ici.
        $simulation = BendingSimulation::where('session_id', $result->sessionId)
            ->where('quote_lines_id', $line->id)
            ->where('user_id', $user->id)
            ->where('status', BendingSimulation::STATUS_PENDING)
            ->first();

        if ($simulation === null || $simulation->reference_externe !== $result->referenceExterne || $result->resultId !== $resultId) {
            throw new Nest2BendException(Nest2BendException::NOT_FOUND, __('nest2bend.errors.not_found'), 404);
        }

        return DB::transaction(function () use ($line, $simulation, $result) {
            $details = QuoteLineDetails::where('quote_lines_id', $line->id)->lockForUpdate()->first()
                ?? QuoteLineDetails::create(['quote_lines_id' => $line->id]);

            $summary = $this->summary($result->preparation, $result->unitaire, (int) $simulation->quantite, $result->compteurs, $result->section);
            $entry = __('nest2bend.comment.entry', ['date' => now()->format('d/m/Y'), 'summary' => $summary]);

            $current = rtrim((string) $details->internal_comment);
            $details->internal_comment = $current === '' ? $entry : $current . "\n" . $entry;
            $details->save();

            $simulation->forceFill([
                'result_id'     => $result->resultId,
                'status'        => BendingSimulation::STATUS_DONE,
                'politique'     => $result->politique ?? $simulation->politique,
                'preparation_s' => $result->preparation,
                'unitaire_s'    => $result->unitaire,
                'compteurs'     => $result->compteurs,
                'section'       => $result->section,
                'completed_at'  => now(),
            ])->save();

            return ['internal_comment' => $details->internal_comment, 'summary' => $summary, 'already' => false];
        });
    }

    /** reference_externe : lisible dans NEST2BEND, ex. « DV-2026-0142/L3 ». */
    public function reference(QuoteLines $line): string
    {
        $quote = $line->quote?->code ?: 'DEVIS-' . $line->quotes_id;

        return mb_substr($quote . '/L' . ($line->ordre ?: $line->id), 0, 255);
    }

    /**
     * « Préparation 4 min 30 s · 12 s/pièce (lot 50) · plis 6, retournements 2 ».
     */
    public function summary(?float $preparation, ?float $unitaire, int $quantity, array $compteurs = [], ?array $section = null): string
    {
        // retenu (ou l'un de ses temps) peut être null : politique « masque », ou « erp » sans
        // temps imposé. Ce n'est pas une erreur : on note qu'il n'y a pas de temps.
        $parts = [];
        if ($preparation === null && $unitaire === null) {
            $parts[] = __('nest2bend.comment.no_time');
        } else {
            if ($preparation !== null) {
                $parts[] = __('nest2bend.comment.preparation', ['time' => self::duration($preparation)]);
            }
            if ($unitaire !== null) {
                $parts[] = __('nest2bend.comment.unit', ['time' => self::duration($unitaire), 'qty' => max(1, $quantity)]);
            }
        }

        $counters = collect($compteurs)
            ->filter(fn ($value, $key) => is_string($key) && is_numeric($value))
            ->map(fn ($value, $key) => str_replace('_', ' ', $key) . ' ' . (0 + $value))
            ->implode(', ');
        if ($counters !== '') {
            $parts[] = $counters;
        }

        if (is_numeric($section['epaisseur'] ?? null)) {
            $parts[] = __('nest2bend.comment.thickness', ['t' => 0 + $section['epaisseur']]);
        }

        return implode(' · ', $parts);
    }

    /** Secondes → « 12 s », « 4 min 30 s », « 1 h 05 min ». */
    public static function duration(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        if ($seconds < 60) {
            return rtrim(rtrim(number_format($seconds, 1, ',', ''), '0'), ',') . ' s';
        }

        $total = (int) round($seconds);
        if ($total < 3600) {
            $s = $total % 60;

            return intdiv($total, 60) . ' min' . ($s ? ' ' . $s . ' s' : '');
        }

        return intdiv($total, 3600) . ' h ' . str_pad((string) intdiv($total % 3600, 60), 2, '0', STR_PAD_LEFT) . ' min';
    }
}
