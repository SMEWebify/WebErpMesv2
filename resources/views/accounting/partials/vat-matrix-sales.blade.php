{{-- Matrice de TVA — VENTES : (régime × nature) → code TVA + comptes. --}}
<div class="row">
  <div class="col-md-8">
    <x-adminlte-card title="{{ __('vat.matrix_sales_title') }}" theme="primary" maximizable>
      <div class="table-responsive p-0">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>{{ __('vat.regime') }}</th><th>{{ __('vat.nature') }}</th><th>{{ __('vat.vat_code') }}</th>
              <th>{{ __('vat.sales_account_short') }}</th><th>{{ __('vat.vat_account_short') }}</th><th></th>
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
                <form method="POST" action="{{ route('accounting.vatMatrixSales.destroy', ['id' => $rule->id]) }}" class="d-inline" onsubmit="return confirm(@js(__('vat.confirm_delete_rule')))">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
                <form method="POST" action="{{ route('accounting.vatMatrixSales.update', ['id' => $rule->id]) }}">
                  <x-adminlte-modal id="SalesRule{{ $rule->id }}" title="{{ __('vat.edit_rule') }}" theme="teal" icon="fa fa-pen" size="lg" disable-animations>
                    @csrf
                    @include('accounting.partials.vat-matrix-sales-fields', ['rule' => $rule])
                    <x-slot name="footerSlot">
                      <x-adminlte-button class="btn-flat" type="submit" label="{{ __('vat.save') }}" theme="info" icon="fas fa-save"/>
                    </x-slot>
                  </x-adminlte-modal>
                </form>
              </td>
            </tr>
            @empty
              <x-EmptyDataLine col="6" text="{{ __('vat.no_rule') }}" />
            @endforelse
          </tbody>
        </table>
      </div>
    </x-adminlte-card>
  </div>

  <div class="col-md-4">
    <form method="POST" action="{{ route('accounting.vatMatrixSales.create') }}">
      <x-adminlte-card title="{{ __('vat.new_sales_rule') }}" theme="secondary" maximizable>
        @csrf
        @include('accounting.partials.vat-matrix-sales-fields', ['rule' => null])
        <x-slot name="footerSlot">
          <x-adminlte-button class="btn-flat" type="submit" label="{{ __('vat.add') }}" theme="danger" icon="fas fa-save"/>
        </x-slot>
      </x-adminlte-card>
    </form>
  </div>
</div>
