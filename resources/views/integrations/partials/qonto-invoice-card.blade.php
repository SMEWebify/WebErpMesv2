@php
use App\Services\Integrations\QontoInvoiceSyncService;

$lifecycleLabels = [
    QontoInvoiceSyncService::LIFECYCLE_PENDING      => ['label' => 'Non soumise',  'badge' => 'secondary'],
    QontoInvoiceSyncService::LIFECYCLE_SUBMITTED    => ['label' => 'Déposée',      'badge' => 'info'],
    QontoInvoiceSyncService::LIFECYCLE_ACKNOWLEDGED => ['label' => 'Accusé reçu', 'badge' => 'primary'],
    QontoInvoiceSyncService::LIFECYCLE_REJECTED     => ['label' => 'Rejetée',      'badge' => 'danger'],
    QontoInvoiceSyncService::LIFECYCLE_REFUSED      => ['label' => 'Refusée',      'badge' => 'danger'],
    QontoInvoiceSyncService::LIFECYCLE_ACCEPTED     => ['label' => 'Acceptée',     'badge' => 'success'],
    QontoInvoiceSyncService::LIFECYCLE_PAID         => ['label' => 'Payée',        'badge' => 'success'],
];

$currentStatus = $qontoMapping?->lifecycle_status ?? QontoInvoiceSyncService::LIFECYCLE_PENDING;
$statusInfo    = $lifecycleLabels[$currentStatus] ?? ['label' => $currentStatus, 'badge' => 'secondary'];
$canSubmit     = ! $qontoMapping || in_array($currentStatus, [
    QontoInvoiceSyncService::LIFECYCLE_PENDING,
    QontoInvoiceSyncService::LIFECYCLE_REJECTED,
]);
@endphp

<x-adminlte-card title="Facturation électronique — Qonto" theme="info" theme-mode="outline" collapsible>
    <div class="mb-2">
        <span class="text-muted small">Statut :</span>
        <span class="badge badge-{{ $statusInfo['badge'] }} ml-1">{{ $statusInfo['label'] }}</span>

        @if($qontoMapping?->qonto_invoice_id)
            <br><small class="text-muted">ID Qonto : {{ $qontoMapping->qonto_invoice_id }}</small>
        @endif

        @if($qontoMapping?->submitted_at)
            <br><small class="text-muted">Déposée le : {{ $qontoMapping->submitted_at->format('d/m/Y H:i') }}</small>
        @endif

        @if($qontoMapping?->rejection_reason)
            <br><small class="text-danger">Motif : {{ $qontoMapping->rejection_reason }}</small>
        @endif
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @if($canSubmit)
        <button type="button"
                class="btn btn-sm btn-info qonto-submit-btn"
                data-invoice-id="{{ $Invoice->id }}"
                data-url="{{ route('api.integrations.qonto.invoices.submit', $Invoice->id) }}">
            <i class="fas fa-paper-plane mr-1"></i>
            {{ $qontoMapping ? 'Re-soumettre' : 'Soumettre à Qonto' }}
        </button>
        @endif

        @if($qontoMapping?->qonto_invoice_id)
        <button type="button"
                class="btn btn-sm btn-outline-secondary qonto-poll-btn"
                data-invoice-id="{{ $Invoice->id }}"
                data-url="{{ route('api.integrations.qonto.invoices.poll', $Invoice->id) }}">
            <i class="fas fa-sync-alt mr-1"></i>Actualiser le statut
        </button>
        @endif
    </div>
</x-adminlte-card>

@push('js')
<script>
(function () {
    const apiToken = document.querySelector('meta[name="api-token"]')?.content ?? '';

    function qontoRequest(url, btn, successMsg) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>En cours…';

        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + apiToken,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (ok) {
                toastr.success(successMsg);
                setTimeout(() => location.reload(), 1200);
            } else {
                toastr.error(data.message ?? 'Une erreur est survenue.');
                btn.disabled = false;
                btn.innerHTML = btn.dataset.label;
            }
        })
        .catch(() => {
            toastr.error('Erreur réseau.');
            btn.disabled = false;
        });
    }

    document.querySelectorAll('.qonto-submit-btn').forEach(btn => {
        btn.dataset.label = btn.innerHTML;
        btn.addEventListener('click', () => qontoRequest(btn.dataset.url, btn, 'Facture soumise à Qonto.'));
    });

    document.querySelectorAll('.qonto-poll-btn').forEach(btn => {
        btn.dataset.label = btn.innerHTML;
        btn.addEventListener('click', () => qontoRequest(btn.dataset.url, btn, 'Statut mis à jour.'));
    });
})();
</script>
@endpush
