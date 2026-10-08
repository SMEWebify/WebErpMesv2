{{-- Matrice de TVA — VENTES : (régime × nature) → code TVA + comptes. --}}
<div class="row">
  <div class="col-md-8">
    <x-adminlte-card title="Matrice TVA — ventes" theme="primary" maximizable>
      <div class="table-responsive p-0">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Régime</th><th>Nature</th><th>Code TVA</th>
              <th>Compte vente</th><th>Compte TVA</th><th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($Rules as $rule)
            <tr>
              <td>{{ $rule->regime?->code }}</td>
              <td>{{ $rule->nature?->code }}</td>
              <td>{{ $rule->VAT?->label }}</td>
              <td>{{ $rule->sales_account }}</td>
              <td>{{ $rule->vat_account }}</td>
              <td class="py-0 align-middle text-right">
                <x-ButtonTextEdit :modalTarget="'SalesRule' . $rule->id" />
                <form method="POST" action="{{ route('accounting.vatMatrixSales.destroy', ['id' => $rule->id]) }}" class="d-inline" onsubmit="return confirm('Supprimer cette règle ?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
                <form method="POST" action="{{ route('accounting.vatMatrixSales.update', ['id' => $rule->id]) }}">
                  <x-adminlte-modal id="SalesRule{{ $rule->id }}" title="Modifier la règle" theme="teal" icon="fa fa-pen" size="lg" disable-animations>
                    @csrf
                    @include('accounting.partials.vat-matrix-sales-fields', ['rule' => $rule])
                    <x-slot name="footerSlot">
                      <x-adminlte-button class="btn-flat" type="submit" label="Enregistrer" theme="info" icon="fas fa-save"/>
                    </x-slot>
                  </x-adminlte-modal>
                </form>
              </td>
            </tr>
            @empty
              <x-EmptyDataLine col="6" text="Aucune règle — sans règle, la ligne retombe sur le code TVA par défaut." />
            @endforelse
          </tbody>
        </table>
      </div>
    </x-adminlte-card>
  </div>

  <div class="col-md-4">
    <form method="POST" action="{{ route('accounting.vatMatrixSales.create') }}">
      <x-adminlte-card title="Nouvelle règle (vente)" theme="secondary" maximizable>
        @csrf
        @include('accounting.partials.vat-matrix-sales-fields', ['rule' => null])
        <x-slot name="footerSlot">
          <x-adminlte-button class="btn-flat" type="submit" label="Ajouter" theme="danger" icon="fas fa-save"/>
        </x-slot>
      </x-adminlte-card>
    </form>
  </div>
</div>
