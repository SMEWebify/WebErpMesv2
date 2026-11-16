{{-- Acomptes : synthèse d'une facture d'acompte, ou déductions d'une facture (App\Services\Invoicing\DownPayments). --}}
@php
  $dpService  = app(\App\Services\Invoicing\DownPayments::class);
  $dpCurrency = app('Factory')->curency ?? 'EUR';
  $dpMoney    = fn ($v) => \Illuminate\Support\Number::currency((float) $v, $dpCurrency, config('app.locale'));
  $dpType     = (int) $Invoice->invoice_type;
@endphp

@if($dpType === \App\Services\Invoicing\DownPayments::TYPE)
  @php $dpSummary = $dpService->summary($Invoice); @endphp
  <div class="alert alert-info py-2 mb-3">
    <i class="fas fa-hand-holding-usd me-2"></i>
    {{ __('down_payments.invoice.is_down_payment', [
        'order'     => $Invoice->order?->code ?? '—',
        'ht'        => $dpMoney($dpSummary['ht']),
        'deducted'  => $dpMoney($dpSummary['deducted']),
        'remaining' => $dpMoney($dpSummary['remaining']),
    ]) }}
    @if($Invoice->order)
      <a href="{{ route('orders.show', ['id' => $Invoice->order->id]) }}" class="ms-1">{{ $Invoice->order->code }}</a>
    @endif
  </div>
@elseif($dpType === 1)
  @php
    $dpDeducted = $dpService->deductedInvoices($Invoice);
    $dpOrderIds = $Invoice->invoiceLines->pluck('orderLine.orders_id')->filter()->unique();
    $dpAcomptes = $dpOrderIds->flatMap(fn ($orderId) => $dpService->forOrder((int) $orderId));
    // Acompte qui existe mais n'est pas déduit ici : brouillon ou impayé.
    $dpUnpaid   = $dpAcomptes
        ->reject(fn ($a) => $dpDeducted->contains('id', $a->id))
        ->filter(function ($a) use ($dpService) {
            $s = $dpService->summary($a);

            return $s['remaining'] > 0.005 && $s['available'] < 0.005;
        });
  @endphp

  @if($dpAcomptes->isNotEmpty())
    <div class="alert alert-light border py-2 mb-3 d-flex flex-wrap align-items-center gap-2">
      <i class="fas fa-hand-holding-usd"></i>
      @if($dpDeducted->isNotEmpty())
        <span>{{ __('down_payments.invoice.deducts') }} :</span>
        @foreach($dpDeducted as $dpInvoice)
          <a href="{{ route('invoices.show', ['id' => $dpInvoice->id]) }}" class="badge text-bg-info">{{ $dpInvoice->code }}</a>
        @endforeach
      @endif
      @foreach($dpUnpaid as $dpInvoice)
        <span class="text-warning-emphasis small">
          <i class="fas fa-exclamation-triangle me-1"></i>{{ __('down_payments.invoice.unpaid_warning', ['code' => $dpInvoice->code]) }}
        </span>
      @endforeach
      @if((int) $Invoice->statu === 1)
        <form method="POST" action="{{ route('invoices.down-payments.recompute', ['id' => $Invoice->id]) }}" class="ms-auto">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-sync-alt me-1"></i>{{ __('down_payments.invoice.recompute') }}
          </button>
        </form>
      @endif
    </div>
  @endif
@endif
