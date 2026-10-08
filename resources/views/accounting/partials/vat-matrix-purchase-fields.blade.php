{{-- Champs partagés création/édition d'une règle de matrice ACHATS. --}}
<div class="form-group">
  <label>Régime (fournisseur)</label>
  <select class="form-control" name="vat_regime_id">
    @foreach ($Regimes as $r)
      <option value="{{ $r->id }}" @if(optional($rule)->vat_regime_id == $r->id) selected @endif>{{ $r->code }} — {{ $r->label }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label>Nature (article)</label>
  <select class="form-control" name="vat_nature_id">
    @foreach ($Natures as $n)
      <option value="{{ $n->id }}" @if(optional($rule)->vat_nature_id == $n->id) selected @endif>{{ $n->code }} — {{ $n->label }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label>Code TVA</label>
  <select class="form-control" name="accounting_vats_id">
    @foreach ($VATSelect as $v)
      <option value="{{ $v->id }}" @if(optional($rule)->accounting_vats_id == $v->id) selected @endif>{{ $v->label }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label>Compte d'achat</label>
  <input type="text" class="form-control" name="purchase_account" value="{{ optional($rule)->purchase_account }}" placeholder="ex. 601000">
</div>
<div class="form-group">
  <label>Compte TVA déductible</label>
  <input type="text" class="form-control" name="vat_account" value="{{ optional($rule)->vat_account }}" placeholder="ex. 445660">
</div>
<div class="form-group">
  <label>Compte TVA due (autoliquidation)</label>
  <input type="text" class="form-control" name="vat_account_autoliq" value="{{ optional($rule)->vat_account_autoliq }}" placeholder="ex. 445200 (repère, écriture manuelle)">
</div>
<div class="form-group">
  <div class="custom-control custom-switch">
    <input type="checkbox" class="custom-control-input" id="manual_vat_{{ optional($rule)->id ?? 'new' }}" name="manual_vat" value="1" @if(optional($rule)->manual_vat) checked @endif>
    <label class="custom-control-label" for="manual_vat_{{ optional($rule)->id ?? 'new' }}">Autoliquidation — écriture TVA à la main</label>
  </div>
</div>
