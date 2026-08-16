<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Integrations\PdpInvoiceSubmission;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Integrations\QontoConnection;
use App\Models\Integrations\QontoSyncReview;
use App\Services\Integrations\Pdp\PdpManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran d'accueil des intégrations : une carte par connecteur, chacune renvoyant
 * vers son écran de configuration dédié.
 *
 * Volontairement en lecture seule : rien ne se configure ici. Les connecteurs
 * n'ont pas le même modèle (OAuth utilisateur pour Qonto, secrets statiques
 * pour les endpoints webhook, config env pour PDP) — les unifier dans un seul
 * formulaire reviendrait à empiler des `@if` par système. Le hub unifie la
 * *découverte* et l'*état de santé*, pas le stockage.
 */
class IntegrationHubController extends Controller
{
    public function __construct(
        private PdpManager $pdp,
    ) {
    }

    public function index(Request $request): View
    {
        // La carte dédiée N2P a été retirée du hub (refactor 2026-08-12) :
        // N2P vit dans la carte générique "Endpoints webhook" désormais.
        // n8n reste une carte dédiée : contrairement à N2P (partenaire technique
        // unique), n8n est un outil grand public que l'utilisateur final peut
        // découvrir depuis le hub — la carte sert de porte d'entrée + doc courte.
        return view('integrations.index', [
            'qonto'     => $this->qontoCard((int) $request->user()->id),
            'pdp'       => $this->pdpCard(),
            'endpoints' => $this->endpointsCard(),
            'n8n'       => $this->n8nCard(),
        ]);
    }

    /**
     * Qonto est scopé par tenant_id = id de l'utilisateur connecté (OAuth
     * personnel), contrairement aux autres connecteurs qui sont globaux usine.
     */
    private function qontoCard(int $tenantId): array
    {
        $configured = (string) config('services.qonto.client_id', '') !== ''
            && (string) config('services.qonto.client_secret', '') !== '';

        $connection = QontoConnection::where('tenant_id', $tenantId)->first();

        $expired = $connection
            && $connection->access_token_expires_at
            && $connection->access_token_expires_at->isPast();

        return [
            'configured'      => $configured,
            'connected'       => (bool) $connection,
            'expired'         => $expired,
            'organization'    => $connection?->organization_slug,
            'last_sync_at'    => $connection?->last_sync_at,
            'bidirectionnel'  => (bool) $connection?->import_bidirectionnel,
            'mapped_clients'  => $connection
                ? QontoClientMapping::where('tenant_id', $tenantId)->count()
                : 0,
            'pending_reviews' => $connection
                ? QontoSyncReview::where('tenant_id', $tenantId)->where('status', 'pending')->count()
                : 0,
        ];
    }

    /**
     * La PDP se configure par .env (PDP_DRIVER) et non en base : la carte est
     * donc purement informative, sans lien de configuration.
     */
    private function pdpCard(): array
    {
        $driver = (string) config('services.pdp.default', 'qonto');

        $counts = PdpInvoiceSubmission::query()
            ->selectRaw('lifecycle_status, COUNT(*) as total')
            ->groupBy('lifecycle_status')
            ->pluck('total', 'lifecycle_status')
            ->all();

        return [
            'driver'    => $driver,
            'enabled'   => $this->pdp->isEnabled(),
            'available' => $this->pdp->available(),
            'counts'    => $counts,
            'total'     => array_sum($counts),
        ];
    }

    private function endpointsCard(): array
    {
        $endpoints = IntegrationEndpoint::all();

        $failing = $endpoints->filter(
            fn (IntegrationEndpoint $e) => $e->last_error_at
                && (! $e->last_success_at || $e->last_error_at->gt($e->last_success_at)),
        );

        return [
            'total'    => $endpoints->count(),
            'active'   => $endpoints->where('is_active', true)->count(),
            'inbound'  => $endpoints->where('direction', IntegrationEndpoint::DIRECTION_INBOUND)->count(),
            'outbound' => $endpoints->where('direction', IntegrationEndpoint::DIRECTION_OUTBOUND)->count(),
            'failing'  => $failing->count(),
            'systems'  => $endpoints->pluck('system_code')->unique()->sort()->values()->all(),
        ];
    }

    /**
     * n8n = automation open source (workflows sur événements). Se branche via
     * la même mécanique d'endpoints webhook que N2P, mais on lui dédie une
     * carte car l'utilisateur final connaît "n8n" en tant qu'outil, pas en
     * tant que system_code. La carte propose un raccourci de création préconfiguré
     * (bearer+HMAC, sortant) et affiche santé et volumétrie des endpoints n8n.
     */
    private function n8nCard(): array
    {
        $endpoints = IntegrationEndpoint::forSystem('n8n')->get();

        $failing = $endpoints->filter(
            fn (IntegrationEndpoint $e) => $e->last_error_at
                && (! $e->last_success_at || $e->last_error_at->gt($e->last_success_at)),
        );

        $lastSuccessAt = $endpoints
            ->pluck('last_success_at')
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'total'           => $endpoints->count(),
            'active'          => $endpoints->where('is_active', true)->count(),
            'inbound'         => $endpoints->where('direction', IntegrationEndpoint::DIRECTION_INBOUND)->count(),
            'outbound'        => $endpoints->where('direction', IntegrationEndpoint::DIRECTION_OUTBOUND)->count(),
            'failing'         => $failing->count(),
            'last_success_at' => $lastSuccessAt,
        ];
    }
}
