      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.accounting_allocations_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{__('general_content.account_number_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.vat_trans_key') }}</th>
                    <th>{{__('general_content.vat_account_trans_key') }}</th>
                    <th>{{__('general_content.code_account_trans_key') }}</th>
                    <th>{{__('general_content.type_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($Allocations as $Allocation)
                  <tr>
                    <td>{{ $Allocation->account }}</td>
                    <td>{{ $Allocation->label }}</td>
                    <td>{{ $Allocation->VAT['label'] }}</td>
                    <td>{{ $Allocation->vat_account }}</td>
                    <td>{{ $Allocation->code_account }}</td>
                    <td>
                      @if($Allocation->type_imputation  == 1) {{__('general_content.sale_trans_key') }} @endif 
                      @if($Allocation->type_imputation  == 2) {{__('general_content.purchase_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 3) {{__('general_content.advance_payment_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 4) {{__('general_content.tax_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 5) {{__('general_content.purchase_stock_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 6) {{__('general_content.other_trans_key') }} @endif
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $Allocation->id }} -->
                      <x-ButtonTextEdit :modalTarget="'Allocation' . $Allocation->id" />
                      <!-- Modal {{ $Allocation->id }} -->
                      <form method="POST" action="{{ route('accounting.allocation.update', ['id' => $Allocation->id]) }}" enctype="multipart/form-data">
                        <x-adminlte-modal id="Allocation{{ $Allocation->id }}" title="Update {{ $Allocation->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $Allocation->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="accounting_vats_id">{{__('general_content.vat_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <select class="form-control" name="accounting_vats_id" id="accounting_vats_id">
                                  @forelse ($VATSelect as $item)
                                  <option value="{{ $item->id }}" @if($Allocation->accounting_vats_id == $item->id ) Selected @endif>{{ $item->label }}</option>
                                  @empty
                                  <option value="">{{__('general_content.no_vat_trans_key') }}</option>
                                  @endforelse
                                </select>
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="vat_account">{{__('general_content.vat_account_trans_key') }}</label>
                              <input type="number" class="form-control" name="vat_account"  id="vat_account" placeholder="{{__('general_content.vat_account_trans_key') }}" value="{{ $Allocation->vat_account }}">
                            </div>
                            <div class="form-group">
                              <label for="code_account">{{__('general_content.code_account_trans_key') }}</label>
                              <input type="number" class="form-control" name="code_account"  id="code_account" placeholder="{{__('general_content.code_account_trans_key') }}" value="{{ $Allocation->code_account }}">
                            </div>
                            <div class="form-group">
                              <label for="type_imputation">{{__('general_content.type_trans_key') }}</label>
                              <select class="form-control" name="type_imputation" id="type_imputation">
                                  <option value="1" @if($Allocation->type_imputation == 1 ) Selected @endif>{{__('general_content.sale_trans_key') }}</option>
                                  <option value="2" @if($Allocation->type_imputation == 2 ) Selected @endif>{{__('general_content.purchase_trans_key') }}</option>
                                  <option value="3" @if($Allocation->type_imputation == 3 ) Selected @endif>{{__('general_content.advance_payment_trans_key') }}</option>
                                  <option value="4" @if($Allocation->type_imputation == 4 ) Selected @endif>{{__('general_content.tax_trans_key') }}</option>
                                  <option value="5" @if($Allocation->type_imputation == 5 ) Selected @endif>{{__('general_content.purchase_stock_trans_key') }}</option>
                                  <option value="6" @if($Allocation->type_imputation == 6 ) Selected @endif>{{__('general_content.other_trans_key') }}</option>
                              </select>
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </x-adminlte-modal>
                      </form>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{__('general_content.account_number_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.vat_trans_key') }}</th>
                    <th>{{__('general_content.vat_account_trans_key') }}</th>
                    <th>{{__('general_content.code_account_trans_key') }}</th>
                    <th>{{__('general_content.type_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>

        <div class="col-md-6">
          <form  method="POST" action="{{ route('accounting.allocation.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_accounting_allocations_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="account">Account (or {{ __('general_content.external_id_trans_key') }})</label>
                <input type="text" class="form-control" name="account" id="account" placeholder="Account">
              </div>
              <div class="form-group">
                <label for="label">{{__('general_content.label_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                </div>
              </div>
              <div class="form-group">
                <label for="accounting_vats_id">{{__('general_content.vat_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                  </div>
                  <select class="form-control" name="accounting_vats_id" id="accounting_vats_id">
                    @forelse ($VATSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @empty
                    <option value="">{{__('general_content.no_vat_trans_key') }}</option>
                    @endforelse
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="vat_account">{{__('general_content.vat_account_trans_key') }}</label>
                <input type="number" class="form-control" name="vat_account"  id="vat_account" placeholder="{{__('general_content.vat_account_trans_key') }}">
              </div>
              <div class="form-group">
                <label for="code_account">{{__('general_content.code_account_trans_key') }}</label>
                <input type="number" class="form-control" name="code_account"  id="code_account" placeholder="{{__('general_content.code_account_trans_key') }}">
              </div>
              <div class="form-group">
                <label for="type_imputation">{{__('general_content.type_trans_key') }}</label>
                <select class="form-control" name="type_imputation" id="type_imputation">
                    <option value="1">{{__('general_content.sale_trans_key') }}</option>
                    <option value="2">{{__('general_content.purchase_trans_key') }}</option>
                    <option value="3">{{__('general_content.advance_payment_trans_key') }}</option>
                    <option value="4">{{__('general_content.tax_trans_key') }}</option>
                    <option value="5">{{__('general_content.purchase_stock_trans_key') }}</option>
                    <option value="6">{{__('general_content.other_trans_key') }}</option>
                </select>
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
