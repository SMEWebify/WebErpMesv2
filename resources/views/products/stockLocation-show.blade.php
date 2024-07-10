@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key')) 

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>{{ $StockLocation->label }} {{__('general_content.stock_location_trans_key') }}</h1> 
      </div>
      <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('products.stock') }}">{{ __('general_content.stock_list_trans_key') }}</a></li>
              <li class="breadcrumb-item"><a href="{{ route('products.stock.show', ['id' => $StockLocation->stocks_id]) }}">{{__('general_content.stock_trans_key') }} {{ $Stock->label }}</a></li>
          </ol>
      </div>
    </div>
@stop

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-8">
      <x-adminlte-card title="{{ __('general_content.stock_location_product_list_trans_key') }}" theme="primary" maximizable>
        @include('include.table-stock-locations-products')
      </x-adminlte-card>
    <!-- /.col-md-8 card-secondary-->
    </div>
    <div class="col-md-4">
      <form  method="POST" action="{{ route('products.stockline.store') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('general_content.new_stock_location_product_trans_key') }}" theme="secondary" maximizable>
          @csrf
          <div class="form-group">
            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              </div>
              <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="STOCK-PRODUCT-{{ $LastStockLocationProduct->id ?? '0' }}">
              <input type="hidden" name="stock_locations_id" id="stock_locations_id" value="{{ $StockLocation->id }}">
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
            <x-adminlte-select2 name="products_id" id="products_id" label="{{ __('general_content.product_trans_key') }}" label-class="text-lightblue"
                  igroup-size="lg" data-placeholder="Select an product...">
                  <x-slot name="prependSlot">
                      <div class="input-group-text bg-gradient-info">
                          <i class="fas fa-barcode"></i>
                      </div>
                  </x-slot>
                  @foreach ($ProductSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                  @endforeach
                </x-adminlte-select2>
          </div>
          <div class="form-group">
            <label for="mini_qty">{{ __('general_content.qty_mini_trans_key') }} :</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-times"></i></span>
              </div>
              <input type="number" class="form-control" name="mini_qty" id="mini_qty" placeholder="{{ __('general_content.qty_mini_trans_key') }} ex: 1" step="1">
            </div>
          </div>
          <div class="form-group">
            <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
            <input type="date" class="form-control" name="end_date"  id="end_date" >
          </div>
          <div class="form-group">
            <label for="addressing">{{ __('general_content.addressing_trans_key') }}</label>
            <input type="text" class="form-control" name="addressing" id="addressing" placeholder="{{ __('general_content.addressing_trans_key') }}">
          </div>
          <div class="card-footer">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </div>
        </x-adminlte-card>
      </form>
    </div>
    <!-- /.card secondary -->
  </div>
  <!-- /.row -->
@stop

@section('css')
@stop

@section('js')
@stop