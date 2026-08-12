@php
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;

// Nom d'affichage de la plateforme active. Le driver est déjà connu par
// PdpManager ; on n'affiche ici que son libellé commercial.
$providerKey   = $submission?->provider ?? config('services.pdp.default');
$providerNames = ['qonto' => 'Qonto', 'superpdp' => 'SUPER PDP'];
$providerName  = $providerNames[$providerKey] ?? ucfirst((string) $providerKey);

$lifecycleLabels = [
    PdpLifecycle::Pending->value      => ['label' => 'Non déposée',      'badge' => 'secondary'],
    PdpLifecycle::Submitted->value    => ['label' => 'Déposée',          'badge' => 'info'],
    PdpLifecycle::Acknowledged->value => ['label' => 'Reçue par le client', 'badge' => 'primary'],
    PdpLifecycle::Rejected->value     => ['label' => 'Rejetée',          'badge' => 'danger'],
    PdpLifecycle::Refused->value      => ['label' => 'Refusée / litige', 'badge' => 'danger'],
    PdpLifecycle::Accepted->value     => ['label' => 'Acceptée',         'badge' => 'success'],
    PdpLifecycle::Paid->value         => ['label' => 'Payée',            'badge' => 'success'],
    PdpLifecycle::Canceled->value     => ['label' => 'Annulée',          'badge' => 'warning'],
];

$currentStatus = $submission?->lifecycle_status ?? PdpLifecycle::Pending->value;
$statusInfo    = $lifecycleLabels[$currentStatus] ?? ['label' => $currentStatus, 'badge' => 'secondary'];

// Un dépôt rejeté peut être corrigé et redéposé ; un dépôt déjà acheminé, non :
// réémettre créerait un doublon chez le destinataire.
$canSubmit = ! $submission || in_array($currentStatus, [
    PdpLifecycle::Pending->value,
    PdpLifecycle::Rejected->value,
]);
@endphp

<x-adminlte-card title="Facturation électronique — {{ $providerName }}" theme="info" theme-mode="outline" collapsible>
    <div class="mb-2">
        <span class="text-muted small">Statut :</span>
        <span class="badge badge-{{ $statusInfo['badge'] }} ml-1">{{ $statusInfo['label'] }}</span>

        @if($submission?->external_id)
            <br><small class="text-muted">Référence {{ $providerName }} : {{ $submission->external_id }}</small>
        @endif

        @if($submission?->submitted_at)
            <br><small class="text-muted">Déposée le : {{ $submission->submitted_at->format('d/m/Y H:i') }}</small>
        @endif

        @if($submission?->rejection_reason)
            <br><small class="text-danger">Motif : {{ $submission->rejection_reason }}</small>
        @endif
    </div>

    {{-- Les refus de la plateforme citent les règles violées (« [BR-CO-10]… ») :
         ils doivent rester lisibles à l'écran, pas disparaître en notification. --}}
    <div class="alert alert-danger d-none mb-2" id="pdp-error-{{ $Invoice->id }}" style="white-space:pre-wrap"></div>

    <div class="d-flex flex-wrap" style="gap:.5rem">
        @if($canSubmit)
        <button type="button"
                class="btn btn-sm btn-info pdp-action-btn"
                data-url="{{ route('invoices.pdp.submit', $Invoice->id) }}">
            <i class="fas fa-paper-plane mr-1"></i>
            {{ $submission ? 'Redéposer' : 'Déposer sur ' . $providerName }}
        </button>
        @endif

        @if($submission?->external_id)
        <button type="button"
                class="btn btn-sm btn-outline-secondary pdp-action-btn"
                data-url="{{ route('invoices.pdp.poll', $Invoice->id) }}">
            <i class="fas fa-sync-alt mr-1"></i>Actualiser le statut
        </button>
        @endif
    </div>
</x-adminlte-card>

@push('js')
<script>
(function () {
    const errorBox = document.getElementById('pdp-error-{{ $Invoice->id }}');

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
        errorBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function pdpRequest(url, btn) {
        errorBox.classList.add('d-none');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>En cours…';

        // Authentification par cookie de session : la page est déjà
        // authentifiée, il n'y a pas de jeton porteur à transmettre.
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        })
        .then(r => r.json().catch(() => ({})).then(data => ({ ok: r.ok, status: r.status, data })))
        .then(({ ok, status, data }) => {
            if (ok) {
                location.reload();
                return;
            }
            showError(data.message ?? ('La plateforme a renvoyé une erreur (HTTP ' + status + ').'));
            btn.disabled = false;
            btn.innerHTML = btn.dataset.label;
        })
        .catch(() => {
            showError('Erreur réseau : la requête n\'a pas abouti.');
            btn.disabled = false;
            btn.innerHTML = btn.dataset.label;
        });
    }

    document.querySelectorAll('.pdp-action-btn').forEach(btn => {
        btn.dataset.label = btn.innerHTML;
        btn.addEventListener('click', () => pdpRequest(btn.dataset.url, btn));
    });
})();
</script>
@endpush
