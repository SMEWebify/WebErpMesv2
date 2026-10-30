@extends('adminlte::page')

@section('title', __('general_content.products_trans_key'))
@section('plugins.Quill', true)

@section('content_header')
  <x-Content-header-previous-button  :h1="$Product->label" previous="{{ $previousUrl }}" list="{{ route('products') }}" next="{{ $nextUrl }}"/>
@stop

@section('content')
@php
  // Grille tarifaire client (onglet « Tarifs ») : affichée pour un article vendu, ou dès qu'elle a des paliers.
  $showPricesTab = $Product->sold == 1 || $CustomerPriceLists->isNotEmpty();
  $customerTypeOptions = [
      ['value' => '', 'label' => __('general_content.all_customer_types_trans_key')],
      ['value' => '1', 'label' => __('general_content.legal_entity_trans_key')],
      ['value' => '2', 'label' => __('general_content.individual_trans_key')],
  ];
  $customerTypeLabels = collect($customerTypeOptions)->pluck('label', 'value');
  // Saisie refusée par la validation : la fenêtre se rouvre avec les valeurs tapées.
  $priceListReopen = session()->hasOldInput('min_qty') && $errors->getBag('customerPriceList')->any()
      ? ['id' => old('_price_list_id'), 'companiesId' => old('companies_id'), 'customerType' => old('customer_type'),
         'minQty' => old('min_qty'), 'maxQty' => old('max_qty'), 'price' => old('price')]
      : null;
@endphp



<div class="row">
  <div class="col-md-8">
    <x-adminlte-card title="{{ __('general_content.product_info_trans_key') }}" theme="teal" theme-mode="outline" maximizable>
        <ul class="nav nav-tabs" id="productTab" role="tablist">
          <li class="nav-item"><a class="nav-link active" href="#Product" data-bs-toggle="tab"><i class="fas fa-info-circle"></i> {{ __('general_content.product_info_trans_key') }}</a></li>
          @if($showPricesTab)
          <li class="nav-item"><a class="nav-link" href="#Prices" data-bs-toggle="tab"><i class="fas fa-tags"></i> {{ __('price_list.tab') }} @if($CustomerPriceLists->isNotEmpty())<span class="badge text-bg-secondary ms-1">{{ $CustomerPriceLists->count() }}</span>@endif</a></li>
          @endif
          <li class="nav-item"><a class="nav-link" href="#TechnicalInfo" data-bs-toggle="tab"><i class="fas fa-cogs"></i> {{ __('general_content.tech_bom_trans_key') }} {{ $Product->getAllTaskCountAttribute() }}</a></li>
          @can('stock-lot-serial-management')
          <li class="nav-item"><a class="nav-link" href="#Stock" data-bs-toggle="tab"><i class="fas fa-boxes"></i> {{ __('general_content.stock_trans_key') }} ({{ $Product->StockLocationProductCount() }})</a></li>
          @endcan
          @if($CustomFields->count() > 0)
          <li class="nav-item"><a class="nav-link" href="#CustomFields" data-bs-toggle="tab"><i class="fas fa-list"></i> {{ __('general_content.custom_fields_trans_key') }}</a></li>
          @endif
          @if($Product->purchased == 1 )
          <li class="nav-item"><a class="nav-link" href="#PreferredSupplier" data-bs-toggle="tab"> <i class="fas fa-truck"></i>{{ __('general_content.preferred_supplier_trans_key') }}</a></li>
          @endif
          <li class="nav-item"><a class="nav-link" href="#history" data-bs-toggle="tab"><i class="fas fa-history"></i> {{ __('general_content.historical_trans_key') }}</a></li>
          @can('stock-lot-serial-management')
          <li class="nav-item"><a class="nav-link" href="#serialNumber" data-bs-toggle="tab"><i class="fas fa-barcode"></i> {{ __('general_content.serial_numbers_trans_key') }}</a></li>
          @endcan
          
          <li class="nav-item"><a class="nav-link" href="#Documents" data-bs-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#ControlPlans" data-bs-toggle="tab"><i class="fas fa-pen-ruler"></i> {{ __('inspection.product_tab') }}</a></li>
          <li class="nav-item"><a class="nav-link" href="#Logs" data-bs-toggle="tab"><i class="fas fa-history"></i> Logs</a></li>
        </ul>
      <!-- /.card-header -->
      <div class="tab-content">
        @include('include.alert-result')
        @unless($canEditProduct)
        <div class="alert alert-info py-2 mt-2">
          <i class="fas fa-eye me-2"></i>{{ __('products.read_only') }}
        </div>
        @endunless
        <div class="tab-pane active" id="Product">
          <form method="POST" action="{{ route('products.update', ['id' => $Product->id]) }}" enctype="multipart/form-data">
              @csrf
              {{-- Lecture seule (products-menu sans products-edit) : champs désactivés, pas d'enregistrement. --}}
              <fieldset class="border-0 p-0 m-0" @disabled(! $canEditProduct)>
              <div class="card card-body mb-3">
                <div class="row">
                    <div class="mb-3 col-md-4">
                      <div class="text-muted">
                        <label for="label">{{ __('general_content.external_id_trans_key') }}</label>
                          <b class="d-block">{{ $Product->code }}</b>
                        </p>
                      </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="label">{{ __('general_content.description_trans_key') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            <input type="text" class="form-control" value="{{ $Product->label }}" name="label"  id="label" placeholder="{{ __('general_content.description_trans_key') }}">
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="ind">{{ __('general_content.index_trans_key') }}</label>
                        <input type="text" class="form-control" value="{{ $Product->ind }}"   name="ind"  id="ind" placeholder="{{ __('general_content.index_trans_key') }}">
                    </div>
                </div>
              </div>
              <div class="card card-body mb-3">
                <div class="row">
                    <div class="mb-3 col-md-4">
                        @include('include.form.form-select-service',['serviceId' =>  $Product->methods_services_id  ])
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="methods_families_id">{{ __('general_content.select_family_trans_key') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-grip-horizontal"></i></span>
                            <select class="form-select" name="methods_families_id" id="methods_families_id">
                                <option value="">{{ __('general_content.family_trans_key') }}</option>
                                @forelse ($FamiliesSelect as $item)
                                <option value="{{ $item->id }}" @if($Product->methods_families_id == $item->id ) Selected @endif >{{ $item->label }}</option>
                                @empty
                                    <option value="">{{ __('general_content.no_family_trans_key') }}</option>
                                @endforelse
                            </select>
                        </div>
                    </div>
                    {{-- Nature de TVA (axe de la matrice + levier d'imputation : usinage / outillage…). --}}
                    <div class="mb-3 col-md-4">
                        <label for="vat_nature_id">{{ __('vat.product_nature') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                            <select class="form-select" name="vat_nature_id" id="vat_nature_id">
                                <option value="">{{ __('vat.default_option') }}</option>
                                @foreach (\App\Models\Accounting\VatNature::orderBy('code')->get() as $nature)
                                <option value="{{ $nature->id }}" @if($Product->vat_nature_id == $nature->id) Selected @endif>{{ $nature->code }} — {{ $nature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="methods_units_id">{{ __('general_content.unit_trans_key') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                            <select class="form-select" name="methods_units_id" id="methods_units_id">
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
              <div class="card card-body mb-3">
                <div class="row">
                    <div class="mb-3 col-md-4">
                      <div class="col-4 text-start"><label for="purchased" class="col-form-label">{{ __('general_content.purchased_trans_key') }}</label></div>
                      <div class="col-8">
                          @if($Product->purchased == 1)  
                          <x-adminlte-input-switch id="purchased" name="purchased" is-checked="true" />
                          @else
                          <x-adminlte-input-switch id="purchased" name="purchased" />
                          @endif
                      </div>
                    </div>
                    <div class="mb-3 col-md-4">
                      <div class="col-4 text-start"><label for="sold" class="col-form-label">{{ __('general_content.sold_trans_key') }}</label></div>
                      <div class="col-8">
                          @if($Product->sold == 1)  
                          <x-adminlte-input-switch id="sold" name="sold" is-checked="true" />
                          @else
                          <x-adminlte-input-switch id="sold" name="sold" />
                          @endif
                      </div>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label for="tracability_type">{{ __('general_content.tracability_trans_key') }}</label>
                        <select class="form-select" name="tracability_type" id="tracability_type">
                            <option value="">{{ __('general_content.select_type_trans_key') }}</option>
                            <option value="1" @if($Product->tracability_type == 1 ) Selected @endif>{{ __('general_content.no_traceability_trans_key') }}</option>
                            <option value="2" @if($Product->tracability_type == 2 ) Selected @endif>{{ __('general_content.with_batch_number_trans_key') }}</option>
                            <option value="3" @if($Product->tracability_type == 3 ) Selected @endif>{{ __('general_content.with_serial_number_trans_key') }}</option>
                        </select>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label for="control_plan_mode">{{ __('inspection.control_plan_mode') }}</label>
                        <select class="form-select" name="control_plan_mode" id="control_plan_mode">
                            <option value="">{{ __('inspection.control_plan_mode_follow') }}</option>
                            <option value="always" @selected($Product->control_plan_mode === 'always')>{{ __('inspection.control_plan_mode_always') }}</option>
                            <option value="never" @selected($Product->control_plan_mode === 'never')>{{ __('inspection.control_plan_mode_never') }}</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    @foreach (\App\Services\Purchases\PurchaseCriticality::options() as $field => $values)
                    <div class="mb-3 col-md-4">
                        <label for="{{ $field }}">{{ __('purchase_quality.criticality') }} — {{ __('purchase_quality.' . $field) }}</label>
                        <select class="form-select" name="{{ $field }}" id="{{ $field }}">
                            <option value="">{{ __('purchase_quality.none') }}</option>
                            @foreach (array_unique(array_filter(array_merge($values, [$Product->{$field}]))) as $value)
                            <option value="{{ $value }}" @selected($Product->{$field} === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                    <div class="col-12 form-text mb-3">{{ __('purchase_quality.product_help') }}</div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">{{ $Factory->curency }}</span>
                            <input type="number" class="form-control" value="{{ $Product->purchased_price }}"  name="purchased_price" id="purchased_price" placeholder="{{ __('general_content.purchased_price_trans_key') }}" step=".001">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#priceHistoryModal" data-focus-type="purchase" title="Historique des prix d'achat">
                                    <i class="fas fa-history"></i>
                                </button>
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">{{ $Factory->curency }}</span>
                            <input type="number" class="form-control"  value="{{ $Product->selling_price }}" name="selling_price" id="selling_price" placeholder="{{ __('general_content.price_trans_key') }}" step=".001">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#priceHistoryModal" data-focus-type="sale" title="Historique des prix de vente">
                                    <i class="fas fa-history"></i>
                                </button>
                            @if($lastSale)
                            @php
                              $lastSaleText = __($lastSale['type'] === 'invoice'
                                      ? 'general_content.last_sale_price_invoice_trans_key'
                                      : 'general_content.last_sale_price_order_trans_key', [
                                  'price' => $lastSale['price'] . ' ' . $Factory->curency,
                                  'code' => $lastSale['code'],
                                  'date' => $lastSale['date'],
                              ]);
                            @endphp
                            {{-- Formulaire hors de celui de la fiche (pas de formulaire imbriqué) : attribut form. --}}
                            <button type="submit" form="selling-price-from-last-sale-form" class="btn btn-outline-primary"
                                    title="{{ $lastSaleText }}" data-bs-toggle="tooltip"
                                    @disabled((float) $Product->selling_price === $lastSale['price'])
                                    onclick="return confirm(@js(__('general_content.apply_last_sale_price_confirm_trans_key', ['detail' => $lastSaleText])));">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            @endif
                        </div>
                        @if($lastSale)
                        <small class="text-muted">
                          <a href="{{ $lastSale['url'] }}">{{ $lastSaleText }}</a>
                        </small>
                        @endif
                    </div>
                </div>
              </div>
              <div class="card card-body mb-3">
                <div class="row">
                    <label for="material">{{ __('general_content.proprieties_trans_key') }}</label>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                            <input type="text" class="form-control" value="{{ $Product->material }}" name="material" id="material"  placeholder="{{ __('general_content.material_trans_key') }}">
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                            <input type="number" class="form-control" value="{{ $Product->thickness }}" name="thickness" id="thickness"  placeholder="{{ __('general_content.thickness_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                            <input type="number" class="form-control" value="{{ $Product->weight }}" name="weight" id="weight"  placeholder="{{ __('general_content.weight_trans_key') }}" step=".001">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                            <input type="number" class="form-control" value="{{ $Product->bend_count }}" name="bend_count" id="bend_count" placeholder="{{ __('general_content.bend_count_trans_key') }}" step="1" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                  <div class="mb-3 col-md-4">
                      <div class="input-group">
                          <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                          <input type="text" class="form-control" value="{{ $Product->finishing }}" name="finishing" id="finishing"  placeholder="{{ __('general_content.finishing_trans_key') }}">
                      </div>
                  </div>
                  <div class="mb-3 col-md-4">
                    <div class="input-group">
                      <span class="input-group-text"><i class="far fa-folder-open"></i></span>
                      <input type="text" class="form-control" value="{{ $Product->cad_file_path }}" name="cad_file_path" id="cad_file_path" placeholder="{{ __('CAD file path') }}">
                    </div>
                  </div>
                  <div class="mb-3 col-md-4">
                    <div class="input-group">
                      <span class="input-group-text"><i class="far fa-folder-open"></i></span>
                      <input type="text" class="form-control" value="{{ $Product->cam_file_path }}" name="cam_file_path" id="cam_file_path" placeholder="{{ __('CAM file path') }}">
                    </div>
                  </div>
                </div>
                <hr>
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="x_size">X</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            <input type="number" class="form-control" value="{{ $Product->x_size }}" name="x_size" id="x_size"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="y_size">Y</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            <input type="number" class="form-control" value="{{ $Product->y_size }}"  name="y_size" id="y_size"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
                        </div>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label for="z_size">Z</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            <input type="number" class="form-control" value="{{ $Product->z_size }}" name="z_size" id="z_size"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="mb-3 col-md-4">
                      <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        <input type="number" class="form-control"  value="{{ $Product->x_oversize }}" name="x_oversize" id="x_oversize"  placeholder="{{ __('general_content.x_oversize_trans_key') }}" step=".001">
                    </div></div>
                    <div class="mb-3 col-md-4">
                      <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        <input type="number" class="form-control" value="{{ $Product->y_oversize }}" name="y_oversize" id="y_oversize"  placeholder="{{ __('general_content.y_oversize_trans_key') }}" step=".001">
                    </div></div>
                    <div class="mb-3 col-md-4">
                      <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        <input type="number" class="form-control" value="{{ $Product->z_oversize }}" name="z_oversize" id="z_oversize"  placeholder="{{ __('general_content.z_oversize_trans_key') }}" step=".001">
                    </div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->diameter }}" name="diameter" id="diameter"  placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                    </div>
                    <div class="mb-3 col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize"  placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                    </div>
                    <div class="mb-3 col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->section_size }}" name="section_size" id="section_size" placeholder="Section size" step=".001">
                    </div>
                </div>
              </div>
              <div class="card card-body mb-3">
                <div class="row">
                    <label for="qty_eco_min">{{ __('general_content.other_information_trans_key') }}</label>
                </div>
                <hr>
                <div class="row">
                    <div class="mb-3 col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->qty_eco_min }}" name="qty_eco_min" id="qty_eco_min" placeholder="{{ __('general_content.quantite_eco_min_trans_key') }}" step=".001">
                    </div>
                    <div class="mb-3 col-md-4">
                      <input type="number" class="form-control" value="{{ $Product->qty_eco_max }}" name="qty_eco_max" id="qty_eco_max" placeholder="{{ __('general_content.quantite_eco_max_trans_key') }}" step=".001">
                    </div>
                    <div class="mb-3 col-md-4"></div>
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
              </fieldset>
            @if($canEditProduct)
            <div class="card-footer">
              <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
            </div>
            @endif
          </form>
        </div>
        @if($showPricesTab)
        <div class="tab-pane" id="Prices">
          @php
            $basePrice = (float) $Product->selling_price;
            $priceListErrors = $errors->getBag('customerPriceList');
            // Du plus large au plus précis ; la priorité appliquée est l'inverse (cf. priority_help).
            $priceGroups = $CustomerPriceLists
                ->groupBy(fn ($p) => $p->companies_id ? 'c' . $p->companies_id : (! is_null($p->customer_type) ? 't' . $p->customer_type : 'all'))
                ->sortBy(fn ($rows, $key) => $key === 'all' ? '0' : ($key[0] === 't' ? '1' . $key : '2' . ($rows->first()->company->label ?? $key)))
                ->map(function ($rows, $key) use ($customerTypeLabels) {
                    $first = $rows->first();
                    $rows = $rows->sortBy('min_qty')->values();
                    $overlap = false;
                    foreach ($rows as $i => $row) {
                        $next = $rows->get($i + 1);
                        if ($next && (is_null($row->max_qty) || $next->min_qty <= $row->max_qty)) {
                            $overlap = true;
                        }
                    }

                    return [
                        'label' => $key === 'all'
                            ? __('price_list.scope_all')
                            : ($key[0] === 't'
                                ? __('price_list.group_type', ['type' => $customerTypeLabels[(string) $first->customer_type] ?? $first->customer_type])
                                : __('price_list.group_company', ['company' => $first->company->label ?? ('#' . $first->companies_id)])),
                        'icon' => $key === 'all' ? 'fa-globe' : ($key[0] === 't' ? 'fa-users' : 'fa-building'),
                        'rows' => $rows,
                        'overlap' => $overlap,
                    ];
                });
          @endphp

          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
              <span class="text-body-secondary">{{ __('price_list.base_price') }} :</span>
              <b>{{ number_format($basePrice, 2, ',', ' ') }} {{ $Factory->curency }}</b>
            </div>
            @if($canEditProduct)
            <button type="button" class="btn btn-success btn-sm" data-price-list-open>
              <i class="fas fa-plus me-1"></i>{{ __('price_list.add') }}
            </button>
            @endif
          </div>

          @if($priceListErrors->any())
          <div class="alert alert-warning">
            <ul class="mb-0">
              @foreach($priceListErrors->all() as $message)
              <li>{{ $message }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          @if($priceGroups->isEmpty())
          <p class="text-body-secondary text-center py-4 mb-0">{{ __('price_list.empty') }}</p>
          @else
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-2">
              <thead>
                <tr>
                  <th>{{ __('price_list.quantity') }}</th>
                  <th class="text-end">{{ __('general_content.price_trans_key') }}</th>
                  <th class="text-end">{{ __('price_list.gap') }}</th>
                  <th class="text-end" style="width: 1%"></th>
                </tr>
              </thead>
              @foreach($priceGroups as $group)
              <tbody>
                <tr class="table-light">
                  <th colspan="4">
                    <i class="fas {{ $group['icon'] }} me-1 text-body-secondary"></i>{{ $group['label'] }}
                    @if($group['overlap'])
                    <span class="badge text-bg-warning ms-2"><i class="fas fa-triangle-exclamation me-1"></i>{{ __('price_list.overlap') }}</span>
                    @endif
                  </th>
                </tr>
                @foreach($group['rows'] as $priceList)
                @php
                  $gap = $basePrice > 0 ? ($priceList->price - $basePrice) / $basePrice * 100 : null;
                @endphp
                <tr>
                  <td class="ps-4">
                    @if(is_null($priceList->max_qty))
                      &ge; {{ $priceList->min_qty }}
                    @elseif($priceList->max_qty == $priceList->min_qty)
                      {{ $priceList->min_qty }}
                    @else
                      {{ $priceList->min_qty }} – {{ $priceList->max_qty }}
                    @endif
                  </td>
                  <td class="text-end fw-semibold">{{ number_format($priceList->price, 2, ',', ' ') }} {{ $Factory->curency }}</td>
                  <td class="text-end text-body-secondary">
                    @if(! is_null($gap))
                      {{ $gap > 0 ? '+' : '' }}{{ number_format($gap, 1, ',', ' ') }} %
                    @else
                      —
                    @endif
                  </td>
                  <td class="text-end text-nowrap">
                    @if($canEditProduct)
                    <button type="button" class="btn btn-sm btn-outline-primary" title="{{ __('price_list.edit') }}"
                      data-price-list-open
                      data-id="{{ $priceList->id }}"
                      data-companies-id="{{ $priceList->companies_id }}"
                      data-customer-type="{{ $priceList->customer_type }}"
                      data-min-qty="{{ $priceList->min_qty }}"
                      data-max-qty="{{ $priceList->max_qty }}"
                      data-price="{{ $priceList->price }}">
                      <i class="fas fa-pen"></i>
                    </button>
                    <form method="POST" action="{{ route('products.customer-price-list.destroy', ['product' => $Product->id, 'priceList' => $priceList->id]) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('general_content.delete_trans_key') }}" onclick="return confirm(@js(__('price_list.delete_confirm')));">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
              @endforeach
            </table>
          </div>
          <small class="text-body-secondary"><i class="fas fa-circle-info me-1"></i>{{ __('price_list.priority_help') }}</small>
          @endif
        </div>
        @endif
        <div class="tab-pane " id="TechnicalInfo">
          @php
          $taskManageEndpoints = [
              'initial_data'          => route('task.manage.json.initial',      ['products_id', $Product->id, $Product->id]),
              'select_data'           => route('task.manage.json.select-data',  ['products_id', $Product->id, $Product->id]),
              'task_store'            => route('task.manage.json.task.store',   ['products_id', $Product->id, $Product->id]),
              'task_update'           => route('task.manage.json.task.update',  ['products_id', $Product->id, '__ID__']),
              'task_destroy'          => route('task.manage.json.task.destroy', ['products_id', $Product->id, '__ID__']),
              'task_duplicate'        => route('task.manage.json.task.duplicate', ['products_id', $Product->id, '__ID__']),
              'task_dependencies'     => route('task.manage.json.task.dependencies', ['products_id', $Product->id, '__ID__']),
              'subassembly_store'     => route('task.manage.json.subassembly.store',     ['products_id', $Product->id, $Product->id]),
              'subassembly_update'    => route('task.manage.json.subassembly.update',    ['products_id', $Product->id, '__ID__']),
              'subassembly_destroy'   => route('task.manage.json.subassembly.destroy',   ['products_id', $Product->id, '__ID__']),
              'subassembly_duplicate' => route('task.manage.json.subassembly.duplicate', ['products_id', $Product->id, '__ID__']),
              'import_csv'            => route('task.manage.json.import-csv',            ['products_id', $Product->id, $Product->id]),
              'apply_nomenclature'    => route('task.manage.json.apply-nomenclature',    ['products_id', $Product->id, $Product->id, '__TPL_ID__']),
          ];
          @endphp
          <div
            id="task-manage-app"
            data-id-type="products_id"
            data-id-page="{{ $Product->id }}"
            data-id-line="{{ $Product->id }}"
            data-statu="{{ $canEditProduct ? 1 : 0 }}"
            data-currency="{{ app('Factory')->curency ?? '€' }}"
            data-endpoints="{{ json_encode($taskManageEndpoints) }}"
          ></div>
        </div>
        @can('stock-lot-serial-management')
        <div class="tab-pane " id="Stock">
          @isset($reservationSummary)
            @php
              $availableClass = $reservationSummary['available'] < 0 ? 'text-bg-danger' : ($reservationSummary['available'] == 0 ? 'text-bg-warning' : 'text-bg-success');
            @endphp
            <div class="row mb-2">
              <div class="col-md-3">
                <div class="small-box text-bg-info">
                  <div class="inner"><h4>{{ $reservationSummary['physical'] }}</h4><p>Stock physique</p></div>
                  <i class="small-box-icon fas fa-boxes" aria-hidden="true"></i>
                </div>
              </div>
              <div class="col-md-3">
                <div class="small-box text-bg-primary">
                  <div class="inner"><h4>{{ $reservationSummary['reserved'] }}</h4><p>Réservé (tâches)</p></div>
                  <i class="small-box-icon fas fa-lock" aria-hidden="true"></i>
                </div>
              </div>
              <div class="col-md-3">
                <div class="small-box {{ $availableClass }}">
                  <div class="inner"><h4>{{ $reservationSummary['available'] }}</h4><p>Disponible</p></div>
                  <i class="small-box-icon fas fa-check-circle" aria-hidden="true"></i>
                </div>
              </div>
              <div class="col-md-3">
                <div class="small-box {{ $reservationSummary['missing_total'] > 0 ? 'text-bg-danger' : 'text-bg-secondary' }}">
                  <div class="inner"><h4>{{ $reservationSummary['missing_total'] }}</h4><p>Manque total</p></div>
                  <i class="small-box-icon fas fa-exclamation-triangle" aria-hidden="true"></i>
                </div>
              </div>
            </div>
          @endisset
          <x-adminlte-card title="{{ __('general_content.stock_location_product_list_trans_key') }}" theme="primary" maximizable>
            @include('include.table-stock-locations-products')
          </x-adminlte-card>
        </div>
        @endcan
        @if($CustomFields->count() > 0)
        <div class="tab-pane" id="CustomFields">
          @include('include.custom-fields-form', ['id' => $Product->id, 'type' => 'product', 'customFieldsReadOnly' => ! $canEditProduct])
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
                                  <span class="badge text-bg-warning">&#9733;</span>
                              @else
                                  <span class="badge text-bg-info">&#9734;</span>
                              @endif
                          @endfor
                        </td>
                        <td><x-ButtonTextView route="{{ route('companies.show', ['id' => $preferredSuppliers->id])}}" /></td>
                        <td class="py-0 align-middle">
                            <!-- Button Modal -->
                            <button type="button" class="btn btn-teal" data-bs-toggle="modal" data-bs-target="#preferredSuppliers{{ $preferredSuppliers->id }}">
                              x €
                            </button>
                            <!-- Modal {{ $preferredSuppliers->id }} -->
                            <x-adminlte-modal id="preferredSuppliers{{ $preferredSuppliers->id }}" title="{{ __('general_content.price_by_qty_trans_key') }} {{ $preferredSuppliers->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                              @if($canEditProduct)
                              <form method="POST" action="{{ route('products.supplier.qty.price.create', ['id' => $Product->id]) }} }}" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                  <div class="row">
                                    <div class="mb-3 col-md-4">
                                      <input type="hidden"  value="{{ $preferredSuppliers->id }}" name="companies_id" id="companies_id">
                                    
                                      <input type="number" class="form-control"  name="min_qty" id="min_qty" placeholder="{{ __('general_content.quantite_min_trans_key') }}" step=".001">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                      <input type="number" class="form-control"  name="max_qty" id="max_qty" placeholder="{{ __('general_content.quantite_max_trans_key') }}" step=".001">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                      <div class="input-group">
                                          <span class="input-group-text">{{ $Factory->curency }}</span>
                                          <input type="number" class="form-control"   name="price" id="price" placeholder="{{ __('general_content.price_trans_key') }}" step=".001">
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="card-footer">
                                  <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </div>
                              </form>
                              @endif
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
            @if($canEditProduct)
            <div class="col-md-6">
              <form  method="POST" action="{{ route('products.supplier.create') }}" class="form-horizontal" enctype="multipart/form-data">
                <x-adminlte-card title="{{ __('general_content.supplier_trans_key') }}" theme="secondary" maximizable>
                @csrf
                  <input type="hidden" name="product_id" value="{{ $Product->id }}">
                  <div class="mb-3">
                      <x-adminlte-select-bs name="companies_id" id="companies_id" label="{{ __('general_content.supplier_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.supplier_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text text-bg-info bg-gradient">
                                <i class="fas fa-building"></i>
                            </div>
                        </x-slot>
                        <option value="NULL">-</option>
                        @foreach ($CompanieSelect as $item)
                        <option value="{{ $item->id }}" >{{ $item->code }} - {{ $item->label }}</option>
                        @endforeach
                    </x-adminlte-select-bs>
                  </div>
                  <x-slot name="footerSlot">
                    <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </x-slot>
                </x-adminlte-card>
              </form>
            </div>
            @endif
          </div>
          <!-- /.row -->
        </div>
        @endif
        <div class="tab-pane" id="history">
          @php
            $historyTrans = [
              'search'          => __('general_content.search_trans_key'),
              'results'         => __('general_content.results_trans_key', [], null) ?? 'résultat(s)',
              'no_data'         => __('general_content.no_data_trans_key'),
              'quotes'          => __('general_content.quotes_list_trans_key'),
              'orders'          => __('general_content.orders_list_trans_key'),
              'purchases'       => __('general_content.purchase_list_trans_key'),
              'quote'           => __('general_content.quote_trans_key'),
              'order'           => __('general_content.order_trans_key'),
              'purchase'        => __('general_content.purchase_trans_key'),
              'sort'            => __('general_content.sort_trans_key'),
              'ref'             => __('general_content.id_trans_key'),
              'label'           => __('general_content.label_trans_key'),
              'qty'             => __('general_content.qty_trans_key'),
              'qty_receipt'     => __('general_content.qty_reciept_trans_key'),
              'unit'            => __('general_content.unit_trans_key'),
              'price'           => __('general_content.price_trans_key'),
              'discount'        => __('general_content.discount_trans_key'),
              'delivery_date'   => __('general_content.delivery_date_trans_key'),
              'status'          => __('general_content.status_trans_key'),
              'tasks_status'    => __('general_content.tasks_status_trans_key'),
              'delivery_status' => __('general_content.delivery_status_trans_key'),
              'invoice_status'  => __('general_content.invoice_status_trans_key'),
              'type_batch'      => __('batches.batch'),
              'batch_statu_1'   => __('batches.open'),
              'batch_statu_2'   => __('batches.closed'),
            ];
          @endphp
          @php $canPurchases = auth()->user()?->can('purchases-menu') ? 'true' : 'false'; @endphp
          <div
            id="product-history-app"
            data-endpoint="{{ route('products.json.history', ['id' => $Product->id]) }}"
            data-trans='@json($historyTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
            data-can-purchases="{{ $canPurchases }}"
          ></div>
        </div>
        @can('stock-lot-serial-management')
        <div class="tab-pane" id="serialNumber">
          @php
            $snTrans = [
              'serial_number'        => __('general_content.serial_numbers_trans_key'),
              'product'              => __('general_content.product_trans_key'),
              'order'                => __('general_content.order_trans_key'),
              'task'                 => __('general_content.task_trans_key'),
              'po_receipt'           => __('general_content.po_receipt_trans_key'),
              'status'               => __('general_content.statu_trans_key'),
              'created_at'           => __('general_content.created_at_trans_key'),
              'trace'                => __('Trace'),
              'view'                 => __('general_content.view_trans_key'),
              'search'               => __('general_content.search_trans_key'),
              'no_results'           => __('general_content.no_data_trans_key'),
              'total_serial_numbers' => __('general_content.serial_numbers_trans_key'),
              'undefined'            => __('general_content.undefined_trans_key'),
              'sold'                 => __('general_content.sold_trans_key'),
              'shipped'              => __('general_content.shipped_trans_key'),
              'returned'             => __('general_content.returned_trans_key'),
              'in_stock'             => __('general_content.in_stock_trans_key'),
            ];
          @endphp
          <div
            id="serial-numbers-embedded-app"
            data-product-id="{{ $Product->id }}"
            data-endpoints='@json(['list' => route('products.serialNumbers.json.list')], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
            data-trans='@json($snTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
          ></div>
        </div>
        @endcan
        {{-- Single document tab: one drop area for every format (PDF, STL, STEP,
             IGES, DXF, SVG, images...) and one viewer that adapts to the file. --}}
        <div class="tab-pane" id="Documents">
          @include('include.file-manager-mount', [
            'fileableType' => 'product',
            'fileableId'   => $Product->id,
            'fileManagerCanEdit' => $canEditProduct,
          ])
        </div>
        <div class="tab-pane" id="ControlPlans">
          @php
            // @json découpe son argument aux virgules : les expressions sont calculées ici.
            $controlPlanEndpoints = \App\Http\Controllers\Inspection\InspectionPlanController::endpoints();
            $controlPlanTrans = \App\Http\Controllers\Inspection\InspectionPlanController::trans();
            $controlPlanCanEdit = auth()->user()->can(\App\Http\Controllers\Inspection\InspectionPlanController::EDIT_PERMISSION);
          @endphp
          <div
            id="product-control-plans-app"
            data-product-id="{{ $Product->id }}"
            data-endpoints='@json($controlPlanEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
            data-trans='@json($controlPlanTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
            data-can-edit='@json($controlPlanCanEdit)'
          ></div>
        </div>
        <div class="tab-pane " id="Logs">
          @include('include.logs-viewer-mount', ['logsSubjectType' => 'App\Models\Products\Products', 'logsSubjectId' => $Product->id])
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
          <p class="small">{{ __('general_content.unit_trans_key') }}
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
          <p class="small">{{ __('general_content.price_trans_key') }}
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
          <p class="small">{{ __('general_content.purchased_price_trans_key') }}
            <b class="d-block">{{ $Product->purchased_price }} {{ $Factory->curency }}</b>
          </p>
          <p class="small">{{ __('general_content.weighted_average_price_trans_key') }}
            <b class="d-block"> {{ number_format($averageCost, 2) }} {{ $Factory->curency }}</b>
          </p>
          <p class="small">{{ __('general_content.last_purchase_price_price_trans_key') }}
            <b class="d-block">{{ number_format($lastPurchasePrice, 2) }} {{ $Factory->curency }}</b>
          </p>
          <p class="small">{{ __('general_content.average_supply_delay_trans_key') }}
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
          <p class="small">{{ __('general_content.quantite_eco_min_trans_key') }}
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
          <p class="small">{{ __('general_content.quantite_eco_max_trans_key') }}
            <b class="d-block">{{ $Product->qty_eco_max }}</b>
          </p>
          </div>
        </div>
      <!-- /.div row -->
      </div>
      @endif
    </x-adminlte-card>

    {{-- Uploads all happen in the Documents tab now; this card only previews the
         photo flagged as primary ($productPicture comes from the controller). --}}
    @if($productPicture || $Product->picture)
    <x-adminlte-card title="{{ __('general_content.picture_file_trans_key') }}" theme="success" collapsible="collapsed" maximizable>
        <img src="{{ $productPicture ? $productPicture->view_url : asset('/images/products/'. $Product->picture) }}" alt="{{ $Product->label }}" style="width: 100%;">
    </x-adminlte-card>
    @endif

    <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible="collapsed" maximizable>
      @if($canEditProduct)
      <p>
        <a href="{{ route('products.duplicate', $Product->id)}}" class="btn btn-sm btn-outline-secondary w-100 mb-2">
          <i class="fa fa-copy"></i> {{ __('general_content.duplicate_product_trans_key') }}
        </a>
      </p>
      @endif
      <p>
        @php echo '<img src="data:image/jpeg;base64,' . DNS1D::getBarcodePNG  (strval($Product->id), $Factory->task_barre_code, 4,60,array(1,1,1), true). '" alt="barcode"   />'; @endphp
      </p>
    </x-adminlte-card>

    <x-adminlte-card title="{{ __('ABC/FMR') }}" theme="danger" collapsible="collapsed" maximizable>
      <div >
        <div class="row justify-content-center">
              <div class="col-3 mb-1 me-1 bg-warning text-center" id="AR" ><h1>AR</h1></div>
              <div class="col-3 mb-1 me-1 bg-success text-center" id="AM" ><h1>AM</h1></div>
              <div class="col-3 mb-1 bg-success text-center" id="AF" ><h1>AF</h1></div>
          </div>
          <div class="row justify-content-center">
              <div class="col-3 mb-1 me-1 bg-warning text-center" id="BR" ><h1>BR</h1></div>
              <div class="col-3 mb-1 me-1 bg-success text-center" id="BM" ><h1>BM</h1></div>
              <div class="col-3 mb-1 bg-success text-center" id="BF" ><h1>BF</h1></div>
        </div>
        <div class="row justify-content-center">
              <div class="col-3 mb-1 me-1 bg-danger text-center" id="CR" ><h1>CR</h1></div>
              <div class="col-3 mb-1 me-1 text-bg-violet text-center" id="CM" ><h1>CM</h1></div>
              <div class="col-3 mb-1 text-bg-violet text-center" id="CF" ><h1>CF</h1></div>
      </div>
      </div>
    </x-adminlte-card>
  </div>
</div>

@if($lastSale)
<form method="POST" id="selling-price-from-last-sale-form" action="{{ route('products.selling-price.from-last-sale', ['id' => $Product->id]) }}" class="d-none">
  @csrf
</form>
@endif

@if($showPricesTab)
{{-- Fenêtre unique d'ajout / modification d'un palier de la grille tarifaire (onglet « Tarifs »). --}}
<div class="modal fade" id="priceListModal" tabindex="-1" aria-labelledby="priceListModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="priceListForm"
      data-store-url="{{ route('products.customer-price-list.store', ['product' => $Product->id]) }}"
      data-update-url="{{ route('products.customer-price-list.update', ['product' => $Product->id, 'priceList' => '__ID__']) }}"
      data-title-add="{{ __('price_list.add') }}"
      data-title-edit="{{ __('price_list.edit') }}"
      @if($priceListReopen) data-reopen='@json($priceListReopen)' @endif
    >
      @csrf
      <input type="hidden" name="_method" value="POST">
      <input type="hidden" name="_price_list_id" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="priceListModalTitle">{{ __('price_list.add') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label class="form-label d-block">{{ __('price_list.scope') }}</label>
        <div class="btn-group mb-3" role="group">
          <input type="radio" class="btn-check" name="_scope" id="price_scope_all" value="all" checked>
          <label class="btn btn-outline-secondary" for="price_scope_all"><i class="fas fa-globe me-1"></i>{{ __('price_list.scope_all') }}</label>
          <input type="radio" class="btn-check" name="_scope" id="price_scope_type" value="type">
          <label class="btn btn-outline-secondary" for="price_scope_type"><i class="fas fa-users me-1"></i>{{ __('price_list.scope_type') }}</label>
          <input type="radio" class="btn-check" name="_scope" id="price_scope_company" value="company">
          <label class="btn btn-outline-secondary" for="price_scope_company"><i class="fas fa-building me-1"></i>{{ __('price_list.scope_company') }}</label>
        </div>

        <div class="mb-3 d-none" data-scope-field="type">
          <label class="form-label" for="price_list_customer_type">{{ __('price_list.scope_type') }}</label>
          <select class="form-select" name="customer_type" id="price_list_customer_type">
            @foreach($customerTypeOptions as $option)
              @if($option['value'] !== '')
              <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
              @endif
            @endforeach
          </select>
        </div>

        <div class="mb-3 d-none" data-scope-field="company">
          <x-adminlte-select-bs name="companies_id" id="price_list_companies_id" label="{{ __('price_list.scope_company') }}">
            <option value="">—</option>
            @foreach($CustomerSelect as $customer)
            <option value="{{ $customer->id }}">{{ $customer->label }}</option>
            @endforeach
          </x-adminlte-select-bs>
        </div>

        <div class="row">
          <div class="mb-3 col-md-4">
            <label class="form-label" for="price_list_min_qty">{{ __('general_content.quantite_min_trans_key') }}</label>
            <input type="number" class="form-control" name="min_qty" id="price_list_min_qty" min="1" step="1" required>
          </div>
          <div class="mb-3 col-md-4">
            <label class="form-label" for="price_list_max_qty">{{ __('general_content.quantite_max_trans_key') }}</label>
            <input type="number" class="form-control" name="max_qty" id="price_list_max_qty" min="1" step="1" placeholder="∞">
            <small class="text-body-secondary">{{ __('price_list.max_qty_help') }}</small>
          </div>
          <div class="mb-3 col-md-4">
            <label class="form-label" for="price_list_price">{{ __('general_content.price_trans_key') }}</label>
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" name="price" id="price_list_price" min="0" required>
              <span class="input-group-text">{{ $Factory->curency }}</span>
            </div>
            <small class="text-body-secondary" id="price_list_gap"></small>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('general_content.cancel_trans_key') }}</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>{{ __('general_content.save_trans_key') }}</button>
      </div>
    </form>
  </div>
</div>
@endif

{{-- Modal historique des prix --}}
@php $canPurchasesPriceHist = auth()->user()?->can('purchases-menu') ? 'true' : 'false'; @endphp
<div class="modal fade" id="priceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="priceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="priceHistoryModalLabel">
                    <i class="fas fa-chart-line me-2"></i>Historique des prix - {{ $Product->label }}
                </h5>
                <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close float-end"></button>
            </div>
            <div class="modal-body">
                <div
                    id="product-price-history-app"
                    data-endpoint="{{ route('products.json.price-history', ['id' => $Product->id]) }}"
                    data-currency="{{ $Factory->curency }}"
                    data-can-purchases="{{ $canPurchasesPriceHist }}"
                ></div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
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

<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      bootstrap.Tooltip.getOrCreateInstance(el);
    });

    // Onglet désigné par l'ancre (#Prices après un ajout de palier…).
    if (location.hash) {
      var hashTab = document.querySelector('#productTab a[data-bs-toggle="tab"][href="' + CSS.escape(location.hash) + '"]');
      if (hashTab) bootstrap.Tab.getOrCreateInstance(hashTab).show();
    }

    initPriceListModal();

    // Ouvre l'historique des prix directement sur la section demandée par le bouton.
    var modal = document.getElementById('priceHistoryModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (e) {
      var focusType = e.relatedTarget ? e.relatedTarget.dataset.focusType : null;
      if (!focusType) return;

      modal.addEventListener('shown.bs.modal', function () {
        var target = modal.querySelector('[data-section="' + focusType + '"]');
        var body = modal.querySelector('.modal-body');
        if (target && body) {
          body.scrollTop += target.getBoundingClientRect().top - body.getBoundingClientRect().top;
        }
      }, { once: true });
    });
  });

  // Grille tarifaire : une seule fenêtre pour ajouter ou modifier un palier.
  function initPriceListModal() {
    var form = document.getElementById('priceListForm');
    if (!form) return;
    var modalEl = document.getElementById('priceListModal');
    var basePrice = {{ (float) $Product->selling_price }};
    var companySelect = form.querySelector('[name="companies_id"]');
    var typeSelect = form.querySelector('[name="customer_type"]');
    var priceInput = form.querySelector('[name="price"]');
    var gapEl = document.getElementById('price_list_gap');

    function setCompany(value) {
      if (companySelect.tomselect) {
        companySelect.tomselect.setValue(value || '', true);
      } else {
        companySelect.value = value || '';
      }
    }

    function applyScope(scope) {
      form.querySelectorAll('[data-scope-field]').forEach(function (el) {
        el.classList.toggle('d-none', el.dataset.scopeField !== scope);
      });
    }

    function showGap() {
      var price = parseFloat(priceInput.value);
      if (!(basePrice > 0) || isNaN(price)) { gapEl.textContent = ''; return; }
      var gap = (price - basePrice) / basePrice * 100;
      gapEl.textContent = (gap > 0 ? '+' : '') + gap.toFixed(1).replace('.', ',') + ' %';
    }

    function open(data) {
      var editing = !!data.id;
      form.action = editing ? form.dataset.updateUrl.replace('__ID__', data.id) : form.dataset.storeUrl;
      form.querySelector('[name="_method"]').value = editing ? 'PUT' : 'POST';
      form.querySelector('[name="_price_list_id"]').value = data.id || '';
      document.getElementById('priceListModalTitle').textContent = editing ? form.dataset.titleEdit : form.dataset.titleAdd;

      // Un client prime sur le type de client, comme dans le calcul du prix des lignes.
      var scope = data.companiesId ? 'company' : (data.customerType ? 'type' : 'all');
      form.querySelector('[name="_scope"][value="' + scope + '"]').checked = true;
      applyScope(scope);
      setCompany(data.companiesId);
      if (data.customerType) typeSelect.value = data.customerType;
      form.querySelector('[name="min_qty"]').value = data.minQty || '';
      form.querySelector('[name="max_qty"]').value = data.maxQty || '';
      priceInput.value = data.price || '';
      showGap();
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.querySelectorAll('[data-price-list-open]').forEach(function (btn) {
      btn.addEventListener('click', function () { open(btn.dataset); });
    });
    form.querySelectorAll('[name="_scope"]').forEach(function (radio) {
      radio.addEventListener('change', function () { applyScope(radio.value); });
    });
    priceInput.addEventListener('input', showGap);

    // Seul le champ de la portée choisie part : les autres sont vidés à l'envoi.
    form.addEventListener('submit', function (e) {
      var scope = form.querySelector('[name="_scope"]:checked').value;
      if (scope === 'company' && !companySelect.value) {
        e.preventDefault();
        (companySelect.tomselect ? companySelect.tomselect : companySelect).focus();
        return;
      }
      if (scope !== 'company') setCompany('');
      typeSelect.disabled = scope !== 'type';
    });
    modalEl.addEventListener('hidden.bs.modal', function () { typeSelect.disabled = false; });

    if (form.dataset.reopen) open(JSON.parse(form.dataset.reopen));
  }
</script>
@stop
