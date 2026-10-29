{{--
  Qualification fournisseur et passe-droits « fournisseur non qualifié »
  $qualification : ['status', 'detail', 'overrides'] (SupplierQualificationOverride)
  $showDocument  : afficher la commande concernée (fiche société)
--}}
<x-adminlte-card title="{{ __('supplier_qualification.label') }}" theme="secondary" theme-mode="outline" icon="fas fa-user-check">
  <p class="mb-2">
    @include('include.supplier-qualification-badge', ['status' => $qualification['status']])
    @if ($qualification['detail'])
      <span class="small text-body-secondary ms-1">{{ $qualification['detail'] }}</span>
    @endif
  </p>
  @if ($qualification['overrides']->isNotEmpty())
    <p class="small fw-bold mb-1">{{ __('supplier_qualification.overrides_title') }}</p>
    <ul class="list-unstyled small mb-0">
      @foreach ($qualification['overrides'] as $override)
        <li class="mb-2">
          <i class="fas fa-hand-paper text-danger me-1"></i>
          {{ $override->created_at?->format('d/m/Y H:i') }} — {{ $override->user?->name ?? '—' }}
          ({{ __('supplier_qualification.context.' . $override->context) }}@if (! empty($showDocument) && $override->document), <a href="{{ route('purchases.show', ['id' => $override->document_id]) }}">{{ $override->document->code }}</a>@endif)
          <div class="text-body-secondary" style="white-space: pre-line;">{{ $override->reason }}</div>
        </li>
      @endforeach
    </ul>
  @endif
</x-adminlte-card>
