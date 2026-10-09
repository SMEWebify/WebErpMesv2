{{-- Champs partagés création/édition d'une règle de matrice VENTES. --}}
<div class="form-group">
  <label>{{ __('vat.regime_customer') }}</label>
  <select class="form-control" name="vat_regime_id">
    @foreach ($Regimes as $r)
      <option value="{{ $r->id }}" @if(optional($rule)->vat_regime_id == $r->id) selected @endif>{{ $r->code }} — {{ $r->label }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label>{{ __('vat.nature_product') }}</label>
  <select class="form-control" name="vat_nature_id">
    @foreach ($Natures as $n)
      <option value="{{ $n->id }}" @if(optional($rule)->vat_nature_id == $n->id) selected @endif>{{ $n->code }} — {{ $n->label }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label>{{ __('vat.vat_code') }}</label>
  <select class="form-control" name="accounting_vats_id">
    @foreach ($VATSelect as $v)
      <option value="{{ $v->id }}" @if(optional($rule)->accounting_vats_id == $v->id) selected @endif>{{ $v->label }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label>{{ __('vat.sales_account') }}</label>
  <input type="text" class="form-control" name="sales_account" value="{{ optional($rule)->sales_account }}" placeholder="701000">
</div>
<div class="form-group">
  <label>{{ __('vat.vat_collected_account') }}</label>
  <input type="text" class="form-control" name="vat_account" value="{{ optional($rule)->vat_account }}" placeholder="{{ __('vat.vat_collected_hint') }}">
</div>
