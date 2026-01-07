@extends('adminlte::page')

@section('title', __('general_content.products_trans_key'))

@section('content_header')
  <x-Content-header-previous-button  h1="{{ $Product->label }}" previous="{{ $previousUrl }}" list="{{ route('products') }}" next="{{ $nextUrl }}"/>
@stop

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>


<div class="row">
  <div class="col-md-8">
    <x-adminlte-card title="{{ __('general_content.product_info_trans_key') }}" theme="primary" maximizable>
        <ul class="nav nav-tabs" id="productTab" role="tablist">
          <li class="nav-item"><a class="nav-link active" href="#Product" data-toggle="tab"><i class="fas fa-info-circle"></i> {{ __('general_content.product_info_trans_key') }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#TechnicalInfo" data-toggle="tab"><i class="fas fa-cogs"></i> {{ __('general_content.tech_bom_trans_key') }} {{ $Product->getAllTaskCountAttribute() }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#Stock" data-toggle="tab"><i class="fas fa-boxes"></i> {{ __('general_content.stock_trans_key') }} ({{ $Product->StockLocationProductCount() }})</a></li>
          @if($CustomFields->count() > 0)
          <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab"><i class="fas fa-list"></i> {{ __('general_content.custom_fields_trans_key') }}</a></li>
          @endif
          @if($Product->purchased == 1 )
          <li class="nav-item"><a class="nav-link" href="#PreferredSupplier" data-toggle="tab"> <i class="fas fa-truck"></i>{{ __('general_content.preferred_supplier_trans_key') }}</a></li>
          @endif
          <li class="nav-item"><a class="nav-link" href="#quote" data-toggle="tab"><i class="fas fa-file-invoice"></i> {{ __('general_content.quotes_list_trans_key') }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#order" data-toggle="tab"><i class="fas fa-shopping-cart"></i> {{ __('general_content.orders_list_trans_key') }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#purchase" data-toggle="tab"><i class="fas fa-cart-arrow-down"></i> {{ __('general_content.purchase_list_trans_key') }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#serialNumber" data-toggle="tab"><i class="fas fa-barcode"></i> {{ __('general_content.serial_numbers_trans_key') }}</a></li>
          
          @if($Product->drawing_file)
          <li class="nav-item"><a class="nav-link" href="#DrawingViewer" data-toggle="tab"> {{ __('general_content.drawing_trans_key') }}</a></li>
          @endif
          @if($Product->stl_file)
          <li class="nav-item"><a class="nav-link" href="#StepViewer" data-toggle="tab"><i class="fas fa-cube"></i> Stl {{ __('general_content.viewer_file_trans_key') }}</a></li>
          @endif
          @if($Product->svg_file)
          <li class="nav-item"><a class="nav-link" href="#SVGViewer" data-toggle="tab"><i class="fas fa-vector-square"></i>  SVG {{ __('general_content.viewer_file_trans_key') }}</a></li>
          @endif
          <li class="nav-item"><a class="nav-link" href="#Logs" data-toggle="tab"><i class="fas fa-history"></i> Logs</a></li>
        </ul>
      <!-- /.card-header -->
      <div class="tab-content">
        <div class="tab-pane active" id="Product">
          @include('include.alert-result')
          <form method="POST" action="{{ route('products.update', ['id' => $Product->id]) }}" enctype="multipart/form-data">
              @csrf
              <div class="card card-body">
                <div class="row">
                    <div class="form-group col-md-4">
                      <div class="text-muted">
                        <label for="label">{{ __('general_content.external_id_trans_key') }}</label>
                          <b class="d-block">{{ $Product->code }}</b>
                        </p>
                      </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="label">{{ __('general_content.description_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" value="{{ $Product->label }}" name="label"  id="label" placeholder="{{ __('general_content.description_trans_key') }}">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="ind">{{ __('general_content.index_trans_key') }}</label>
                        <input type="text" class="form-control" value="{{ $Product->ind }}"   name="ind"  id="ind" placeholder="{{ __('general_content.index_trans_key') }}">
                    </div>
                </div>
              </div>
              <div class="card card-body">
                <div class="row">
                    <div class="form-group col-md-4">
                        @include('include.form.form-select-service',['serviceId' =>  $Product->methods_services_id  ])
                    </div>
                    <div class="form-group col-md-4">
                        <label for="methods_families_id">{{ __('general_content.select_family_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-grip-horizontal"></i></span>
                            </div>
                            <select class="form-control" name="methods_families_id" id="methods_families_id">
                                <option value="">{{ __('general_content.family_trans_key') }}</option>
                                @forelse ($FamiliesSelect as $item)
                                <option value="{{ $item->id }}" @if($Product->methods_families_id == $item->id ) Selected @endif >{{ $item->label }}</option>
                                @empty
                                    <option value="">{{ __('general_content.no_family_trans_key') }}</option>
                                @endforelse
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="methods_units_id">{{ __('general_content.unit_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            </div>
                            <select class="form-control" name="methods_units_id" id="methods_units_id">
                                <option value="">{{ __('general_content.select_unit_trans_key') }}</option>
                                @forelse ($UnitsSelect as $item)
                                <option value="{{ $item->id }}" @if($Product->methods_units_id == $item->id ) Selected @endif>{{ $item->label }}</option>
                                @empty
                                    <option value="">{{ __('general_content.no_unit_trans_key') }}</option>
                                @endforelse
                            </select>
                        </div>
                    </div>
                </div>
              </div>
              <div class="card card-body">
                <div class="row">
                    <div class="form-group col-md-4">
                      <div class="col-4 text-left"><label for="purchased" class="col-form-label">{{ __('general_content.purchased_trans_key') }}</label></div>
                      <div class="col-8">
                          @if($Product->purchased == 1)  
                          <x-adminlte-input-switch id="purchased" name="purchased" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true" />
                          @else
                          <x-adminlte-input-switch id="purchased" name="purchased" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                          @endif
                      </div>
                    </div>
                    <div class="form-group col-md-4">
                      <div class="col-4 text-left"><label for="sold" class="col-form-label">{{ __('general_content.sold_trans_key') }}</label></div>
                      <div class="col-8">
                          @if($Product->sold == 1)  
                          <x-adminlte-input-switch id="sold" name="sold" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true" />
                          @else
                          <x-adminlte-input-switch id="sold" name="sold" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                          @endif
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="tracability_type">{{ __('general_content.tracability_trans_key') }}</label>
                        <select class="form-control" name="tracability_type" id="tracability_type">
                            <option value="">{{ __('general_content.select_type_trans_key') }}</option>
                            <option value="1" @if($Product->tracability_type == 1 ) Selected @endif>{{ __('general_content.no_traceability_trans_key') }}</option>
                            <option value="2" @if($Product->tracability_type == 2 ) Selected @endif>{{ __('general_content.with_batch_number_trans_key') }}</option>
                            <option value="3" @if($Product->tracability_type == 3 ) Selected @endif>{{ __('general_content.with_serial_number_trans_key') }}</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->purchased_price }}"  name="purchased_price" id="purchased_price" placeholder="{{ __('general_content.purchased_price_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control"  value="{{ $Product->selling_price }}" name="selling_price" id="selling_price" placeholder="{{ __('general_content.price_trans_key') }}" step=".001">
                        </div>
                    </div>
                </div>
              </div>
              <div class="card card-body">
                <div class="row">
                    <label for="material">{{ __('general_content.proprieties_trans_key') }}</label>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                            </div>
                            <input type="text" class="form-control" value="{{ $Product->material }}" name="material" id="material"  placeholder="{{ __('general_content.material_trans_key') }}">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->thickness }}" name="thickness" id="thickness"  placeholder="{{ __('general_content.thickness_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->weight }}" name="weight" id="weight"  placeholder="{{ __('general_content.weight_trans_key') }}" step=".001">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->bend_count }}" name="bend_count" id="bend_count" placeholder="{{ __('general_content.bend_count_trans_key') }}" step="1" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                      <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                          </div>
                          <input type="text" class="form-control" value="{{ $Product->finishing }}" name="finishing" id="finishing"  placeholder="{{ __('general_content.finishing_trans_key') }}">
                      </div>
                  </div>
                  <div class="form-group col-md-4">
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-folder-open"></i></span>
                      </div>
                      <input type="text" class="form-control" value="{{ $Product->cad_file_path }}" name="cad_file_path" id="cad_file_path" placeholder="{{ __('CAD file path') }}">
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-folder-open"></i></span>
                      </div>
                      <input type="text" class="form-control" value="{{ $Product->cam_file_path }}" name="cam_file_path" id="cam_file_path" placeholder="{{ __('CAM file path') }}">
                    </div>
                  </div>
                </div>
                <hr>
                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="x_size">X</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->x_size }}" name="x_size" id="x_size"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="y_size">Y</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->y_size }}"  name="y_size" id="y_size"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="z_size">Z</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->z_size }}" name="z_size" id="z_size"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="form-group col-md-4">
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control"  value="{{ $Product->x_oversize }}" name="x_oversize" id="x_oversize"  placeholder="{{ __('general_content.x_oversize_trans_key') }}" step=".001">
                    </div></div>
                    <div class="form-group col-md-4">
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control" value="{{ $Product->y_oversize }}" name="y_oversize" id="y_oversize"  placeholder="{{ __('general_content.y_oversize_trans_key') }}" step=".001">
                    </div></div>
                    <div class="form-group col-md-4">
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control" value="{{ $Product->z_oversize }}" name="z_oversize" id="z_oversize"  placeholder="{{ __('general_content.z_oversize_trans_key') }}" step=".001">
                    </div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->diameter }}" name="diameter" id="diameter"  placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                    </div>
                    <div class="form-group col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize"  placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                    </div>
                    <div class="form-group col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->section_size }}" name="section_size" id="section_size" placeholder="Section size" step=".001">
                    </div>
                </div>
              </div>
              <div class="card card-body">
                <div class="row">
                    <label for="qty_eco_min">{{ __('general_content.other_information_trans_key') }}</label>
                </div>
                <hr>
                <div class="row">
                    <div class="form-group col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->qty_eco_min }}" name="qty_eco_min" id="qty_eco_min" placeholder="{{ __('general_content.quantite_eco_min_trans_key') }}" step=".001">
                    </div>
                    <div class="form-group col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->qty_eco_max }}" name="qty_eco_max" id="qty_eco_max" placeholder="{{ __('general_content.quantite_eco_max_trans_key') }}" step=".001">
                    </div>
                    <div class="form-group col-md-4"></div>
                </div>
                <div class="row">
                  <div class="col-12">
                    @php
                    $config = [
                        "height" => "200",
                        "toolbar" => [
                            // [groupName, [list of button]]
                            ['style', ['bold', 'italic', 'underline', 'clear']],
                            ['font', ['strikethrough', 'superscript', 'subscript']],
                            ['fontsize', ['fontsize']],
                            ['color', ['color']],
                            ['para', ['ul', 'ol', 'paragraph']],
                            ['height', ['height']],
                            ['table', ['table']],
                            ['insert', ['link', 'picture', 'video']],
                            ['view', ['fullscreen', 'codeview', 'help']],
                        ],
                    ]
                    @endphp
                    <x-adminlte-text-editor name="comment" label="{{ __('general_content.comment_trans_key') }}" label-class="text-primary"
                        igroup-size="sm" placeholder="..." :config="$config"> 
                        {{  $Product->comment }}
                    </x-adminlte-text-editor>
                  </div>
                </div>
              </div>
            <div class="card-footer">
              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
            </div>
          </form>
          @php
            $customerTypeOptions = [
                ['value' => '', 'label' => __('general_content.all_customer_types_trans_key')],
                ['value' => '1', 'label' => __('general_content.legal_entity_trans_key')],
                ['value' => '2', 'label' => __('general_content.individual_trans_key')],
            ];
            $customerTypeLabels = collect($customerTypeOptions)->pluck('label', 'value');
          @endphp
          <x-adminlte-card title="{{ __('general_content.customer_price_grid_trans_key') }}" theme="info" maximizable>
            <form method="POST" action="{{ route('products.customer-price-list.store', ['product' => $Product->id]) }}">
              @csrf
              <div class="row">
                <div class="form-group col-md-4">
                  <label for="price_list_companies_id">{{ __('general_content.customer_trans_key') }}</label>
                  <select class="form-control" name="companies_id" id="price_list_companies_id">
                    <option value="">{{ __('general_content.all_customers_trans_key') }}</option>
                    @foreach($CustomerSelect as $customer)
                    <option value="{{ $customer->id }}" @if((string) old('companies_id', '') === (string) $customer->id) selected @endif>{{ $customer->code }} - {{ $customer->label }}</option>
                    @endforeach
                  </select>
                  @error('companies_id', 'customerPriceList')
                  <small class="text-danger d-block">{{ $message }}</small>
                  @enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="price_list_customer_type">{{ __('general_content.customer_type_trans_key') }}</label>
                  <select class="form-control" name="customer_type" id="price_list_customer_type">
                    @foreach($customerTypeOptions as $option)
                    <option value="{{ $option['value'] }}" @if((string) old('customer_type', '') === (string) $option['value']) selected @endif>{{ $option['label'] }}</option>
                    @endforeach
                  </select>
                  @error('customer_type', 'customerPriceList')
                  <small class="text-danger d-block">{{ $message }}</small>
                  @enderror
                </div>
                <div class="form-group col-md-2">
                  <label for="price_list_min_qty">{{ __('general_content.quantite_min_trans_key') }}</label>
                  <input type="number" class="form-control" name="min_qty" id="price_list_min_qty" value="{{ old('min_qty') }}" min="1">
                  @error('min_qty', 'customerPriceList')
                  <small class="text-danger d-block">{{ $message }}</small>
                  @enderror
                </div>
                <div class="form-group col-md-2">
                  <label for="price_list_max_qty">{{ __('general_content.quantite_max_trans_key') }}</label>
                  <input type="number" class="form-control" name="max_qty" id="price_list_max_qty" value="{{ old('max_qty') }}" min="1">
                  @error('max_qty', 'customerPriceList')
                  <small class="text-danger d-block">{{ $message }}</small>
                  @enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="price_list_price">{{ __('general_content.price_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">{{ $Factory->curency }}</span>
                    </div>
                    <input type="number" step="0.01" class="form-control" name="price" id="price_list_price" value="{{ old('price') }}" min="0">
                  </div>
                  @error('price', 'customerPriceList')
                  <small class="text-danger d-block">{{ $message }}</small>
                  @enderror
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 d-flex justify-content-between align-items-center">
                  <small class="text-muted">{{ __('general_content.customer_price_list_create_help_trans_key') }}</small>
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.add_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
                </div>
              </div>
            </form>
            @php $customerPriceListErrors = $errors->getBag('customerPriceList'); @endphp
            @if($customerPriceListErrors->has('customer_price_list'))
            <div class="alert alert-warning mt-3 mb-0">
              {{ $customerPriceListErrors->first('customer_price_list') }}
            </div>
            @endif
            <div class="table-responsive mt-3">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.source_trans_key') }}</th>
                    <th>{{ __('general_content.quantite_min_trans_key') }}</th>
                    <th>{{ __('general_content.quantite_max_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
                    <th class="text-right">{{ __('general_content.action_trans_key') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($CustomerPriceLists as $priceList)
                  @php
                    $scopeLabel = __('general_content.customer_trans_key');
                    if($priceList->companies_id){
                      $scopeLabel = __('general_content.companie_trans_key') . ' - ' . ($priceList->company->label ?? ('#' . $priceList->companies_id));
                    }elseif(!is_null($priceList->customer_type)){
                      $typeLabel = $customerTypeLabels[(string) $priceList->customer_type] ?? __('general_content.customer_type_trans_key');
                      $scopeLabel = __('general_content.customer_type_trans_key') . ' - ' . $typeLabel;
                    }
                  @endphp
                  <tr>
                    <td>{{ $scopeLabel }}</td>
                    <td>{{ $priceList->min_qty }}</td>
                    <td>
                      @if(is_null($priceList->max_qty))
                        &infin;
                      @else
                        {{ $priceList->max_qty }}
                      @endif
                    </td>
                    <td>{{ number_format($priceList->price, 2) }} {{ $Factory->curency }}</td>
                    <td class="text-right">
                      <button type="button" class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#customerPriceListEdit{{ $priceList->id }}">
                        <i class="fas fa-edit"></i>
                      </button>
                      <form method="POST" action="{{ route('products.customer-price-list.destroy', ['product' => $Product->id, 'priceList' => $priceList->id]) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-outline-danger" onclick="return confirm('{{ __('general_content.delete_trans_key') }} ?');">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                      <x-adminlte-modal id="customerPriceListEdit{{ $priceList->id }}" title="{{ __('general_content.update_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('products.customer-price-list.update', ['product' => $Product->id, 'priceList' => $priceList->id]) }}">
                          @csrf
                          @method('PUT')
                          <div class="row">
                            <div class="form-group col-md-6">
                              <label for="price_list_companies_id_{{ $priceList->id }}">{{ __('general_content.customer_trans_key') }}</label>
                              <select class="form-control" name="companies_id" id="price_list_companies_id_{{ $priceList->id }}">
                                <option value="">{{ __('general_content.all_customers_trans_key') }}</option>
                                @foreach($CustomerSelect as $customer)
                                <option value="{{ $customer->id }}" @if($priceList->companies_id === $customer->id) selected @endif>{{ $customer->code }} - {{ $customer->label }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="form-group col-md-6">
                              <label for="price_list_customer_type_{{ $priceList->id }}">{{ __('general_content.customer_type_trans_key') }}</label>
                              <select class="form-control" name="customer_type" id="price_list_customer_type_{{ $priceList->id }}">
                                @foreach($customerTypeOptions as $option)
                                <option value="{{ $option['value'] }}" @if((string) $priceList->customer_type === (string) $option['value']) selected @endif>{{ $option['label'] }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <div class="row">
                            <div class="form-group col-md-4">
                              <label for="price_list_min_qty_{{ $priceList->id }}">{{ __('general_content.quantite_min_trans_key') }}</label>
                              <input type="number" class="form-control" name="min_qty" id="price_list_min_qty_{{ $priceList->id }}" value="{{ $priceList->min_qty }}" min="1">
                            </div>
                            <div class="form-group col-md-4">
                              <label for="price_list_max_qty_{{ $priceList->id }}">{{ __('general_content.quantite_max_trans_key') }}</label>
                              <input type="number" class="form-control" name="max_qty" id="price_list_max_qty_{{ $priceList->id }}" value="{{ $priceList->max_qty }}" min="1">
                            </div>
                            <div class="form-group col-md-4">
                              <label for="price_list_price_{{ $priceList->id }}">{{ __('general_content.price_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text">{{ $Factory->curency }}</span>
                                </div>
                                <input type="number" step="0.01" class="form-control" name="price" id="price_list_price_{{ $priceList->id }}" value="{{ $priceList->price }}" min="0">
                              </div>
                            </div>
                          </div>
                          <div class="text-right">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                      </x-adminlte-modal>
                    </td>
                  </tr>
                  @empty
                  <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}" />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.source_trans_key') }}</th>
                    <th>{{ __('general_content.quantite_min_trans_key') }}</th>
                    <th>{{ __('general_content.quantite_max_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        </div>
        <div class="tab-pane " id="TechnicalInfo">
          @livewire('task-manage', ['idType' => 'products_id', 'idPage' => $Product->id, 'idLine' => $Product->id, 'statu' => 1])
        </div>
        <div class="tab-pane " id="Stock">
          <x-adminlte-card title="{{ __('general_content.stock_location_product_list_trans_key') }}" theme="primary" maximizable>
            @include('include.table-stock-locations-products')
          </x-adminlte-card>
        </div>
        @if($CustomFields->count() > 0)
        <div class="tab-pane" id="CustomFields">
          @include('include.custom-fields-form', ['id' => $Product->id, 'type' => 'product'])
        </div>
        @endif
        @if($Product->purchased == 1 )
        <div class="tab-pane" id="PreferredSupplier">
          <div class="row">
            <div class="col-md-6">
              <x-adminlte-card title="{{ __('general_content.preferred_supplier_trans_key') }}" theme="primary" maximizable>
                <div class="table-responsive p-0">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{ __('general_content.supplier_rate_trans_key') }}</th>
                        <th></th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($Product->preferredSuppliers as $preferredSuppliers)
                      <tr>
                        <td>{{ $preferredSuppliers->code }}</td>
                        <td>{{ $preferredSuppliers->label }}</td>
                        <td>
                          @for ($i = 1; $i <= 5; $i++)
                              @if ($i <= $preferredSuppliers->averageRating())
                                  <span class="badge badge-warning">&#9733;</span>
                              @else
                                  <span class="badge badge-info">&#9734;</span>
                              @endif
                          @endfor
                        </td>
                        <td><x-ButtonTextView route="{{ route('companies.show', ['id' => $preferredSuppliers->id])}}" /></td>
                        <td class="py-0 align-middle">
                            <!-- Button Modal -->
                            <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#preferredSuppliers{{ $preferredSuppliers->id }}">
                              x €
                            </button>
                            <!-- Modal {{ $preferredSuppliers->id }} -->
                            <x-adminlte-modal id="preferredSuppliers{{ $preferredSuppliers->id }}" title="{{ __('general_content.price_by_qty_trans_key') }} {{ $preferredSuppliers->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                              <form method="POST" action="{{ route('products.supplier.qty.price.create', ['id' => $Product->id]) }} }}" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                  <div class="row">
                                    <div class="form-group col-md-4">
                                      <input type="hidden"  value="{{ $preferredSuppliers->id }}" name="companies_id" id="companies_id">
                                    
                                      <input type="number" class="form-control"  name="min_qty" id="min_qty" placeholder="{{ __('general_content.quantite_min_trans_key') }}" step=".001">
                                    </div>
                                    <div class="form-group col-md-4">
                                      <input type="number" class="form-control"  name="max_qty" id="max_qty" placeholder="{{ __('general_content.quantite_max_trans_key') }}" step=".001">
                                    </div>
                                    <div class="form-group col-md-4">
                                      <div class="input-group">
                                          <div class="input-group-prepend">
                                              <span class="input-group-text">{{ $Factory->curency }}</span>
                                          </div>
                                          <input type="number" class="form-control"   name="price" id="price" placeholder="{{ __('general_content.price_trans_key') }}" step=".001">
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="card-footer">
                                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </div>
                              </form>
                              <div class="card-body">
                                <div class="row">
                                  <div class="card-body table-responsive p-0">
                                    <table class="table table-hover">
                                      <thead>
                                        <tr>
                                          <th>{{__('general_content.quantite_min_trans_key') }}</th>
                                          <th>{{__('general_content.quantite_max_trans_key') }}</th>
                                          <th>{{__('general_content.price_trans_key') }}</th>
                                          <th></th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        @forelse ($Product->getQuantityPricesForSupplier($preferredSuppliers->id) as $QuantityPrice)
                                        <tr>
                                          <td>{{ $QuantityPrice->min_qty }}</td>
                                          <td>{{ $QuantityPrice->max_qty }}</td>
                                          <td>{{ $QuantityPrice->price }} {{ $Factory->curency }}</td>
                                          <td></td>
                                        </tr>
                                        @empty
                                        <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                                        @endforelse
                                      </tbody>
                                      <tfoot>
                                        <tr>
                                          <th>{{__('general_content.quantite_min_trans_key') }}</th>
                                          <th>{{__('general_content.quantite_max_trans_key') }}</th>
                                          <th>{{__('general_content.price_trans_key') }}</th>
                                          <th></th>
                                        </tr>
                                      </tfoot>
                                    </table>
                                  </div>
                                </div>
                              </div>
                            </x-adminlte-modal>
                          </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{ __('general_content.supplier_rate_trans_key') }}</th>
                        <th></th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </x-adminlte-card>
            </div>
            <div class="col-md-6">
              <form  method="POST" action="{{ route('products.supplier.create') }}" class="form-horizontal" enctype="multipart/form-data">
                <x-adminlte-card title="{{ __('general_content.supplier_trans_key') }}" theme="secondary" maximizable>
                @csrf
                  <input type="hidden" name="product_id" value="{{ $Product->id }}">
                  <div class="form-group">
                      <x-adminlte-select2 name="companies_id" id="companies_id" label="{{ __('general_content.supplier_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.supplier_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-building"></i>
                            </div>
                        </x-slot>
                        <option value="NULL">-</option>
                        @foreach ($CompanieSelect as $item)
                        <option value="{{ $item->id }}" >{{ $item->code }} - {{ $item->label }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                  <x-slot name="footerSlot">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </x-slot>
                </x-adminlte-card>
              </form>
            </div>
          </div>
          <!-- /.row -->
        </div>
        @endif
        <div class="tab-pane" id="quote">
          @livewire('quotes-lines-index' , ['product_id' => $Product->id ])
        </div>
        <div class="tab-pane" id="order">
          @livewire('orders-lines-index' , ['product_id' => $Product->id ])
        </div>
        <div class="tab-pane" id="purchase">
          @livewire('purchases-lines-index' , ['search_product_id' => $Product->id, 'purchase_id' => null, 'OrderStatu' => 0 ])
        </div>
        <div class="tab-pane" id="serialNumber">
          @livewire('serial-numbers-index' , ['product_id' => $Product->id ])
        </div>
        @if($Product->drawing_file)
        <div class="tab-pane" id="DrawingViewer">
          <object data="{{ asset('drawing/'. $Product->drawing_file) }}" type="application/pdf" width="100%" height="1000px"></object>
        </div>
        @endif
        @if($Product->stl_file)
        <div class="tab-pane" id="StepViewer">
          <script type="importmap">
            {
                "imports": {
                    "three": "{{ asset('js/three.module.js') }}",
                    "stl-loader": "{{ asset('js/STLLoader.js') }}",
                    "orbit-controls": "{{ asset('js/OrbitControls.js') }}"
                }
            }
          </script>
          <!-- La div où sera affiché le rendu Three.js -->
          <div id="scene-container" style="width: 100%;  overflow: hidden;"></div>
        </div>
        @endif
        @if($Product->svg_file)
        <div class="tab-pane" id="SVGViewer">
          <img src="{{ asset('svg/') }}/{{ $Product->svg_file }}" width="800" height="800">
        </div>
        @endif
        <div class="tab-pane " id="Logs">
          @livewire('logs-viewer', ['subjectType' => 'App\Models\Products\Products', 'subjectId' => $Product->id])
        </div>
      </div>
    </x-adminlte-card>
  </div>
  
  <div class="col-md-4">
    <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
      <p class="text-muted">{{ __('general_content.external_id_trans_key') }} : {{ $Product->code }} </p>
      <div class="row"> 
        <div class="col-12 col-sm-4">
          <div class="text-muted">
          <p class="text-sm">{{ __('general_content.unit_trans_key') }}
            <b class="d-block">{{ $Product->Unit['label'] }}</b>
          </p>
          </div>
        </div>
      <!-- /.div row -->
      </div>
      @if($Product->sold == 1 )
      <hr>
      <div class="row"> 
        <div class="col-12 col-sm-4">
          <div class="text-muted">
          <p class="text-sm">{{ __('general_content.price_trans_key') }}
            <b class="d-block">{{ $Product->selling_price }} {{ $Factory->curency }}</b>
          </p>
          </div>
        </div>
      <!-- /.div row -->
      </div>
      @endif
      @if($Product->purchased == 1 )
      <hr>
      <div class="row">
        <div class="col-12 col-sm-4">
          <div class="text-muted">
          <p class="text-sm">{{ __('general_content.purchased_price_trans_key') }}
            <b class="d-block">{{ $Product->purchased_price }} {{ $Factory->curency }}</b>
          </p>
          <p class="text-sm">{{ __('general_content.weighted_average_price_trans_key') }}
            <b class="d-block"> {{ number_format($averageCost, 2) }} {{ $Factory->curency }}</b>
          </p>
          <p class="text-sm">{{ __('general_content.last_purchase_price_price_trans_key') }}
            <b class="d-block">{{ number_format($lastPurchasePrice, 2) }} {{ $Factory->curency }}</b>
          </p>
          <p class="text-sm">{{ __('general_content.average_supply_delay_trans_key') }}
            <b class="d-block">
              @if(!is_null($averageSupplyDelay))
                {{ number_format($averageSupplyDelay, 0) }} {{ __('general_content.day_trans_key') }}
              @else
                N/A
              @endif
            </b>
          </p>
          </div>
        </div>
      <!-- /.div row -->
      </div>
      @endif
      @if($Product->qty_eco_min)
      <hr>
      <div class="row">
        <div class="col-12 col-sm-4">
          <div class="text-muted">
          <p class="text-sm">{{ __('general_content.quantite_eco_min_trans_key') }}
            <b class="d-block">{{ $Product->qty_eco_min }}</b>
          </p>
          </div>
        </div>
      <!-- /.div row -->
      </div>
      @endif
      @if($Product->qty_eco_max)
      <hr>
      <div class="row">
        <div class="col-12 col-sm-4">
          <div class="text-muted">
          <p class="text-sm">{{ __('general_content.quantite_eco_max_trans_key') }}
            <b class="d-block">{{ $Product->qty_eco_max }}</b>
          </p>
          </div>
        </div>
      <!-- /.div row -->
      </div>
      @endif
    </x-adminlte-card>

    <x-adminlte-card title="{{ __('general_content.picture_file_trans_key') }}" theme="success" collapsible="collapsed" maximizable>
        @if($Product->picture)
            <img src="{{ asset('/images/products/'. $Product->picture) }}" alt="Product Image" style="width: 100%;">
        @endif
        <form action="{{ route('products.update.image') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
          <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="far fa-image"></i></span>
              </div>
              <div class="custom-file">
                  <input type="hidden" name="id" value="{{ $Product->id }}">
                  <input type="file" class="custom-file-input" name="picture" id="picture">
                  <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
              </div>
              <div class="input-group-append">
                  <button type="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
              </div>
          </div>
        </form>
        <form action="{{ route('products.update.drawing') }}" method="post" enctype="multipart/form-data">
          @csrf
          <label for="chooseFile">{{ __('general_content.drawing_trans_key') }}</label> (.pdf | max: 10 240 Ko)
          
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="far fa-file"></i></span>
            </div>
            <div class="custom-file">
              <input type="hidden" name="id" value="{{ $Product->id }}" >
              <input type="file" name="drawing" class="custom-file-input" id="drawing">
              <label class="custom-file-label" for="drawing">{{ __('general_content.choose_file_trans_key') }}</label>
            </div>
            <div class="input-group-append">
              <button type="submit" name="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
            </div>
          </div>
        </form>
        <form action="{{ route('products.update.stl') }}" method="post" enctype="multipart/form-data">
          @csrf
          <label for="chooseFile">{{ __('general_content.stl_file_trans_key') }}</label> (.stl | max: 10 240 Ko)
          
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="far fa-file"></i></span>
            </div>
            <div class="custom-file">
              <input type="hidden" name="id" value="{{ $Product->id }}" >
              <input type="file" name="stl" class="custom-file-input" id="stl">
              <label class="custom-file-label" for="stl">{{ __('general_content.choose_file_trans_key') }}</label>
            </div>
            <div class="input-group-append">
              <button type="submit" name="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
            </div>
          </div>
        </form>
        <form action="{{ route('products.update.svg') }}" method="post" enctype="multipart/form-data">
            @csrf
            <label for="chooseFile">{{ __('general_content.svg_file_trans_key') }}</label> (.svg | max: 10 240 Ko)
            
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="far fa-file"></i></span>
              </div>
              <div class="custom-file">
                <input type="hidden" name="id" value="{{ $Product->id }}" >
                <input type="file" name="svg" class="custom-file-input" id="svg">
                <label class="custom-file-label" for="svg">{{ __('general_content.choose_file_trans_key') }}</label>
              </div>
              <div class="input-group-append">
                <button type="submit" name="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
              </div>
            </div>
        </form>
    </x-adminlte-card>
        
    @include('include.file-store', ['inputName' => "products_id",'inputValue' => $Product->id,'filesList' => $Product->files,])
    
    <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible="collapsed" maximizable>
      <p>
        <a href="{{ route('products.duplicate', $Product->id)}}" class="btn btn-sm btn-default btn-block mb-2">
          <i class="fa fa-copy"></i> {{ __('general_content.duplicate_product_trans_key') }}
        </a>
      </p>
      <p>
        @php echo '<img src="data:image/jpeg;base64,' . DNS1D::getBarcodePNG  (strval($Product->id), $Factory->task_barre_code, 4,60,array(1,1,1), true). '" alt="barcode"   />'; @endphp
      </p>
    </x-adminlte-card>

    <x-adminlte-card title="{{ __('ABC/FMR') }}" theme="danger" collapsible="collapsed" maximizable>
      <div >
        <div class="row justify-content-center">
              <div class="col-3 mb-1 mr-1 bg-warning text-center" id="AR" ><h1>AR</h1></div>
              <div class="col-3 mb-1 mr-1 bg-success text-center" id="AM" ><h1>AM</h1></div>
              <div class="col-3 mb-1 bg-success text-center" id="AF" ><h1>AF</h1></div>
          </div>
          <div class="row justify-content-center">
              <div class="col-3 mb-1 mr-1 bg-warning text-center" id="BR" ><h1>BR</h1></div>
              <div class="col-3 mb-1 mr-1 bg-success text-center" id="BM" ><h1>BM</h1></div>
              <div class="col-3 mb-1 bg-success text-center" id="BF" ><h1>BF</h1></div>
        </div>
        <div class="row justify-content-center">
              <div class="col-3 mb-1 mr-1 bg-danger text-center" id="CR" ><h1>CR</h1></div>
              <div class="col-3 mb-1 mr-1 bg-purple text-center" id="CM" ><h1>CM</h1></div>
              <div class="col-3 mb-1 bg-purple text-center" id="CF" ><h1>CF</h1></div>
      </div>
      </div>
    </x-adminlte-card>
  </div>    
</div>
@stop

@section('css')
@stop

@section('js')
<script>
  // Données passées de Laravel à JavaScript
  const analysisData = @json($finalAnalysis);

  // Parcourir les données et placer les croix dans les bonnes cellules
  analysisData.forEach(product => {
      const cellId = product.category;
      const cell = document.getElementById(cellId);
      if (cell) {
          cell.innerHTML += '<span class="cross">✕</span>';
      }
  });
</script>

<script type="module">
  // Récupérer l'élément conteneur par son ID
  const container = document.getElementById('scene-container');

  // Ajouter un gestionnaire d'événements pour redimensionner la fenêtre
  window.addEventListener( 'resize', onWindowResize );

  // Importation des modules ES6
  import * as THREE from '{{ asset('js/three.module.js') }}';
  import { STLLoader } from '{{ asset('js/STLLoader.js') }}';
  import { OrbitControls  } from '{{ asset('js/OrbitControls.js') }}';

  // Récupérer l'ID du produit à partir de Blade
  const productStlFile = '{{ $Product->stl_file }}';
  // Charger le modèle 3D en utilisant l'ID du produit
  const modelUrl = `{{ asset('stl/') }}/${productStlFile}`;
  // Créer une scène Three.js
  const scene = new THREE.Scene();
  // Définir la couleur de fond de la scène (par exemple, un fond gris foncé)
  scene.background = new THREE.Color(0x333333);
  // Créer une caméra
  const camera = new THREE.PerspectiveCamera( 75, window.innerWidth / window.innerHeight, 0.1, 1000 );
  //ajouter un éclairage ambiant 
  const directionalLight = new THREE.DirectionalLight(0xFFFFFF, 0.3); // Couleur blanche, intensité 0.5
  directionalLight.position.set(1, 1, 1).normalize(); // Position de la lumière
  directionalLight.shadow.bias = -0.01;
  scene.add(directionalLight);

  // Créer un rendu
  const renderer = new THREE.WebGLRenderer();
  renderer.setSize( window.innerWidth, window.innerHeight );
  document.body.appendChild( renderer.domElement );

  // Créez un contrôle d'orbite
  const controls = new OrbitControls(camera, renderer.domElement);
  controls.listenToKeyEvents( window ); // optional
  controls.enableDamping = true; // an animation loop is required when either damping or auto-rotation are enabled
  controls.dampingFactor = 0.05;
  controls.screenSpacePanning = false;
  controls.minDistance = 50;
  controls.maxDistance = 500;

  function animate() {
    requestAnimationFrame( animate );

    // Mettez à jour les contrôles
    controls.update(); // only required if controls.enableDamping = true, or if controls.autoRotate = true

    renderer.render( scene, camera );
  };

  const loadObject = () => {
      const loader = new STLLoader();
      let group, mesh; 
      // Créer un chargeur STL
      loader.load(modelUrl, function (geometry) {
          group = new THREE.Group()
          scene.add(group)

          const material = new THREE.MeshPhongMaterial({ color: 0xFFF2CC, specular: 0xffffff , shininess: 50 })
          mesh = new THREE.Mesh(geometry, material)
          mesh.position.set(0, 0, 5); // Placez la caméra à une position appropriée
          mesh.lookAt(mesh.position); // Orientez la caméra vers le modèle
          mesh.scale.set(1, 1, 1)
          mesh.castShadow = true
          mesh.receiveShadow = true

          geometry.center()
          group.add(mesh)
      })
  }

  function onWindowResize() {

    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();

    renderer.setSize( window.innerWidth, window.innerHeight );

    }

  // Ajouter le rendu à l'élément conteneur
  container.appendChild(renderer.domElement);

  animate();

  loadObject(); // Chargez votre objet STL
</script>

<script type="text/javascript">
  $('.custom-file-input').on('change',function(){
    // Obtient le nom du fichier sélectionné
    var fileName = $(this).val().split('\\').pop(); 
    // Sélectionne le label correspondant et met à jour son contenu
    $(this).next('.custom-file-label').addClass("selected").html(fileName);
  });
</script>

<script type="text/javascript">
  $(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip(); // Active les infobulles Bootstrap pour tous les éléments qui ont l'attribut data-toggle="tooltip"
  });
</script>
@stop
