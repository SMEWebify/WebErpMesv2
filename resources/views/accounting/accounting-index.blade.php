@extends('adminlte::page')

@section('title', __('general_content.accounting_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.accounting_trans_key') }}</h1>
@stop

@section('right-sidebar') 

@section('content')

<div class="card">
  @include('include.alert-result')
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#PaymentCondition" data-toggle="tab">{{ __('general_content.payment_conditions_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#PaymentChoice" data-toggle="tab">{{ __('general_content.payment_methods_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#VAT" data-toggle="tab">{{ __('general_content.vat_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#AccountingAllocations" data-toggle="tab"> Accounting allocations</a></li>
      <li class="nav-item"><a class="nav-link" href="#Delevery" data-toggle="tab">{{ __('general_content.delevery_method_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="PaymentCondition">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.payment_conditions_trans_key') }}" theme="primary" maximizable>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.number_of_month_trans_key') }}</th>
                    <th>{{__('general_content.number_of_day_trans_key') }}</th>
                    <th>{{__('general_content.end_of_month_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($PaymentConditions as $PaymentCondition)
                  <tr>
                    <td>{{ $PaymentCondition->code }}</td>
                    <td>{{ $PaymentCondition->label }}</td>
                    <td>{{ $PaymentCondition->number_of_month }}</td>
                    <td>{{ $PaymentCondition->number_of_day }}</td>
                    <td>
                      @if($PaymentCondition->month_end  == 1) Yes @endif
                      @if($PaymentCondition->month_end  == 2) No @endif
                    </td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioPaymentCondition{{ $PaymentCondition->id }}" name="customRadioPaymentCondition"  @if( $PaymentCondition->default == 1 ) checked @endif disabled>
                        <label for="customRadioPaymentCondition{{ $PaymentCondition->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $PaymentCondition->id }} -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#PaymentCondition{{ $PaymentCondition->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $PaymentCondition->id }} -->
                      <form method="POST" action="{{ route('accounting.paymentCondition.update', ['id' => $PaymentCondition->id]) }}" enctype="multipart/form-data">
                        <x-adminlte-modal id="PaymentCondition{{ $PaymentCondition->id }}" title="Update {{ $PaymentCondition->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $PaymentCondition->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="number_of_month">{{__('general_content.number_of_month_trans_key') }}</label>
                              <input type="number" class="form-control" name="number_of_month"  id="number_of_month" placeholder="{{__('general_content.number_of_month_trans_key') }}" value="{{ $PaymentCondition->number_of_month }}">
                            </div>
                            <div class="form-group">
                              <label for="number_of_day">{{__('general_content.number_of_day_trans_key') }}</label>
                              <input type="number" class="form-control" name="number_of_day"  id="number_of_day" placeholder="{{__('general_content.number_of_day_trans_key') }}" value="{{ $PaymentCondition->number_of_day }}">
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.end_of_month_trans_key') }}</label>
                              <select class="form-control" name="month_end" id="month_end">
                                  <option value="2" @if($PaymentCondition->month_end == 2 ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($PaymentCondition->month_end == 1 ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($PaymentCondition->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($PaymentCondition->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                          </div>
                          <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </x-slot>
                        </x-adminlte-modal>
                      </form>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.number_of_month_trans_key') }}</th>
                    <th>{{__('general_content.number_of_day_trans_key') }}</th>
                    <th>{{__('general_content.end_of_month_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>

        <div class="col-md-6">
          <form  method="POST" action="{{ route('accounting.paymentCondition.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_payment_conditions_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                </div>
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
                <label for="number_of_month">{{__('general_content.number_of_month_trans_key') }}</label>
                <input type="number" class="form-control" name="number_of_month"  id="number_of_month" placeholder="{{__('general_content.number_of_month_trans_key') }}">
              </div>
              <div class="form-group">
                <label for="number_of_day">{{__('general_content.number_of_day_trans_key') }}</label>
                <input type="number" class="form-control" name="number_of_day"  id="number_of_day" placeholder="{{__('general_content.number_of_day_trans_key') }}">
              </div>
              <div class="form-group">
                <label for="month_end">{{__('general_content.end_of_month_trans_key') }}</label>
                <select class="form-control" name="month_end" id="month_end">
                    <option value="2">{{ __('general_content.no_trans_key') }}</option>
                    <option value="1">{{ __('general_content.yes_trans_key') }}</option>
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
    <!-- /.tab-pane -->

    <div class="tab-pane" id="PaymentChoice">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.payment_methods_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.code_account_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($PaymentMethods as $PaymentMethod)
                  <tr>
                    <td>{{ $PaymentMethod->code }}</td>
                    <td>{{ $PaymentMethod->label }}</td>
                    <td>{{ $PaymentMethod->code_account }}</td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioPaymentMethod{{ $PaymentMethod->id }}" name="customRadioPaymentMethod"  @if( $PaymentMethod->default == 1 ) checked @endif disabled>
                        <label for="customRadioPaymentMethod{{ $PaymentMethod->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $PaymentMethod->id }} -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#PaymentMethod{{ $PaymentMethod->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $PaymentMethod->id }} -->
                      <form method="POST" action="{{ route('accounting.paymentMethod.update', ['id' => $PaymentMethod->id]) }}" enctype="multipart/form-data">
                        <x-adminlte-modal id="PaymentMethod{{ $PaymentMethod->id }}" title="Update {{ $PaymentMethod->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $PaymentMethod->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="code_account">{{__('general_content.code_account_trans_key') }}</label>
                              <input type="text" class="form-control" name="code_account"  id="code_account" placeholder="{{__('general_content.code_account_trans_key') }}" value="{{ $PaymentMethod->code_account }}">
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($PaymentMethod->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($PaymentMethod->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
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
                    <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.code_account_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>

        <div class="col-md-6">
          <form  method="POST" action="{{ route('accounting.paymentMethod.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_payment_methods_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                </div>
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
                <label for="code_account">{{__('general_content.code_account_trans_key') }}</label>
                <input type="text" class="form-control" name="code_account"  id="code_account" placeholder="{{__('general_content.code_account_trans_key') }}">
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
    <!-- /.tab-pane -->

    <div class="tab-pane" id="VAT">
      <x-InfocalloutComponent note="You can define as many tax rates as you want, depending on the types of the quoted or sold products / components."  />
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.vat_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.rate_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($VATs as $VAT)
                  <tr>
                    <td>{{ $VAT->code }}</td>
                    <td>{{ $VAT->label }}</td>
                    <td>{{ $VAT->rate }}</td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioVAT{{ $VAT->id }}" name="customRadioVAT"  @if( $VAT->default == 1 ) checked @endif disabled>
                        <label for="customRadioVAT{{ $VAT->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $VAT->id }} -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#VAT{{ $VAT->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $VAT->id }} -->
                        <form method="POST" action="{{ route('accounting.vat.update', ['id' => $VAT->id]) }}" enctype="multipart/form-data">
                          <x-adminlte-modal id="VAT{{ $VAT->id }}" title="Update {{ $VAT->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $VAT->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="rate">rate</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <input type="number" class="form-control" name="rate"  id="rate" placeholder="10 %" step=".01" value="{{ $VAT->rate }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($VAT->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($VAT->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
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
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.rate_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>

        <div class="col-md-6">
          <form  method="POST" action="{{ route('accounting.vat.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_vat_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                </div>
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
                <label for="rate">rate</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                  </div>
                  <input type="number" class="form-control" name="rate"  id="rate" placeholder="10 %" step=".01">
                </div>
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
          <!-- /.card body -->
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->

    <div class="tab-pane" id="AccountingAllocations">
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
                      @if($Allocation->type_imputation  == 1) {{__('general_content.purchase_trans_key') }} @endif 
                      @if($Allocation->type_imputation  == 2) {{__('general_content.purchase_stock_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 3) {{__('general_content.advance_payment_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 4) {{__('general_content.advance_payment_vat_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 5) {{__('general_content.other_trans_key') }} @endif
                      @if($Allocation->type_imputation  == 6) VAT @endif
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $Allocation->id }} -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Allocation{{ $Allocation->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
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
                                  <option value="1" @if($Allocation->type_imputation == 1 ) Selected @endif>{{__('general_content.purchase_trans_key') }}</option>
                                  <option value="2" @if($Allocation->type_imputation == 2 ) Selected @endif>{{__('general_content.purchase_stock_trans_key') }}</option>
                                  <option value="3" @if($Allocation->type_imputation == 3 ) Selected @endif>{{__('general_content.advance_payment_trans_key') }}</option>
                                  <option value="4" @if($Allocation->type_imputation == 4 ) Selected @endif>{{__('general_content.advance_payment_vat_trans_key') }}</option>
                                  <option value="5" @if($Allocation->type_imputation == 5 ) Selected @endif>{{__('general_content.other_trans_key') }}</option>
                                  <option value="6" @if($Allocation->type_imputation == 6 ) Selected @endif>{{__('general_content.vat_trans_key') }}</option>
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
                    <option value="1">{{__('general_content.purchase_trans_key') }}</option>
                    <option value="2">{{__('general_content.purchase_stock_trans_key') }}</option>
                    <option value="3">{{__('general_content.advance_payment_trans_key') }}</option>
                    <option value="4">{{__('general_content.advance_payment_vat_trans_key') }}</option>
                    <option value="5">{{__('general_content.other_trans_key') }}</option>
                    <option value="6">{{__('general_content.vat_trans_key') }}</option>
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
    <!-- /.tab-pane -->

    <div class="tab-pane" id="Delevery">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.delivery_notes_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($Deleverys as $Delevery)
                  <tr>
                    <td>{{ $Delevery->code }}</td>
                    <td>{{ $Delevery->label }}</td>
                    <td>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="customRadioDelevery{{ $Delevery->id }}" name="customRadioDelevery"  @if( $Delevery->default == 1 ) checked @endif disabled>
                        <label for="customRadioDelevery{{ $Delevery->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                      </div>
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal {{ $Delevery->id }} -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Delevery{{ $Delevery->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $Delevery->id }} -->
                      <x-adminlte-modal id="Delevery{{ $Delevery->id }}" title="Update {{ $Delevery->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('accounting.delivery.update', ['id' => $Delevery->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $Delevery->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
                              <select class="form-control" name="default" id="default">
                                  <option value="0" @if($Delevery->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($Delevery->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                      </x-adminlte-modal>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>

        <div class="col-md-6">
          <form  method="POST" action="{{ route('accounting.delivery.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_delevery_method_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                </div>
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
    <!-- /.tab-pane -->
  </div>
  <!-- /.card -->
</div>
@stop

@section('css')

@stop

@section('js')
@stop