@php
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;

$lifecycleLabels = [
    PdpLifecycle::Pending->value      => ['label' => 'Non soumise',  'badge' => 'secondary'],
    PdpLifecycle::Submitted->value    => ['label' => 'Déposée',      'badge' => 'info'],
    PdpLifecycle::Acknowledged->value => ['label' => 'Accusé reçu', 'badge' => 'primary'],
    PdpLifecycle::Rejected->value     => ['label' => 'Rejetée',      'badge' => 'danger'],
    PdpLifecycle::Refused->value      => ['label' => 'Refusée',      'badge' => 'danger'],
    PdpLifecycle::Accepted->value     => ['label' => 'Acceptée',     'badge' => 'success'],
    PdpLifecycle::Paid->value         => ['label' => 'Payée',        'badge' => 'success'],
];

$currentStatus = $submission?->lifecycle_status ?? PdpLifecycle::Pending->value;
$statusInfo    = $lifecycleLabels[$currentStatus] ?? ['label' => $currentStatus, 'badge' => 'secondary'];
$canSubmit     = ! $submission || in_array($currentStatus, [
    PdpLifecycle::Pending->value,
    PdpLifecycle::Rejected->value,
]);
@endphp

<x-adminlte-card title="Facturation électronique - Qonto" theme="info" theme-mode="outline" collapsible>
    <div class="mb-2">
        <span class="text-muted small">Statut :</span>
        <span class="badge badge-{{ $statusInfo['badge'] }} ml-1">{{ $statusInfo['label'] }}</span>

        @if($submission?->external_id)
            <br><small class="text-muted">ID Qonto : {{ $submission->external_id }}</small>
        @endif

        @if($submission?->submitted_at)
            <br><small class="text-muted">Déposée le : {{ $submission->submitted_at->format('d/m/Y H:i') }}</small>
        @endif

        @if($submission?->rejection_reason)
            <br><small class="text-danger">Motif : {{ $submission->rejection_reason }}</small>
        @endif
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @if($canSubmit)
        <button type="button"
                class="btn btn-sm btn-info qonto-submit-btn"
                data-invoice-id="{{ $Invoice->id }}"
                data-url="{{ route('api.integrations.qonto.invoices.submit', $Invoice->id) }}">
            <i class="fas fa-paper-plane mr-1"></i>
            {{ $submission ? 'Re-soumettre' : 'Soumettre à Qonto' }}
        </button>
        @endif

        @if($submission?->external_id)
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
