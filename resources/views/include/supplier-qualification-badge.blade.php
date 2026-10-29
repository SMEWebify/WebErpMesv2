{{-- Badge de qualification fournisseur (App\Services\Purchases\SupplierQualification) : $status --}}
@php
  $badge = [
    'qualified'      => ['text-bg-success', 'fa-circle-check'],
    'review_overdue' => ['text-bg-warning', 'fa-clock'],
    'unqualified'    => ['text-bg-danger', 'fa-ban'],
  ][$status] ?? ['text-bg-secondary', 'fa-question'];
@endphp
<span class="badge {{ $badge[0] }}"><i class="fas {{ $badge[1] }} me-1"></i>{{ __('supplier_qualification.status.' . $status) }}</span>
