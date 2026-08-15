<?php

namespace App\Services\Integrations\Pdp\Drivers;

use App\Models\Integrations\PdpSyncCursor;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\Contracts\PdpCursorSyncGateway;
use App\Services\Integrations\Pdp\Contracts\PdpDirectoryGateway;
use App\Services\Integrations\Pdp\Contracts\PdpGateway;
use App\Services\Integrations\Pdp\Contracts\PdpInboundGateway;
use App\Services\Integrations\Pdp\Contracts\PdpStatusReportingGateway;
use App\Services\Integrations\Pdp\Data\PdpInvoiceResult;
use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;
use App\Services\Integrations\Pdp\Enums\PdpOutgoingStatus;
use App\Services\Integrations\SuperPdpConnectionService;
use App\Services\Invoicing\FacturXBuilder;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Driver SUPER PDP — Plateforme Agréée (PA/PDP) immatriculée, raccordée au
 * réseau Peppol et au portail public de facturation (PPF).
 *
 * Différence de fond avec le driver Qonto : ici WEM envoie **son propre
 * document**. Le Factur-X produit par FacturXBuilder est déposé tel quel ; la
 * plateforme ne le régénère pas. WEM reste donc responsable de la conformité du
 * document, ce qui est le comportement voulu pour un ERP (le PDF archivé par le
 * client et celui reçu par son acheteur sont le même fichier).
 *
 * Suivi : SUPER PDP **n'expose aucun webhook** (la spec OpenAPI n'en déclare
 * pas). La synchronisation documentée passe par des curseurs — d'où
 * l'implémentation de PdpCursorSyncGateway et la commande wem:pdp:sync.
 *
 * @see https://www.superpdp.tech/openapi (spec « SUPER PDP », v1.33.0.beta)
 */
class SuperPdpGateway implements PdpGateway, PdpInboundGateway, PdpCursorSyncGateway, PdpDirectoryGateway, PdpStatusReportingGateway
{
    /** Taille de page demandée lors des synchronisations. */
    private const PAGE_SIZE = 100;

    /** Garde-fou : nombre de pages maximum lues en une exécution. */
    private const MAX_PAGES = 50;

    /** Société du jeton courant (cf. company()), résolue au plus une fois. */
    private ?array $company = null;

    public function __construct(
        private SuperPdpConnectionService $connection,
        private FacturXBuilder $facturX,
    ) {}

    public function key(): string
    {
        return 'superpdp';
    }

    public function isEnabled(): bool
    {
        return SuperPdpConnectionService::isEnabled();
    }

    /* ---------------------------------------------------------------- Émission */

    public function submit(Invoices $invoice): PdpInvoiceResult
    {
        $pdf = $this->facturX->buildPdf($invoice);

        if (config('services.superpdp.pre_validate', true)) {
            $this->preValidate($invoice, $pdf);
        }

        $query = http_build_query(array_filter([
            // Notre référence, visible dans le compte SUPER PDP (36 caractères max).
            'external_id'     => Str::limit((string) $invoice->code, 36, ''),
            'processing_rule' => config('services.superpdp.processing_rule', 'B2B'),
        ]));

        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->withBody($pdf, 'application/pdf')
            ->post($this->url('/invoices') . '?' . $query));

        $this->guardAgainstRejection($response, $invoice);

        $data       = $response->json() ?? [];
        $externalId = isset($data['id']) ? (string) $data['id'] : null;

        Log::info('SuperPdpGateway: invoice deposited', [
            'invoice_id'  => $invoice->id,
            'code'        => $invoice->code,
            'external_id' => $externalId,
        ]);

        return $this->toResult($externalId, $data);
    }

    public function poll(string $externalId, int $tenantId): PdpInvoiceResult
    {
        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->get($this->url("/invoices/{$externalId}") . '?expand[]=events'))
            ->throw();

        return $this->toResult($externalId, $response->json() ?? []);
    }

    /**
     * SUPER PDP ne publie aucun webhook : la spec OpenAPI n'en déclare pas, et
     * la documentation officielle décrit explicitement la synchronisation par
     * curseur comme le mécanisme de suivi. L'endpoint générique reste donc
     * inerte pour ce driver — voir fetchEvents() et la commande wem:pdp:sync.
     */
    public function parseWebhook(Request $request): ?PdpWebhookEvent
    {
        Log::warning('SuperPdpGateway: unexpected webhook call, SUPER PDP does not emit webhooks');

        return null;
    }

    /* ---------------------------------------------------- Cycle de vie (curseur) */

    /**
     * @return array<int, PdpWebhookEvent>
     */
    public function fetchEvents(int $tenantId): array
    {
        $cursor = PdpSyncCursor::positionOf($this->key(), PdpSyncCursor::STREAM_EVENTS, $tenantId);
        $events = [];
        $maxId  = 0;

        foreach ($this->paginate('/invoice_events', [], $cursor) as $event) {
            $maxId     = max($maxId, (int) ($event['id'] ?? 0));
            $lifecycle = $this->mapStatus((string) ($event['status_code'] ?? ''));

            // Les statuts sans portée sur le cycle de vie (ppf:*, e-reporting
            // fiscal) sont ignorés.
            if (! $lifecycle || ! isset($event['invoice_id'])) {
                continue;
            }

            $events[] = new PdpWebhookEvent(
                (string) $event['invoice_id'],
                $lifecycle,
                $this->reasonFor($lifecycle, $event),
                $event,
            );
        }

        // Lot entièrement ignoré : rien ne sera traité, donc rien ne peut
        // échouer — on avance nous-mêmes pour ne pas relire ces mêmes
        // événements à chaque exécution, indéfiniment.
        if ($events === [] && $maxId > 0) {
            $this->commitEvents($tenantId, $maxId);
        }

        return $events;
    }

    public function commitEvents(int $tenantId, int $lastEventId): void
    {
        PdpSyncCursor::advance($this->key(), PdpSyncCursor::STREAM_EVENTS, $lastEventId, $tenantId);
    }

    /* --------------------------------------------------------------- Réception */

    /**
     * SUPER PDP ne pousse rien : cette méthode du contrat est sans objet ici.
     */
    public function parseInboundWebhook(Request $request): ?array
    {
        return null;
    }

    /**
     * Factures fournisseurs reçues depuis le curseur, contenu brut téléchargé.
     *
     * Le curseur n'est pas avancé ici : c'est la synchronisation qui le valide
     * (commitInbound) au fur et à mesure des documents réellement traités.
     *
     * @return array<int, array{external_id: ?string, content: string}>
     */
    public function fetchInbound(int $tenantId): array
    {
        $cursor  = PdpSyncCursor::positionOf($this->key(), PdpSyncCursor::STREAM_INVOICES_IN, $tenantId);
        $entries = [];

        foreach ($this->paginate('/invoices', ['direction' => 'in'], $cursor) as $invoice) {
            if (! isset($invoice['id'])) {
                continue;
            }

            $entries[] = [
                'external_id' => (string) $invoice['id'],
                'content'     => $this->download((int) $invoice['id']),
            ];
        }

        return $entries;
    }

    public function commitInbound(int $tenantId, int $lastInvoiceId): void
    {
        PdpSyncCursor::advance($this->key(), PdpSyncCursor::STREAM_INVOICES_IN, $lastInvoiceId, $tenantId);
    }

    /** Fichier brut (Factur-X ou XML) d'une facture de la plateforme. */
    private function download(int $id): string
    {
        return $this->withAuth(fn (string $token) => Http::withToken($token)
            ->get($this->url("/invoices/{$id}/download")))
            ->throw()
            ->body();
    }

    /* ------------------------------------------------- Statuts émis (acheteur) */

    public function reportStatus(
        string $externalId,
        PdpOutgoingStatus $status,
        ?string $reason = null,
        ?string $note = null,
    ): void {
        $detail = array_filter([
            'reason' => $reason,
            'notes'  => $note ? [[
                'content_code' => $status->value,
                'contents'     => [['content' => Str::limit($note, 900)]],
            ]] : null,
        ]);

        $payload = array_filter([
            'invoice_id'  => (int) $externalId,
            'status_code' => $status->value,
            // `details` n'est envoyé que s'il porte quelque chose : un tableau
            // vide serait refusé par la plateforme.
            'details'     => $detail !== [] ? [$detail] : null,
        ]);

        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->asJson()
            ->post($this->url('/invoice_events'), $payload));

        if (! $response->successful()) {
            $body = $response->json() ?? [];

            throw new \RuntimeException(
                "Déclaration du statut « {$status->label()} » refusée par la plateforme : "
                . Str::limit((string) ($body['message'] ?? $response->body()), 500)
            );
        }

        Log::info('SuperPdpGateway: status reported', [
            'external_id' => $externalId,
            'status_code' => $status->value,
            'reason'      => $reason,
        ]);
    }

    /* --------------------------------------------------------------- Annuaire */

    public function listEntries(): array
    {
        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->get($this->url('/directory_entries')))
            ->throw();

        return array_map(fn (array $entry) => [
            'id'             => (string) ($entry['id'] ?? ''),
            'identifier'     => (string) ($entry['identifier'] ?? ''),
            'directory'      => (string) ($entry['directory'] ?? ''),
            'is_replyto'     => (bool) ($entry['is_replyto'] ?? false),
            'effective_date' => isset($entry['effective_date']) ? substr((string) $entry['effective_date'], 0, 10) : null,
        ], $response->json('data', []));
    }

    public function openEntry(string $identifier, ?string $effectiveDate = null): array
    {
        $directory = $this->directoryForEnvironment();

        $payload = array_filter([
            'directory'      => $directory,
            'identifier'     => $this->normalizeIdentifier($identifier, $directory),
            // Date de prise d'effet : propre à l'annuaire français.
            'effective_date' => $directory === 'ppf' ? $effectiveDate : null,
        ]);

        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->asJson()
            ->post($this->url('/directory_entries'), $payload));

        if (! $response->successful()) {
            $body = $response->json() ?? [];
            throw new \RuntimeException(
                "Ouverture de la ligne d'annuaire refusée : "
                . Str::limit((string) ($body['message'] ?? $response->body()), 500)
            );
        }

        $entry = $response->json() ?? [];

        Log::info('SuperPdpGateway: directory entry opened', [
            'identifier' => $payload['identifier'],
            'directory'  => $directory,
        ]);

        return [
            'id'         => (string) ($entry['id'] ?? ''),
            'identifier' => (string) ($entry['identifier'] ?? $payload['identifier']),
            'directory'  => (string) ($entry['directory'] ?? $directory),
        ];
    }

    public function closeEntry(string $id): void
    {
        $this->withAuth(fn (string $token) => Http::withToken($token)
            ->delete($this->url("/directory_entries/{$id}")))
            ->throw();

        Log::info('SuperPdpGateway: directory entry closed', ['id' => $id]);
    }

    public function lookupEntries(string $siren): array
    {
        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->get($this->url('/french_directory/entries'), ['number' => $this->digits($siren)]))
            ->throw();

        return array_map(fn (array $entry) => [
            'identifier' => (string) ($entry['identifier'] ?? ''),
            'is_active'  => (bool) ($entry['is_active'] ?? false),
            'name'       => $entry['company']['formal_name'] ?? null,
            'city'       => $entry['company']['city'] ?? null,
        ], $response->json('data', []));
    }

    public function searchCompanies(array $criteria): array
    {
        $query = array_filter([
            'number'                 => isset($criteria['number']) ? $this->digits($criteria['number']) : null,
            'formal_name_starts_with' => $criteria['name'] ?? null,
            'post_code_starts_with'  => $criteria['post_code'] ?? null,
            'limit'                  => $criteria['limit'] ?? 20,
        ]);

        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->get($this->url('/french_directory/companies'), $query))
            ->throw();

        return array_map(fn (array $company) => [
            'number'      => (string) ($company['number'] ?? ''),
            'formal_name' => (string) ($company['formal_name'] ?? ''),
            'address'     => $company['address'] ?? null,
            'postcode'    => $company['postcode'] ?? null,
            'city'        => $company['city'] ?? null,
        ], $response->json('data', []));
    }

    /**
     * Annuaire dans lequel ouvrir une ligne, imposé par l'environnement :
     * en bac à sable seul `peppol` est ouvrable, en production les identifiants
     * français passent par `ppf` — la plateforme se chargeant elle-même de
     * créer l'entrée Peppol correspondante. Choisir à la place de l'utilisateur
     * évite un refus incompréhensible au moment de l'ouverture.
     */
    private function directoryForEnvironment(): string
    {
        return $this->company()['env'] === 'sandbox' ? 'peppol' : 'ppf';
    }

    /**
     * Met l'identifiant au format attendu par l'annuaire visé : préfixé du
     * scheme ID pour Peppol (`0225:853322915`), nu pour l'annuaire français
     * (`853322915`, `853322915_SERVICEACHATS`).
     */
    private function normalizeIdentifier(string $identifier, string $directory): string
    {
        $identifier = trim($identifier);

        if ($directory === 'ppf') {
            return Str::contains($identifier, ':') ? Str::after($identifier, ':') : $identifier;
        }

        return Str::contains($identifier, ':') ? $identifier : '0225:' . $identifier;
    }

    /** Société rattachée au jeton courant, mise en cache le temps de la requête. */
    private function company(): array
    {
        return $this->company ??= $this->withAuth(fn (string $token) => Http::withToken($token)
            ->get($this->url('/companies/me')))
            ->throw()
            ->json() ?? [];
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }

    /* ------------------------------------------------------------- Mécanique HTTP */

    /**
     * Parcourt un flux paginé à partir d'un curseur et retourne tous les objets.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paginate(string $path, array $query, int $cursor): array
    {
        $items = [];
        $pages = 0;

        do {
            $response = $this->withAuth(fn (string $token) => Http::withToken($token)
                ->get($this->url($path), $query + [
                    'starting_after_id' => $cursor,
                    'limit'             => self::PAGE_SIZE,
                ]))
                ->throw();

            $body = $response->json() ?? [];
            $page = $body['data'] ?? [];

            foreach ($page as $item) {
                $items[] = $item;
                $cursor  = max($cursor, (int) ($item['id'] ?? 0));
            }

            $hasAfter = (bool) ($body['has_after'] ?? false) && $page !== [];
        } while ($hasAfter && ++$pages < self::MAX_PAGES);

        if ($hasAfter) {
            Log::warning('SuperPdpGateway: pagination cap reached, remaining objects deferred to next run', [
                'path'   => $path,
                'cursor' => $cursor,
            ]);
        }

        return $items;
    }

    /**
     * Exécute un appel authentifié, en réessayant une fois avec un token neuf.
     *
     * L'access_token vit 30 minutes ; entre sa mise en cache et son usage il
     * peut avoir été révoqué côté plateforme. Un seul réessai : si le second
     * échoue aussi, ce sont les identifiants qui sont en cause.
     */
    private function withAuth(\Closure $call): Response
    {
        $response = $call($this->connection->getValidToken());

        if ($response->status() === 401) {
            $this->connection->forgetToken();
            $response = $call($this->connection->getValidToken(true));
        }

        return $response;
    }

    private function url(string $path): string
    {
        return $this->connection->baseUrl() . '/v1.beta' . $path;
    }

    /* ------------------------------------------------------------- Validation */

    /**
     * Pré-validation schematron avant dépôt.
     *
     * Sans elle, un document non conforme est accepté en HTTP 200 puis rejeté
     * de façon asynchrone en `api:invalid` : l'utilisateur croit avoir émis sa
     * facture. Avec elle, l'erreur remonte immédiatement dans l'interface.
     *
     * @throws \RuntimeException si le document ne passe pas les règles en vigueur
     */
    private function preValidate(Invoices $invoice, string $pdf): void
    {
        $response = $this->withAuth(fn (string $token) => Http::withToken($token)
            ->attach('file_name', $pdf, 'invoice.pdf')
            ->post($this->url('/validation_reports')));

        if (! $response->successful()) {
            // La validation est un confort, pas une autorisation : si le service
            // est indisponible on laisse le dépôt suivre son cours normal.
            Log::warning('SuperPdpGateway: pre-validation unavailable, proceeding', [
                'invoice_id' => $invoice->id,
                'status'     => $response->status(),
            ]);
            return;
        }

        foreach ($response->json('data', []) as $report) {
            if ($report['is_valid'] ?? true) {
                continue;
            }

            // Tout ce qui rend `is_valid` faux est bloquant, y compris les
            // assertions marquées `flag="warning"` : la plateforme rejette
            // ensuite le document en fr:213 / REJ_SEMAN, en citant précisément
            // ces règles-là. Le libellé « warning » décrit la sévérité dans le
            // schematron, pas le sort réservé à la facture.
            //
            // Les deux tableaux sont donc réunis : `failures` porte les échecs
            // fatals, `messages` les avertissements — et les deux font rejeter.
            $problems = array_merge(
                $this->collect($report, 'failures'),
                $this->collect($report, 'messages'),
            );

            throw new \RuntimeException(
                "La facture {$invoice->code} n'est pas conforme : " . $this->format($problems, $report)
            );
        }
    }

    /**
     * Aplatit les entrées d'un rapport de validation.
     *
     * @return array<int, string>
     */
    private function collect(array $report, string $key): array
    {
        $messages = [];

        foreach ($report['subreports'] ?? [] as $subreport) {
            foreach ($subreport[$key] ?? [] as $entry) {
                $rule    = trim((string) ($entry['rule'] ?? ''));
                $message = trim((string) ($entry['message'] ?? $entry['raw'] ?? ''));

                if ($message === '') {
                    continue;
                }

                // Le libellé cite souvent déjà la règle : ne pas la répéter.
                $messages[] = ($rule !== '' && ! str_contains($message, $rule))
                    ? "[{$rule}] {$message}"
                    : $message;
            }
        }

        return array_values(array_unique($messages));
    }

    /** @param array<int, string> $messages */
    private function format(array $messages, array $report): string
    {
        if ($messages === []) {
            return (string) ($report['error'] ?? 'motif non précisé par le validateur');
        }

        return Str::limit(implode("\n", array_slice($messages, 0, 5)), 1500);
    }

    /** Transforme un refus d'API en message exploitable dans l'interface. */
    private function guardAgainstRejection(Response $response, Invoices $invoice): void
    {
        if ($response->successful()) {
            return;
        }

        if (in_array($response->status(), [400, 422], true)) {
            $body    = $response->json() ?? [];
            $message = $body['message'] ?? $body['error'] ?? $response->body();

            throw new \RuntimeException(
                "SUPER PDP a refusé la facture {$invoice->code} : " . Str::limit((string) $message, 900)
            );
        }

        $response->throw();
    }

    /* -------------------------------------------------------------- Traduction */

    /**
     * Normalise une réponse `invoice` vers le vocabulaire canonique WEM.
     */
    private function toResult(?string $externalId, array $data): PdpInvoiceResult
    {
        $event = $this->latestMeaningfulEvent($data['events'] ?? []);

        if (! $event) {
            // Dépôt accepté mais aucun événement encore attaché : c'est le cas
            // normal juste après le POST, le traitement étant asynchrone.
            return new PdpInvoiceResult($externalId, PdpLifecycle::Submitted, null, $data);
        }

        [$lifecycle, $rawEvent] = $event;

        return new PdpInvoiceResult(
            $externalId,
            $lifecycle,
            $this->reasonFor($lifecycle, $rawEvent),
            $data,
        );
    }

    /**
     * Retient l'événement le plus récent porteur d'un cycle de vie connu.
     *
     * SUPER PDP documente explicitement que les statuts sont un **tableau
     * d'événements et non une machine à états** : ils s'accumulent et aucun ne
     * remplace le précédent. Le plus récent (id le plus élevé) est donc le seul
     * qui décrive la situation courante — un classement par « gravité » ferait
     * par exemple gagner une acceptation sur un refus postérieur.
     *
     * @return array{0: PdpLifecycle, 1: array<string, mixed>}|null
     */
    private function latestMeaningfulEvent(array $events): ?array
    {
        usort($events, fn ($a, $b) => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));

        foreach ($events as $event) {
            if ($lifecycle = $this->mapStatus((string) ($event['status_code'] ?? ''))) {
                return [$lifecycle, $event];
            }
        }

        return null;
    }

    /**
     * Statut SUPER PDP → vocabulaire canonique WEM.
     *
     * `api:*` = statuts internes de la plateforme (également utilisés pour les
     * échanges Peppol hors cadre français), `fr:*` = statuts officiels de la
     * facturation électronique française. Les `ppf:*` décrivent les échanges
     * techniques avec le portail public (e-reporting fiscal) et ne disent rien
     * du sort de la facture : ils retournent null et sont ignorés.
     */
    private function mapStatus(string $code): ?PdpLifecycle
    {
        return match ($code) {
            'api:uploaded', 'api:validated', 'api:sent' => PdpLifecycle::Submitted,
            'api:invalid', 'api:rejected'               => PdpLifecycle::Rejected,
            'api:received', 'api:acknowledged'          => PdpLifecycle::Acknowledged,
            'api:accepted'                              => PdpLifecycle::Accepted,

            'fr:200', 'fr:201'                => PdpLifecycle::Submitted,  // déposée, émise
            'fr:202', 'fr:203', 'fr:204', 'fr:208' => PdpLifecycle::Acknowledged, // reçue, mise à disposition, prise en charge, suspendue
            'fr:205', 'fr:206', 'fr:209'      => PdpLifecycle::Accepted,   // approuvée, partiellement, service fait
            'fr:207', 'fr:210'                => PdpLifecycle::Refused,    // litige, refusée
            'fr:211'                          => PdpLifecycle::Accepted,   // paiement émis, pas encore encaissé
            'fr:212'                          => PdpLifecycle::Paid,       // paiement encaissé
            'fr:213', 'fr:501'                => PdpLifecycle::Rejected,   // rejetée, irrecevable

            default => null,
        };
    }

    /**
     * Motif affiché à l'utilisateur pour les issues défavorables. Le libellé
     * porté par l'événement est plus parlant que le code (« litige » vs fr:207).
     */
    private function reasonFor(PdpLifecycle $lifecycle, array $event): ?string
    {
        if (! in_array($lifecycle, [PdpLifecycle::Rejected, PdpLifecycle::Refused], true)) {
            return null;
        }

        $parts = [trim((string) ($event['status_text'] ?? ''))];

        // `data.reason` porte le texte complet du refus, avec la règle violée.
        // C'est la seule information réellement actionnable : sans elle, un
        // rejet se résume à un code (« REJ_SEMAN ») qui ne dit pas quoi corriger.
        if (! empty($event['data']['reason'])) {
            $parts[] = trim((string) $event['data']['reason']);
        }

        foreach ($event['details'] ?? [] as $detail) {
            foreach ($detail['notes'] ?? [] as $note) {
                // notes[].contents[].content : le contenu est imbriqué d'un
                // niveau de plus que ne le laisse penser le nom du champ.
                foreach ($note['contents'] ?? [] as $content) {
                    $parts[] = trim((string) ($content['content'] ?? ''));
                }
                // Le code de règle n'est ajouté que s'il ne figure pas déjà
                // dans le texte, où il est presque toujours cité en tête.
                $code = trim((string) ($note['content_code'] ?? ''));
                if ($code !== '' && ! Str::contains(implode(' ', $parts), $code)) {
                    $parts[] = $code;
                }
            }
            if (! empty($detail['reason'])) {
                $parts[] = 'code ' . $detail['reason'];
            }
        }

        // Les chemins XPath des schematrons occupent des centaines de caractères
        // sans rien apprendre à l'utilisateur : le message s'arrête avant.
        $parts = array_map(
            fn (string $part) => trim((string) preg_split('/\s+at\s+\/\*:/', $part, 2)[0]),
            $parts
        );

        $reason = implode(' — ', array_filter(array_unique($parts)));

        return $reason !== '' ? Str::limit($reason, 480) : (string) ($event['status_code'] ?? null);
    }
}
