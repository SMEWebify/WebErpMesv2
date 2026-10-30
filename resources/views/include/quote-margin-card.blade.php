{{-- Marge du devis : information interne, jamais incluse dans une vue d'impression. --}}
@php
  $marginMoney = fn ($value) => \Illuminate\Support\Number::currency($value, $Margin['currency'], app()->getLocale());
  $marginPct = fn ($value) => $value === null ? '—' : number_format($value, 2, ',', ' ') . ' %';
  $marginClass = $Margin['below_threshold'] ? 'text-danger' : 'text-success';
@endphp
<x-adminlte-card title="{{ __('quote_margin.title') }}" theme="secondary" theme-mode="outline" icon="fas fa-percentage" collapsible="collapsed">
  <p class="small text-body-secondary mb-2"><i class="fas fa-lock me-1"></i>{{ __('quote_margin.internal') }}</p>
  <div class="table-responsive p-0">
    <table class="table table-sm mb-2">
      <tr>
        <td style="width:50%">{{ __('quote_margin.cost_total') }}</td>
        <td class="text-end">{{ $marginMoney($Margin['cost_total']) }}</td>
      </tr>
      <tr>
        <td>{{ __('quote_margin.sale_total') }}</td>
        <td class="text-end">{{ $marginMoney($Margin['sale_total']) }}</td>
      </tr>
      <tr>
        <td>{{ __('quote_margin.margin') }}</td>
        <td class="text-end fw-bold {{ $marginClass }}">{{ $marginMoney($Margin['margin']) }}</td>
      </tr>
      <tr>
        <td>{{ __('quote_margin.margin_rate') }}</td>
        <td class="text-end fw-bold {{ $marginClass }}"
            @if($Margin['below_threshold']) title="{{ __('quote_margin.below_threshold', ['threshold' => $Margin['threshold']]) }}" @endif>
          @if($Margin['below_threshold'])<i class="fas fa-exclamation-triangle me-1"></i>@endif{{ $marginPct($Margin['margin_rate']) }}
        </td>
      </tr>
      <tr>
        <td>{{ __('quote_margin.markup_rate') }}</td>
        <td class="text-end">{{ $marginPct($Margin['markup_rate']) }}</td>
      </tr>
    </table>
  </div>
  @if($Margin['lines_without_cost'] > 0)
    <div class="small text-warning-emphasis mb-2">
      <i class="fas fa-exclamation-circle me-1"></i>{{ trans_choice('quote_margin.lines_without_cost', $Margin['lines_without_cost'], ['count' => $Margin['lines_without_cost']]) }}
    </div>
  @endif
  <p class="small text-body-secondary mb-2">{{ __('quote_margin.cost_help') }}</p>

  @if($Margin['converted'])
    <div class="small text-body-secondary"><i class="fas fa-lock me-1"></i>{{ __('quote_margin.locked_converted') }}</div>
  @elseif((int) $Quote->statu === 1)
    <form method="POST" action="{{ route('quotes.margin.apply', ['id' => $Quote->id]) }}"
          onsubmit="return confirm(@js(__('quote_margin.apply_confirm')))">
      @csrf
      <label for="margin_rate" class="form-label small fw-bold mb-1">{{ __('quote_margin.apply_title') }}</label>
      <div class="input-group input-group-sm">
        <input type="number" class="form-control" name="margin_rate" id="margin_rate" min="0" step="0.01" required
               placeholder="{{ __('quote_margin.apply_rate') }}" aria-label="{{ __('quote_margin.apply_rate') }}"
               value="{{ old('margin_rate') }}">
        <span class="input-group-text">%</span>
      </div>
      <div class="form-text mb-2">{{ __('quote_margin.apply_help') }}</div>
      <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-calculator me-1"></i>{{ __('quote_margin.apply_button') }}</button>
    </form>
  @endif
</x-adminlte-card>
