{{-- Tables de référence de la matrice de TVA : régimes (fiche client) et natures (fiche article). --}}
<div class="row">

  {{-- ======================= RÉGIMES ======================= --}}
  <div class="col-md-6">
    <x-adminlte-card title="{{ __('vat.regimes_title') }}" theme="primary" maximizable>
      <div class="table-responsive p-0">
        <table class="table table-hover">
          <thead>
            <tr><th>{{ __('vat.code') }}</th><th>{{ __('vat.label') }}</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($Regimes as $r)
            <tr>
              <td>{{ $r->code }}</td>
              <td>{{ $r->label }}</td>
              <td class="py-0 align-middle text-right">
                <x-ButtonTextEdit :modalTarget="'Regime' . $r->id" />
                <form method="POST" action="{{ route('accounting.vatRegime.destroy', ['id' => $r->id]) }}" class="d-inline" onsubmit="return confirm(@js(__('vat.confirm_delete_regime')))">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
                <form method="POST" action="{{ route('accounting.vatRegime.update', ['id' => $r->id]) }}">
                  <x-adminlte-modal id="Regime{{ $r->id }}" title="{{ __('vat.edit_title', ['code' => $r->code]) }}" theme="teal" icon="fa fa-pen" disable-animations>
                    @csrf
                    <div class="form-group"><label>{{ __('vat.code') }}</label><input type="text" class="form-control" name="code" value="{{ $r->code }}"></div>
                    <div class="form-group"><label>{{ __('vat.label') }}</label><input type="text" class="form-control" name="label" value="{{ $r->label }}"></div>
                    <x-slot name="footerSlot">
                      <x-adminlte-button class="btn-flat" type="submit" label="{{ __('vat.save') }}" theme="info" icon="fas fa-save"/>
                    </x-slot>
                  </x-adminlte-modal>
                </form>
              </td>
            </tr>
            @empty
              <x-EmptyDataLine col="3" text="{{ __('vat.no_regime') }}" />
            @endforelse
          </tbody>
        </table>
      </div>
      <form method="POST" action="{{ route('accounting.vatRegime.create') }}" class="mt-2">
        @csrf
        <div class="row">
          <div class="col-4"><input type="text" class="form-control" name="code" placeholder="{{ __('vat.regime_code_example') }}"></div>
          <div class="col-6"><input type="text" class="form-control" name="label" placeholder="{{ __('vat.label') }}"></div>
          <div class="col-2"><x-adminlte-button class="btn-flat btn-block" type="submit" label="+" theme="secondary"/></div>
        </div>
      </form>
    </x-adminlte-card>
  </div>

  {{-- ======================= NATURES ======================= --}}
  <div class="col-md-6">
    <x-adminlte-card title="{{ __('vat.natures_title') }}" theme="primary" maximizable>
      <div class="table-responsive p-0">
        <table class="table table-hover">
          <thead>
            <tr><th>{{ __('vat.code') }}</th><th>{{ __('vat.label') }}</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($Natures as $n)
            <tr>
              <td>{{ $n->code }}</td>
              <td>{{ $n->label }}</td>
              <td class="py-0 align-middle text-right">
                <x-ButtonTextEdit :modalTarget="'Nature' . $n->id" />
                <form method="POST" action="{{ route('accounting.vatNature.destroy', ['id' => $n->id]) }}" class="d-inline" onsubmit="return confirm(@js(__('vat.confirm_delete_nature')))">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
                <form method="POST" action="{{ route('accounting.vatNature.update', ['id' => $n->id]) }}">
                  <x-adminlte-modal id="Nature{{ $n->id }}" title="{{ __('vat.edit_title', ['code' => $n->code]) }}" theme="teal" icon="fa fa-pen" disable-animations>
                    @csrf
                    <div class="form-group"><label>{{ __('vat.code') }}</label><input type="text" class="form-control" name="code" value="{{ $n->code }}"></div>
                    <div class="form-group"><label>{{ __('vat.label') }}</label><input type="text" class="form-control" name="label" value="{{ $n->label }}"></div>
                    <x-slot name="footerSlot">
                      <x-adminlte-button class="btn-flat" type="submit" label="{{ __('vat.save') }}" theme="info" icon="fas fa-save"/>
                    </x-slot>
                  </x-adminlte-modal>
                </form>
              </td>
            </tr>
            @empty
              <x-EmptyDataLine col="3" text="{{ __('vat.no_nature') }}" />
            @endforelse
          </tbody>
        </table>
      </div>
      <form method="POST" action="{{ route('accounting.vatNature.create') }}" class="mt-2">
        @csrf
        <div class="row">
          <div class="col-4"><input type="text" class="form-control" name="code" placeholder="{{ __('vat.nature_code_example') }}"></div>
          <div class="col-6"><input type="text" class="form-control" name="label" placeholder="{{ __('vat.label') }}"></div>
          <div class="col-2"><x-adminlte-button class="btn-flat btn-block" type="submit" label="+" theme="secondary"/></div>
        </div>
      </form>
    </x-adminlte-card>
  </div>

</div>
