@extends('adminlte::page')

@section('title', __('general_content.expense_report_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.expense_report_trans_key') }} {{ $UserExpenseReports->label }}</h1>
@stop

@section('content')
@include('include.alert-result')
<div class="row ">
    @if($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
    <div class="col-md-8">
    @else
    <div class="col-md-12">
    @endif
    <x-adminlte-card title="{{ __('general_content.expense_report_trans_key') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>{{__('general_content.categories_trans_key') }}</th>
                    <th>{{__('general_content.date_trans_key') }}</th>
                    <th>{{__('general_content.location_expense_trans_key') }}</th>
                    <th>{{__('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.amount_trans_key') }}</th>
                    <th>{{__('general_content.payer_trans_key') }}</th>
                    <th>{{__('general_content.tax_trans_key') }}</th>
                    <th>{{__('general_content.order_trans_key') }}</th>
                    <th>{{__('general_content.scan_file_trans_key') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($UserExpenseReports->expenses as $Expense)
                <tr>
                    <td>{{ $Expense->category->label }}</td>
                    <td>{{ $Expense->expense_date }}</td>
                    <td>{{ $Expense->location }}</td>
                    <td>{{ $Expense->description }}</td>
                    <td>{{ $Expense->amount }} {{ $Factory->curency }}</td>
                    <td>{{ $Expense->payer->name }}</td>
                    <td>{{ $Expense->tax }}</td>
                    <td>
                        @if($Expense->order_id)
                        <x-OrderButton id="{{ $Expense->order_id }}" code="{{ $Expense->order->code }}"  />
                        @endif
                    </td>
                    <td>
                        @if($Expense->scan_file)
                        <div class="btn-group btn-group-sm">
                            <a href="{{ asset('/file/Expense/'. $Expense->scan_file) }}" download="{{ $Expense->scan_file }}" class="btn bg-primary"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                        </div>
                        @endif
                    </td>
                    <td class=" py-0 align-middle">
                        @if($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
                        <!-- Button Modal -->
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn bg-teal " data-toggle="modal" data-target="#Expense{{ $Expense->id }}">
                                <i class="fa fa-lg fa-fw  fa-edit"></i>
                            </button>
                        </div>
                        <!-- Modal {{ $Expense->id }} -->
                        <x-adminlte-modal id="Expense{{ $Expense->id }}" title="Update {{ $Expense->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                            <form method="POST" action="{{ route('human.resources.update.expense.line', ['id' => $Expense->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="category_id">{{ __('general_content.categories_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="category_id" id="category_id">
                                            
                                            <option value="" >{{ __('general_content.select_categorie_trans_key') }}</option>
                                            @foreach ($UserExpenseCategoriesSelect as $item)
                                            <option value="{{ $item->id }}" @if($Expense->category_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="expense_date">{{ __('general_content.date_trans_key') }}</label>
                                    <input type="date" class="form-control" name="expense_date"  id="expense_date" value="{{ $Expense->expense_date }}">
                                </div>
                                <div class="form-group">
                                    <label for="location">{{__('general_content.location_expense_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="location"  id="location" placeholder="{{__('general_content.location_expense_trans_key') }}" value="{{ $Expense->location }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">{{__('general_content.description_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="description"  id="description" placeholder="{{__('general_content.description_trans_key') }}" value="{{ $Expense->description }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="amount">{{__('general_content.amount_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control" name="amount"  id="amount" placeholder="{{__('general_content.amount_trans_key') }}" step=".001" value="{{ $Expense->amount }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="payer_id">{{ __('general_content.payer_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control" name="payer_id" id="payer_id">
                                            @foreach ($userSelect as $item)
                                            <option value="{{ $item->id }}" @if($Expense->payer_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="amount">{{__('general_content.tax_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <input type="number" class="form-control" name="tax"  id="tax" placeholder="{{__('general_content.tax_trans_key') }}" step=".001" value="{{ $Expense->tax }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <x-adminlte-select2 name="order_id" id="order_id" label="{{ __('general_content.select_order_trans_key') }}" label-class="text-secondary"
                                        igroup-size="lg" data-placeholder="{{ __('general_content.select_order_line_trans_key') }}">
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text bg-gradient-secondary">
                                                <i class="fas fa-list"></i>
                                            </div>
                                        </x-slot>
                                        <option value="null">{{ __('general_content.select_order_trans_key') }}</option>
                                        @foreach ($OrderLineList as $item)
                                        <option value="{{ $item->id }}" @if($Expense->order_id == $item->id  ) Selected @endif>#{{ $item->id }} - {{ $item->code }}</option>
                                        @endforeach
                                    </x-adminlte-select2>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                </div>
                            </form>
                        </x-adminlte-modal>
                        @endif
                    </td>
                </tr>
                @empty
                    <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.categories_trans_key') }}</th>
                        <th>{{__('general_content.date_trans_key') }}</th>
                        <th>{{__('general_content.location_expense_trans_key') }}</th>
                        <th>{{__('general_content.description_trans_key') }}</th>
                        <th>{{__('general_content.amount_trans_key') }}</th>
                        <th>{{__('general_content.payer_trans_key') }}</th>
                        <th>{{__('general_content.tax_trans_key') }}</th>
                        <th>{{__('general_content.order_trans_key') }}</th>
                        <th>{{__('general_content.scan_file_trans_key') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-adminlte-card>
    <!-- /.card secondary -->
    </div>
    @if($UserExpenseReports->status  == 1 || $UserExpenseReports->status  == 2 || $UserExpenseReports->status  == 4)
    <div class="col-md-4">
        <x-adminlte-card title="{{ __('general_content.new_expense_report_trans_key') }}" theme="secondary" maximizable>
            <form  method="POST" action="{{ route('human.resources.create.expense.line', ['report_id' => $UserExpenseReports->id])  }}" class="form-horizontal" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="category_id">{{ __('general_content.categories_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <select class="form-control" name="category_id" id="category_id">
                            
                            <option value="" >{{ __('general_content.select_categorie_trans_key') }}</option>
                            @foreach ($UserExpenseCategoriesSelect as $item)
                            <option value="{{ $item->id }}" >{{ $item->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="expense_date">{{ __('general_content.date_trans_key') }}</label>
                    <input type="date" class="form-control" name="expense_date"  id="expense_date" >
                </div>
                <div class="form-group">
                    <label for="location">{{__('general_content.location_expense_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="location"  id="location" placeholder="{{__('general_content.location_expense_trans_key') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">{{__('general_content.description_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="description"  id="description" placeholder="{{__('general_content.description_trans_key') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="amount">{{__('general_content.amount_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">{{ $Factory->curency }}</span>
                        </div>
                        <input type="number" class="form-control" name="amount"  id="amount" placeholder="{{__('general_content.amount_trans_key') }}" step=".001">
                    </div>
                </div>
                <div class="form-group">
                    <label for="payer_id">{{ __('general_content.payer_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <select class="form-control" name="payer_id" id="payer_id">
                            @foreach ($userSelect as $item)
                            <option value="{{ $item->id }}" >{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tax">{{__('general_content.tax_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">%</span>
                        </div>
                        <input type="number" class="form-control" name="tax"  id="tax" placeholder="{{__('general_content.tax_trans_key') }}" step=".001">
                    </div>
                </div>
                
                <div class="form-group">
                    <x-adminlte-select2 name="order_id" id="order_id" label="{{ __('general_content.select_order_trans_key') }}" label-class="text-secondary"
                        igroup-size="lg" data-placeholder="{{ __('general_content.select_order_line_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-secondary">
                                <i class="fas fa-list"></i>
                            </div>
                        </x-slot>
                        <option value="">{{ __('general_content.select_order_trans_key') }}</option>
                        @foreach ($OrderLineList as $item)
                        <option value="{{ $item->id }}">#{{ $item->id }} - {{ $item->code }}</option>
                        @endforeach
                    </x-adminlte-select2>
                </div>
                <div class="form-group">
                    <label for="scan_file">{{ __('general_content.picture_trans_key') }}</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-image"></i></span>
                        </div>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="scan_file" id="scan_file">
                            <label class="custom-file-label" for="scan_file">{{ __('general_content.choose_file_trans_key') }}</label>
                        </div>
                    </div>
                </div>
                    
                <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
            </form>
        </x-adminlte-card>
    </div>
    @endif
    <!-- /.card secondary -->
  </div>
  <!-- /.row -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop