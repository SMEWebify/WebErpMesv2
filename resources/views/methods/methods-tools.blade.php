@extends('adminlte::page')

@section('title', __('general_content.tools_trans_key')) 

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0">{{ __('general_content.tools_trans_key') }}</h1>
        <a href="{{ route('purchases.reorder', ['scope' => 'tools']) }}" class="btn btn-sm btn-warning ms-auto me-2">
            <i class="fas fa-cart-plus me-1"></i>Réapprovisionner
        </a>
        <a href="{{ route('methods.tool.configurator') }}" class="btn btn-sm btn-primary me-2">
            <i class="fas fa-tools me-1"></i>{{ __('adminlte::menu.methods_tool_configurator_trans_key') }}
        </a>
        <a href="{{ route('methods.tool.punch-designer') }}" class="btn btn-sm btn-info">
            <i class="fas fa-drafting-compass me-1"></i>{{ __('adminlte::menu.methods_punch_designer_trans_key') }}
        </a>
    </div>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.tools_trans_key') }}" theme="secondary" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('general_content.picture_trans_key') }}</th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.etat_trans_key') }}</th>
                <th>{{ __('general_content.cost_trans_key') }}</th>
                <th>{{ __('general_content.end_date_trans_key') }}</th>
                <th>{{ __('general_content.qty_trans_key') }}</th>
                <th>Stock</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($MethodsTools as $MethodsTool)
              <tr>
                <td> 
                  @if($MethodsTool->picture )
                  <img alt="Tool" class="profile-user-img img-fluid rounded-circle" src="{{ asset('storage/images/tools/'. $MethodsTool->picture) }}">
                  @endif
                </td>
                <td>{{ $MethodsTool->code }}</td>
                <td>{{ $MethodsTool->label }}</td>
                <td>
                  @if($MethodsTool->ETAT  == 1) {{ __('general_content.used_trans_key') }} @endif
                  @if($MethodsTool->ETAT  == 2) {{ __('general_content.unsed_trans_key') }} @endif
                </td>
                <td>{{ $MethodsTool->cost }}</td>
                <td>{{ $MethodsTool->end_date }}</td>
                <td>{{ $MethodsTool->qty }}</td>
                <td class="text-nowrap">
                  @if($MethodsTool->stockProduct && isset($ToolStock[$MethodsTool->id]))
                    @php($ts = $ToolStock[$MethodsTool->id])
                    <a href="{{ route('products.show', ['id' => $MethodsTool->products_id]) }}" title="Article de stock {{ $MethodsTool->stockProduct->code }}">
                      <span class="badge text-bg-{{ $ts['below'] ? 'danger' : 'success' }}">{{ rtrim(rtrim(number_format($ts['stock'], 3, ',', ' '), '0'), ',') }}</span>
                    </a>
                    @if($ts['mini'] > 0)<small class="text-muted">/ mini {{ rtrim(rtrim(number_format($ts['mini'], 3, ',', ' '), '0'), ',') }}</small>@endif
                    <a href="{{ route('purchases.reorder', ['products' => [$MethodsTool->products_id]]) }}" class="btn btn-sm btn-outline-warning ms-1" title="Commander">
                      <i class="fas fa-cart-plus"></i>
                    </a>
                  @else
                    <small class="text-muted">—</small>
                  @endif
                </td>
                <td class="py-0 align-middle">
                  <!-- Button Modal -->
                  <x-ButtonTextEdit :modalTarget="'MethodsTool' . $MethodsTool->id" />
                  <!-- Modal {{ $MethodsTool->id }} -->
                  <x-adminlte-modal id="MethodsTool{{ $MethodsTool->id }}" title="Update {{ $MethodsTool->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.tool.update', ['id' => $MethodsTool->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="mb-3">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsTool->label }}">
                          </div>
                        </div>
                        <div class="mb-3">
                          <div class="col-4 text-start"><label for="etat_update{{ $MethodsTool->id }}" class="col-form-label">{{ __('general_content.etat_trans_key') }}</label></div>
                          <div class="col-8">
                              @if($MethodsTool->ETAT == 1)  
                              <x-adminlte-input-switch id="etat_update{{ $MethodsTool->id }}" name="etat_update" is-checked="true" />
                              @else
                              <x-adminlte-input-switch id="etat_update{{ $MethodsTool->id }}" name="etat_update" />
                              @endif
                          </div>
                        </div>
                        <div class="mb-3">
                          <label for="cost">{{ __('general_content.cost_trans_key') }}</label>
                          <div class="input-group">
                            <span class="input-group-text">{{ $Factory->curency }}</span>
                            <input type="number" class="form-control" name="cost"  id="cost" placeholder="{{ __('general_content.cost_trans_key') }}" step=".001" value="{{ $MethodsTool->cost }}">
                          </div>
                        </div>
                        <div class="mb-3">
                          <label for="qty">{{ __('general_content.qty_trans_key') }}</label>
                          <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-times"></i></span>
                            <input type="numer" class="form-control" name="qty"  id="qty" placeholder="{{__('general_content.qty_trans_key') }}" value="{{ $MethodsTool->qty }}">
                          </div>
                        </div>
                        <div class="mb-3">
                          <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                          <input type="date" class="form-control" name="end_date"  id="end_date" placeholder="{{__('general_content.qty_trans_key') }}" value="{{ $MethodsTool->end_date }}" >
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                    <div class="card-body">
                      <form action="{{ route('methods.tool.update.picture', ['id' => $MethodsTool->id]) }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label>(peg,png,jpg,gif,svg | max: 10 240 Ko)
                          <div class="input-group">
                              <span class="input-group-text"><i class="far fa-image"></i></span>
                              <input type="hidden" name="id" value="{{ $MethodsTool->id }}">
                              <input type="file" class="form-control" name="picture" id="picture">
                              <button type="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
                          </div>
                      </form>
                    </div>
                    <div class="card-body border-top">
                      <h5 class="mb-3"><i class="fas fa-boxes me-1"></i>Article de stock</h5>
                      @if($MethodsTool->stockProduct)
                        <p class="mb-2">
                          Lié à <a href="{{ route('products.show', ['id' => $MethodsTool->products_id]) }}">{{ $MethodsTool->stockProduct->code }} — {{ $MethodsTool->stockProduct->label }}</a>.
                          Le stock, le seuil mini et les prix fournisseurs se gèrent sur la fiche article.
                        </p>
                        <form method="POST" action="{{ route('methods.tool.stock-product', ['id' => $MethodsTool->id]) }}">
                          @csrf
                          <input type="hidden" name="mode" value="unlink">
                          <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-unlink me-1"></i>Délier l'article</button>
                        </form>
                      @else
                        <p class="text-muted small">Sans article, l'outil n'a pas de stock suivi. Créez-en un (consommable, outillage acheté) ou liez un article existant.</p>
                        <form method="POST" action="{{ route('methods.tool.stock-product', ['id' => $MethodsTool->id]) }}">
                          @csrf
                          <input type="hidden" name="mode" value="create">
                          <div class="row gx-2">
                            <div class="mb-3 col-md-4">
                              <label class="small">Service</label>
                              <select name="methods_services_id" class="form-select form-select-sm" required>
                                @foreach($StockServices as $item)<option value="{{ $item->id }}">{{ $item->label }}</option>@endforeach
                              </select>
                            </div>
                            <div class="mb-3 col-md-4">
                              <label class="small">Famille</label>
                              <select name="methods_families_id" class="form-select form-select-sm" required>
                                @foreach($StockFamilies as $item)<option value="{{ $item->id }}">{{ $item->label }}</option>@endforeach
                              </select>
                            </div>
                            <div class="mb-3 col-md-4">
                              <label class="small">Unité</label>
                              <select name="methods_units_id" class="form-select form-select-sm" required>
                                @foreach($StockUnits as $item)<option value="{{ $item->id }}">{{ $item->label }}</option>@endforeach
                              </select>
                            </div>
                            <div class="mb-3 col-md-4">
                              <label class="small">Emplacement</label>
                              <select name="stock_locations_id" class="form-select form-select-sm">
                                <option value="">— aucun —</option>
                                @foreach($StockLocations as $item)<option value="{{ $item->id }}">{{ $item->code }} — {{ $item->label }}</option>@endforeach
                              </select>
                            </div>
                            <div class="mb-3 col-md-4">
                              <label class="small">Seuil mini</label>
                              <input type="number" step="any" min="0" name="mini_qty" class="form-control form-control-sm" placeholder="0">
                            </div>
                            <div class="mb-3 col-md-4">
                              <label class="small">Qté éco. d'achat</label>
                              <input type="number" step="any" min="0" name="qty_eco_min" class="form-control form-control-sm" placeholder="ex. 10">
                            </div>
                          </div>
                          <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Créer l'article {{ $MethodsTool->code }}</button>
                        </form>
                        <form method="POST" action="{{ route('methods.tool.stock-product', ['id' => $MethodsTool->id]) }}" class="d-flex flex-wrap align-items-center gap-2 mt-3">
                          @csrf
                          <input type="hidden" name="mode" value="link">
                          <input type="text" name="product_code" class="form-control form-control-sm me-2 w-auto" placeholder="Code d'un article existant" required>
                          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-link me-1"></i>Lier</button>
                        </form>
                      @endif
                    </div>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{ __('general_content.picture_trans_key') }}</th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.etat_trans_key') }}</th>
                <th>{{ __('general_content.cost_trans_key') }}</th>
                <th>{{ __('general_content.end_date_trans_key') }}</th>
                <th>{{ __('general_content.qty_trans_key') }}</th>
                <th>Stock</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </x-adminlte-card>
    </div>
    <div class="col-md-6">
      <form  method="POST" action="{{ route('methods.tool.create') }}" class="form-horizontal" enctype="multipart/form-data">
        <x-adminlte-card title="{{ __('general_content.new_tool_trans_key') }}" theme="secondary" maximizable>
          @csrf
          <div class="mb-3">
            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="label">{{__('general_content.label_trans_key') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-tags"></i></span>
              <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
            </div>
          </div>
          <div class="mb-3">
              <label for="ETAT" class="col-form-label">{{ __('general_content.etat_trans_key') }}</label>
              <x-adminlte-input-switch name="ETAT" is-checked="true" />
          </div>
          <div class="mb-3">
            <label for="cost">{{ __('general_content.cost_trans_key') }}</label>
            <div class="input-group">
              <span class="input-group-text">{{ $Factory->curency }}</span>
              <input type="number" class="form-control" name="cost"  id="cost" placeholder="{{ __('general_content.cost_trans_key') }}" step=".001">
            </div>
          </div>
          <div class="mb-3">
            <label for="qty">{{ __('general_content.qty_trans_key') }}</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-times"></i></span>
              <input type="numer" class="form-control" name="qty"  id="qty" placeholder="{{__('general_content.qty_trans_key') }}" >
            </div>
          </div>
          <div class="mb-3">
            <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
            <input type="date" class="form-control" name="end_date"  id="end_date">
          </div>
          <div class="mb-3">
            <label for="picture">{{ __('general_content.picture_trans_key') }}</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
            <div class="input-group">
              <span class="input-group-text"><i class="far fa-image"></i></span>
              <input type="file" class="form-control" name="picture" id="picture">
          </div>
          <div class="card-footer">
            <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </div>
        </x-adminlte-card>
      </form>
    </div>
    <!-- /.card secondary -->
  </div>
@stop

@section('css')
@stop

@section('js')
@stop