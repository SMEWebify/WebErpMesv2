{{-- Avoir entier ou partiel sur une facture émise (App\Services\Invoicing\CreditNoteBuilder). --}}
@php
  $cnBuilder  = app(\App\Services\Invoicing\CreditNoteBuilder::class);
  $cnCurrency = app('Factory')->curency ?? 'EUR';
  $cnMoney    = fn ($v) => \Illuminate\Support\Number::currency((float) $v, $cnCurrency, config('app.locale'));
@endphp
<form action="{{ route('credit-notes.store.from.invoice') }}" method="POST" class="card card-outline card-info mt-3 mb-3">
  @csrf
  <input type="hidden" name="restore_quantities_sent" value="1">
  <div class="card-header py-2">
    <h3 class="card-title">
      <i class="fas fa-file-circle-minus me-2"></i>{{ __('credit_notes.form.title') }}
      <x-guide-help guide="facturation" section="faire-un-avoir" />
    </h3>
  </div>
  <div class="card-body py-2">
    <p class="small text-body-secondary mb-2">{{ __('credit_notes.form.help') }}</p>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-2">
        <thead>
          <tr>
            <th style="width: 2rem;"></th>
            <th>{{ __('credit_notes.form.line') }}</th>
            <th class="text-end">{{ __('credit_notes.form.invoiced') }}</th>
            <th class="text-end">{{ __('credit_notes.form.creditable') }}</th>
            <th class="text-end">{{ __('credit_notes.form.qty') }}</th>
            <th class="text-end">{{ __('credit_notes.form.unit_price') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($Invoice->InvoiceLines as $cnLine)
            @php
              $cnLeft      = $cnBuilder->creditable($cnLine);
              $cnDeduction = $cnLine->down_payment_line_id !== null;
              $cnDisabled  = $cnDeduction || $cnLeft['qty'] <= 0 || $cnLeft['ht'] <= 0.004;
            @endphp
            <tr @class(['text-body-secondary' => $cnDisabled])>
              <td>
                <input type="checkbox" class="form-check-input" name="selected_invoice_lines[]" value="{{ $cnLine->id }}"
                       id="cn-line-{{ $cnLine->id }}" @disabled($cnDisabled)>
              </td>
              <td>
                <label for="cn-line-{{ $cnLine->id }}" class="mb-0">
                  {{ $cnLine->display_code ?: $cnLine->display_label }}
                  @if($cnLine->display_code)<span class="small text-body-secondary">{{ $cnLine->display_label }}</span>@endif
                </label>
                @if($cnDeduction)
                  <span class="badge text-bg-info ms-1">{{ __('credit_notes.form.deduction') }}</span>
                @elseif($cnDisabled)
                  <span class="badge text-bg-secondary ms-1">{{ __('credit_notes.form.fully_credited') }}</span>
                @endif
              </td>
              <td class="text-end small">{{ format_qty($cnLine->qty) }} × {{ $cnMoney($cnLine->resolved_unit_price) }}</td>
              <td class="text-end small">{{ format_qty($cnLeft['qty']) }} · {{ $cnMoney($cnLeft['ht']) }} HT</td>
              <td class="text-end">
                <input type="number" name="qty[{{ $cnLine->id }}]" class="form-control form-control-sm text-end d-inline-block"
                       style="width: 6rem;" min="0" step="0.001" max="{{ $cnLeft['qty'] }}" value="{{ $cnLeft['qty'] }}" @disabled($cnDisabled)>
              </td>
              <td class="text-end">
                <input type="number" name="unit_price[{{ $cnLine->id }}]" class="form-control form-control-sm text-end d-inline-block"
                       style="width: 7rem;" min="0" step="0.01" max="{{ $cnLeft['unit_price'] }}" value="{{ $cnLeft['unit_price'] }}" @disabled($cnDisabled)>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="form-check mb-1">
      <input type="checkbox" class="form-check-input" name="restore_quantities" value="1" id="cn-restore" checked>
      <label class="form-check-label" for="cn-restore">{{ __('credit_notes.form.restore') }}</label>
    </div>
    <p class="small text-body-secondary mb-2">{{ __('credit_notes.form.restore_help') }}</p>
    <button type="submit" class="btn btn-info btn-sm">
      <i class="fas fa-save me-1"></i>{{ __('credit_notes.form.submit') }}
    </button>
  </div>
</form>
