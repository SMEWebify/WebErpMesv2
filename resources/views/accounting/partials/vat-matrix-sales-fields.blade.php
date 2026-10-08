{{-- Champs partagés création/édition d'une règle de matrice VENTES. --}}
<div class="form-group">
  <label>Régime (client)</label>
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
  <label>Compte de vente</label>
  <input type="text" class="form-control" name="sales_account" value="{{ optional($rule)->sales_account }}" placeholder="ex. 701000">
</div>
<div class="form-group">
  <label>Compte TVA collectée</label>
  <input type="text" class="form-control" name="vat_account" value="{{ optional($rule)->vat_account }}" placeholder="ex. 445710 (vide si exonéré)">
</div>
