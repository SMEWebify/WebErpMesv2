@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key')) 

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1>{{ $Stock->label }} {{ __('general_content.stock_trans_key') }}</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('products.stock') }}">{{ __('general_content.stock_list_trans_key') }}</a></li>
        </ol>
    </div>
  </div>
@stop

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.stock_location_list_trans_key') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{__('general_content.id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{__('general_content.lines_count_trans_key') }}</th>
                <th>{{ __('general_content.end_date_trans_key') }}</th>
                <th>{{ __('general_content.user_management_trans_key') }}</th>
                <th>{{__('general_content.action_trans_key') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($StockLocations as $StockLocation)
              <tr>
                <td>{{ $StockLocation->code }}</td>
                <td>{{ $StockLocation->label }}</td>
                <td>{{ $StockLocation->stock_location_products_count }}</td>
                <td>{{ $StockLocation->end_date }}</td>
                <td>{{ $StockLocation->UserManagement['name'] }}</td>
                <td class=" py-0 align-middle">
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('products.stocklocation.show', ['id' => $StockLocation->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                  </div>
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#StockLocation{{ $StockLocation->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $StockLocation->id }} -->
                  <x-adminlte-modal id="StockLocation{{ $StockLocation->id }}" title="Update {{ $StockLocation->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('products.stocklocation.update', ['id' => $StockLocation->id]) }}" >
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $StockLocation->label }}">
                            <input type="hidden" name="stocks_id"  id="stocks_id"  value="{{ $StockLocation->stocks_id }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="service_id">{{ __('general_content.user_management_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                              <select class="form-control" name="user_id" id="user_id">
                                @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}" @if($StockLocation->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                @endforeach
                              </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.comment_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="comment"  placeholder="...">{{ $StockLocation->comment }}</textarea>
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
                <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{__('general_content.id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{__('general_content.lines_count_trans_key') }}</th>
                <th>{{ __('general_content.end_date_trans_key') }}</th>
                <th>{{ __('general_content.user_management_trans_key') }}</th>
                <th>{{__('general_content.action_trans_key') }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </x-adminlte-card>
    <!-- /.card secondary -->
    </div>
    <div class="col-md-6">
      <form  method="POST" action="{{ route('products.stocklocation.store') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('general_content.new_stock_location_trans_key') }}" theme="secondary" maximizable>
          @csrf
          <div class="form-group">
            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              </div>
              <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="STOCK-LOCATION-{{ $LastStockLocation->id ?? '0' }}">
              <input type="hidden" name="stocks_id" id="stocks_id" value="{{ $Stock->id }}">
            </div>
          </div>
          <div class="form-group">
            <label for="label">{{ __('general_content.description_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
              </div>
              <input type="text" class="form-control" name="label" id="label" placeholder="Description">
            </div>
          </div>
          <div class="form-group">
            <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
              </div>
              <select class="form-control" name="user_id" id="user_id">
                @foreach ($userSelect as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
            <input type="date" class="form-control" name="end_date"  id="end_date" >
          </div>
          <div class="form-group">
            <label>{{ __('general_content.comment_trans_key') }}</label>
            <textarea class="form-control" rows="3" name="comment"  placeholder="..."></textarea>
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>
    <!-- /.row -->
    </div>
  <!-- /.card body -->
  </div>
@stop

@section('css')
@stop

@section('js')
@stop