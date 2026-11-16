{{-- Factures d'acompte de la commande (App\Services\Invoicing\DownPayments). --}}
@php
  $downPaymentService = app(\App\Services\Invoicing\DownPayments::class);
  $downPayments       = $downPaymentService->forOrder($Order->id);
  $downPaymentTotal   = $downPaymentService->orderTotal($Order);
  $dpCurrency         = app('Factory')->curency ?? 'EUR';
  $dpMoney            = fn ($v) => \Illuminate\Support\Number::currency((float) $v, $dpCurrency, config('app.locale'));
@endphp
<x-adminlte-card title="{{ __('down_payments.card.title') }}" theme="secondary" theme-mode="outline" icon="fas fa-hand-holding-usd" collapsible>
  <p class="small text-body-secondary mb-2">{{ __('down_payments.card.help') }}</p>

  @if($downPayments->isEmpty())
    <p class="small text-body-secondary">{{ __('down_payments.card.none') }}</p>
  @else
    <table class="table table-sm small mb-2">
      <thead>
        <tr>
          <th>{{ __('down_payments.card.invoice') }}</th>
          <th class="text-end">{{ __('down_payments.card.ht') }}</th>
          <th class="text-end">{{ __('down_payments.card.paid') }}</th>
          <th class="text-end">{{ __('down_payments.card.deducted') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($downPayments as $downPayment)
          @php $dpSummary = $downPaymentService->summary($downPayment); @endphp
          <tr>
            <td>
              <a href="{{ route('invoices.show', ['id' => $downPayment->id]) }}">{{ $downPayment->code }}</a>
              @if((int) $downPayment->statu === 1)
                <span class="badge text-bg-secondary ms-1">{{ __('down_payments.card.draft') }}</span>
              @endif
            </td>
            <td class="text-end">{{ $dpMoney($dpSummary['ht']) }}</td>
            <td class="text-end">{{ $dpMoney($dpSummary['paid']) }}</td>
            <td class="text-end">{{ $dpMoney($dpSummary['deducted']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if((int) $Order->statu !== 6 && $downPaymentTotal > 0)
    <form method="POST" action="{{ route('invoices.down-payments.store', ['orderId' => $Order->id]) }}">
      @csrf
      <div class="small text-body-secondary mb-1">{{ __('down_payments.card.order_total') }} : {{ $dpMoney($downPaymentTotal) }}</div>
      <div class="input-group input-group-sm">
        <input type="number" name="value" class="form-control text-end" min="0.01" step="0.01" required
               placeholder="{{ __('down_payments.card.value') }}" aria-label="{{ __('down_payments.card.value') }}">
        <select name="mode" class="form-select" style="max-width: 7rem;" aria-label="{{ __('down_payments.card.value') }}">
          <option value="percent">{{ __('down_payments.card.mode_percent') }}</option>
          <option value="amount">{{ __('down_payments.card.mode_amount') }}</option>
        </select>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>{{ __('down_payments.card.create') }}
        </button>
      </div>
    </form>
  @endif
</x-adminlte-card>
