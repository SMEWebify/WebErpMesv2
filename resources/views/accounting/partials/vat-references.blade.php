{{-- Tables de référence de la matrice de TVA : régimes (fiche client) et natures (fiche article). --}}
<div class="row">

  {{-- ======================= RÉGIMES ======================= --}}
  <div class="col-md-6">
    <x-adminlte-card title="Régimes de TVA (fiche client)" theme="primary" maximizable>
      <div class="table-responsive p-0">
        <table class="table table-hover">
          <thead>
            <tr><th>Code</th><th>Libellé</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($Regimes as $r)
            <tr>
              <td>{{ $r->code }}</td>
              <td>{{ $r->label }}</td>
              <td class="py-0 align-middle text-right">
                <x-ButtonTextEdit :modalTarget="'Regime' . $r->id" />
                <form method="POST" action="{{ route('accounting.vatRegime.destroy', ['id' => $r->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer ce régime ?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
                <form method="POST" action="{{ route('accounting.vatRegime.update', ['id' => $r->id]) }}">
                  <x-adminlte-modal id="Regime{{ $r->id }}" title="Modifier {{ $r->code }}" theme="teal" icon="fa fa-pen" disable-animations>
                    @csrf
                    <div class="form-group"><label>Code</label><input type="text" class="form-control" name="code" value="{{ $r->code }}"></div>
                    <div class="form-group"><label>Libellé</label><input type="text" class="form-control" name="label" value="{{ $r->label }}"></div>
                    <x-slot name="footerSlot">
                      <x-adminlte-button class="btn-flat" type="submit" label="Enregistrer" theme="info" icon="fas fa-save"/>
                    </x-slot>
                  </x-adminlte-modal>
                </form>
              </td>
            </tr>
            @empty
              <x-EmptyDataLine col="3" text="Aucun régime" />
            @endforelse
          </tbody>
        </table>
      </div>
      <form method="POST" action="{{ route('accounting.vatRegime.create') }}" class="mt-2">
        @csrf
        <div class="row">
          <div class="col-4"><input type="text" class="form-control" name="code" placeholder="Code (ex. UE)"></div>
          <div class="col-6"><input type="text" class="form-control" name="label" placeholder="Libellé"></div>
          <div class="col-2"><x-adminlte-button class="btn-flat btn-block" type="submit" label="+" theme="secondary"/></div>
        </div>
      </form>
    </x-adminlte-card>
  </div>

  {{-- ======================= NATURES ======================= --}}
  <div class="col-md-6">
    <x-adminlte-card title="Natures de TVA (fiche article / levier d'imputation)" theme="primary" maximizable>
      <div class="table-responsive p-0">
        <table class="table table-hover">
          <thead>
            <tr><th>Code</th><th>Libellé</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($Natures as $n)
            <tr>
              <td>{{ $n->code }}</td>
              <td>{{ $n->label }}</td>
              <td class="py-0 align-middle text-right">
                <x-ButtonTextEdit :modalTarget="'Nature' . $n->id" />
                <form method="POST" action="{{ route('accounting.vatNature.destroy', ['id' => $n->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer cette nature ?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
                <form method="POST" action="{{ route('accounting.vatNature.update', ['id' => $n->id]) }}">
                  <x-adminlte-modal id="Nature{{ $n->id }}" title="Modifier {{ $n->code }}" theme="teal" icon="fa fa-pen" disable-animations>
                    @csrf
                    <div class="form-group"><label>Code</label><input type="text" class="form-control" name="code" value="{{ $n->code }}"></div>
                    <div class="form-group"><label>Libellé</label><input type="text" class="form-control" name="label" value="{{ $n->label }}"></div>
                    <x-slot name="footerSlot">
                      <x-adminlte-button class="btn-flat" type="submit" label="Enregistrer" theme="info" icon="fas fa-save"/>
                    </x-slot>
                  </x-adminlte-modal>
                </form>
              </td>
            </tr>
            @empty
              <x-EmptyDataLine col="3" text="Aucune nature" />
            @endforelse
          </tbody>
        </table>
      </div>
      <form method="POST" action="{{ route('accounting.vatNature.create') }}" class="mt-2">
        @csrf
        <div class="row">
          <div class="col-4"><input type="text" class="form-control" name="code" placeholder="Code (ex. outillage)"></div>
          <div class="col-6"><input type="text" class="form-control" name="label" placeholder="Libellé"></div>
          <div class="col-2"><x-adminlte-button class="btn-flat btn-block" type="submit" label="+" theme="secondary"/></div>
        </div>
      </form>
    </x-adminlte-card>
  </div>

</div>
