<?php

namespace App\Services\Integrations\Pdp\Drivers;

use App\Models\Integrations\QontoClientMapping;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\Contracts\PdpGateway;
use App\Services\Integrations\Pdp\Data\PdpInvoiceResult;
use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;
use App\Services\Integrations\Pdp\Exceptions\PdpSignatureException;
use App\Services\Integrations\QontoConnectionService;
use App\Services\InvoiceCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Driver Qonto, branché sur la Business API v2 (`/client_invoices`).
 *
 * WEM reste la source de vérité : numérotation, prix, TVA et comptabilité sont
 * calculés ici, puis poussés en **données structurées**. C'est Qonto qui produit
 * le document (PDF / Factur-X) à partir de ce payload — on ne lui envoie jamais
 * notre propre PDF, sans quoi la conformité du document reposerait sur nous.
 *
 * Périmètre : facturation commerciale Qonto. Le champ `einvoicing_status` renvoyé
 * par l'API (canal Plateforme Agréée) est journalisé mais volontairement pas
 * interprété ici — il relève du raccordement PDP, traité séparément.
 *
 * @see https://docs.qonto.com/api-reference/business-api/expense-management/client-quotes-notes/client-invoices
 */
class QontoGateway implements PdpGateway
{
    public function __construct(private QontoConnectionService $connectionService) {}

    public function key(): string
    {
        return 'qonto';
    }

    public function isEnabled(): bool
    {
        return QontoConnectionService::isEnabled();
    }

    public function submit(Invoices $invoice): PdpInvoiceResult
    {
        $tenantId = (int) $invoice->user_id;
        $token    = $this->connectionService->getValidToken($tenantId);

        $response = Http::withToken($token)
            ->asJson()
            ->post("{$this->baseUrl()}/client_invoices", $this->buildPayload($invoice, $tenantId));

        $this->guardAgainstRejection($response, $invoice);

        $clientInvoice = (array) $response->json('client_invoice', []);
        $externalId    = $clientInvoice['id'] ?? null;

        Log::info('QontoGateway: client invoice created', [
            'invoice_id'  => $invoice->id,
            'external_id' => $externalId,
            'status'      => $clientInvoice['status'] ?? null,
        ]);

        return $this->toResult($externalId, $clientInvoice, $response->json() ?? []);
    }

    public function poll(string $externalId, int $tenantId): PdpInvoiceResult
    {
        $token = $this->connectionService->getValidToken($tenantId);

        $response = Http::withToken($token)
            ->get("{$this->baseUrl()}/client_invoices/{$externalId}")
            ->throw();

        return $this->toResult($externalId, (array) $response->json('client_invoice', []), $response->json() ?? []);
    }

    /**
     * Qonto ne publie aujourd'hui aucun événement webhook sur les client_invoices
     * (seuls les événements `registrations.*` de l'Onboarding API sont documentés).
     * L'endpoint est donc opérationnel mais inerte : le mécanisme fiable de suivi
     * reste le polling. La correspondance ci-dessous est prête si Qonto ouvre ces
     * événements ; tout autre payload est ignoré après vérification de signature.
     */
    public function parseWebhook(Request $request): ?PdpWebhookEvent
    {
        if (! $this->verifySignature($request)) {
            throw new PdpSignatureException('Invalid Qonto webhook signature.');
        }

        $payload = $request->json()->all();
        $event   = (string) ($payload['event'] ?? $payload['event_type'] ?? '');

        if (! str_starts_with($event, 'client_invoice.')) {
            return null;
        }

        $clientInvoice = $payload['data']['client_invoice'] ?? $payload['client_invoice'] ?? $payload['data'] ?? [];
        $clientInvoice = is_array($clientInvoice) ? $clientInvoice : [];
        $externalId    = $clientInvoice['id'] ?? null;
        $lifecycle     = $this->mapStatus($clientInvoice['status'] ?? null) ?? $this->mapEvent($event);

        if (! $externalId || ! $lifecycle) {
            Log::warning('QontoGateway: unprocessable webhook', ['event' => $event]);
            return null;
        }

        return new PdpWebhookEvent((string) $externalId, $lifecycle, null, $payload);
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/');
    }

    /**
     * Payload de création d'une client_invoice.
     *
     * `status: unpaid` crée la facture directement finalisée : elle est déjà
     * validée dans WEM, la laisser en `draft` imposerait un second appel
     * (POST /client_invoices/{id}/finalize) sans rien apporter.
     */
    private function buildPayload(Invoices $invoice, int $tenantId): array
    {
        $currency = $this->currency();

        $payload = [
            'client_id'       => $this->resolveClientId($tenantId, (int) $invoice->companies_id, $invoice),
            'number'          => Str::limit((string) $invoice->code, 40, ''),
            'issue_date'      => Carbon::parse($invoice->created_at)->format('Y-m-d'),
            'due_date'        => $this->resolveDueDate($invoice),
            'currency'        => $currency,
            'status'          => 'unpaid',
            'payment_methods' => ['iban' => $this->connectionService->getInvoicingIban($tenantId)],
            'items'           => $this->buildItems($invoice, $currency),
        ];

        if ($invoice->customer_reference) {
            $payload['purchase_order'] = Str::limit((string) $invoice->customer_reference, 40, '');
        }

        return $payload;
    }

    /**
     * Lignes de facture au format Qonto, construites depuis le snapshot de prix
     * figé sur invoice_lines (même source que le PDF et le Factur-X internes).
     *
     * Remise : transmise telle quelle en pourcentage plutôt que fondue dans le
     * prix net, pour que le document Qonto affiche la même chose que le nôtre.
     */
    private function buildItems(Invoices $invoice, string $currency): array
    {
        $items = [];

        foreach ((new InvoiceCalculatorService($invoice))->getNormalizedLines() as $line) {
            $label = trim((string) $line['label']);
            $code  = trim((string) $line['code']);
            $title = $label !== '' ? $label : $code;

            $item = [
                'title'      => Str::limit($title !== '' ? $title : 'Ligne de facture', 40, ''),
                'quantity'   => $this->decimal($line['qty'], 4),
                'unit_price' => [
                    // Qonto stocke les prix au cent (unit_price_cents) : au-delà de
                    // 2 décimales, la valeur serait arrondie côté plateforme.
                    'value'    => $this->decimal($line['unit_price'], 2),
                    'currency' => $currency,
                ],
                // Qonto attend un ratio ('0.2'), WEM stocke un pourcentage (20).
                'vat_rate'   => $this->decimal($line['vat_rate'] / 100, 4),
            ];

            $description = trim(implode(' — ', array_filter([$code, $label])));
            if ($description !== '' && $description !== $item['title']) {
                $item['description'] = Str::limit($description, 1800, '');
            }

            if (! empty($line['unit_code'])) {
                $item['unit'] = Str::limit((string) $line['unit_code'], 20, '');
            }

            if ((float) $line['discount'] > 0) {
                $item['discount'] = [
                    'type'  => 'percentage',
                    'value' => $this->decimal($line['discount'], 2),
                ];
            }

            $items[] = $item;
        }

        if ($items === []) {
            throw new \RuntimeException("La facture #{$invoice->id} n'a aucune ligne : rien à déposer chez Qonto.");
        }

        return $items;
    }

    /** Résout l'ID Qonto du client WEM ; obligatoire pour créer une facture. */
    private function resolveClientId(int $tenantId, int $companyId, Invoices $invoice): string
    {
        $clientId = QontoClientMapping::where('tenant_id', $tenantId)
            ->where('wem_client_id', $companyId)
            ->whereNotNull('qonto_client_id')
            ->value('qonto_client_id');

        if (! $clientId) {
            throw new \RuntimeException(
                "Aucun client Qonto associé à l'entreprise #{$companyId} (facture {$invoice->code}). "
                .'Lancez la synchronisation des clients avant de déposer la facture.'
            );
        }

        return (string) $clientId;
    }

    /** `due_date` est obligatoire côté Qonto ; on ne l'invente pas sur un document légal. */
    private function resolveDueDate(Invoices $invoice): string
    {
        if (! $invoice->due_date) {
            throw new \RuntimeException(
                "La facture {$invoice->code} n'a pas de date d'échéance : renseignez-la avant le dépôt."
            );
        }

        return Carbon::parse($invoice->due_date)->format('Y-m-d');
    }

    private function currency(): string
    {
        return app('Factory')->curency ?? 'EUR';
    }

    private function decimal(float|int|string $value, int $precision): string
    {
        return number_format((float) $value, $precision, '.', '');
    }

    /** Transforme une erreur d'API en message exploitable dans l'UI. */
    private function guardAgainstRejection(Response $response, Invoices $invoice): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 409) {
            throw new \RuntimeException(
                "Le numéro {$invoice->code} correspond déjà à une facture Qonto : "
                .'utilisez « Actualiser le statut » plutôt qu\'un nouveau dépôt.'
            );
        }

        if (in_array($response->status(), [400, 422], true)) {
            throw new \RuntimeException(
                "Qonto a refusé la facture {$invoice->code} : ".$this->formatErrors($response->json() ?? [])
            );
        }

        $response->throw();
    }

    private function formatErrors(array $body): string
    {
        $errors = collect($body['errors'] ?? [])
            ->map(fn ($error) => is_array($error) ? ($error['detail'] ?? $error['code'] ?? json_encode($error)) : (string) $error)
            ->filter()
            ->implode(' ; ');

        return $errors !== '' ? $errors : ($body['message'] ?? 'erreur inconnue');
    }

    /** Normalise une réponse client_invoice vers le vocabulaire canonique. */
    private function toResult(?string $externalId, array $clientInvoice, array $raw): PdpInvoiceResult
    {
        $status    = $clientInvoice['status'] ?? null;
        $lifecycle = $this->mapStatus($status);

        if (! $lifecycle) {
            Log::warning('QontoGateway: unknown client invoice status', [
                'external_id' => $externalId,
                'status'      => $status,
            ]);
            $lifecycle = PdpLifecycle::Submitted;
        }

        // Canal Plateforme Agréée : conservé dans raw et journalisé, non interprété.
        if (! empty($clientInvoice['einvoicing_status'])) {
            Log::info('QontoGateway: einvoicing status reported', [
                'external_id'       => $externalId,
                'einvoicing_status' => $clientInvoice['einvoicing_status'],
            ]);
        }

        return new PdpInvoiceResult($externalId, $lifecycle, null, $raw);
    }

    /** Statut commercial Qonto → vocabulaire canonique. */
    private function mapStatus(?string $status): ?PdpLifecycle
    {
        return match ($status) {
            'draft'    => PdpLifecycle::Pending,
            'unpaid'   => PdpLifecycle::Submitted,
            'paid'     => PdpLifecycle::Paid,
            'canceled' => PdpLifecycle::Canceled,
            default    => null,
        };
    }

    /** Repli si un webhook ne porte pas l'objet complet (cf. parseWebhook). */
    private function mapEvent(string $event): ?PdpLifecycle
    {
        return match ($event) {
            'client_invoice.created'   => PdpLifecycle::Pending,
            'client_invoice.finalized' => PdpLifecycle::Submitted,
            'client_invoice.paid'      => PdpLifecycle::Paid,
            'client_invoice.canceled'  => PdpLifecycle::Canceled,
            default                    => null,
        };
    }

    /**
     * Signature Qonto : HMAC-SHA256 hexadécimal du corps brut, avec le secret
     * fourni par nos soins lors de l'abonnement (POST /data_api/webhooks),
     * transmis dans l'en-tête `Qonto-SHA256-Signature` (sans préfixe).
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.qonto.webhook_secret', '');

        if ($secret === '') {
            if (app()->isProduction()) {
                Log::error('QontoGateway: QONTO_WEBHOOK_SECRET not configured in production');
                return false;
            }
            Log::warning('QontoGateway: webhook secret not configured, skipping signature check (non-production)');
            return true;
        }

        $signature = (string) $request->header('Qonto-SHA256-Signature', '');
        $expected  = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
