<div>
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalProduct" tabindex="-1" role="dialog" aria-labelledby="ModalProductTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="ModalProductTitle">{{ __('general_content.new_product_trans_key') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        @csrf
                        <div class="card card-body">
                            @include('include.alert-result')
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model.live="code" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                                    </div>
                                    @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="label">{{ __('general_content.description_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model.live="label" name="label"  id="label" placeholder="{{ __('general_content.description_trans_key') }}">
                                    </div>
                                    @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="ind">{{ __('general_content.index_trans_key') }}</label>
                                    <input type="text" class="form-control"  wire:model.live="ind" name="ind"  id="ind" placeholder="{{ __('general_content.index_trans_key') }}">
                                    @error('ind') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="methods_services_id" name="methods_services_id" id="methods_services_id">
                                            <option value="">{{ __('general_content.select_service_trans_key') }}</option>
                                            @forelse ($ServicesSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->label }}</option>
                                            @empty
                                                <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    @error('methods_services_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="methods_families_id">{{ __('general_content.select_family_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-grip-horizontal"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="methods_families_id" name="methods_families_id" id="methods_families_id">
                                            <option value="">{{ __('general_content.family_trans_key') }}</option>
                                            @forelse ($FamiliesSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->label }}</option>
                                            @empty
                                                <option value="">{{ __('general_content.no_family_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    @error('methods_families_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="methods_units_id">{{ __('general_content.unit_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="methods_units_id" name="methods_units_id" id="methods_units_id">
                                            <option value="">{{ __('general_content.select_unit_trans_key') }}</option>
                                            @forelse ($UnitsSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->label }}</option>
                                            @empty
                                                <option value="">{{ __('general_content.no_unit_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    @error('methods_units_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="purchased">{{ __('general_content.purchased_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="purchased" name="purchased" id="purchased">
                                            <option value="">{{ __('general_content.select_statu_trans_key') }}</option>
                                            <option value="2">{{ __('general_content.no_trans_key') }}</option>
                                            <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                                        </select>
                                    </div>
                                    @error('purchased') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="sold">{{ __('general_content.sold_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="sold" name="sold" id="sold">
                                            <option value="">{{ __('general_content.select_statu_trans_key') }}</option>
                                            <option value="2">{{ __('general_content.no_trans_key') }}</option>
                                            <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                                        </select>
                                    </div>
                                    @error('sold') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="tracability_type">{{ __('general_content.tracability_trans_key') }}</label>
                                    <select class="form-control" wire:model.live="tracability_type" name="tracability_type" id="tracability_type">
                                        <option value="">{{ __('general_content.select_type_trans_key') }}</option>
                                        <option value="1">{{ __('general_content.no_traceability_trans_key') }}</option>
                                        <option value="2">{{ __('general_content.with_batch_number_trans_key') }}</option>
                                        <option value="3">{{ __('general_content.with_serial_number_trans_key') }}</option>
                                    </select>
                                    @error('tracability_type') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="purchased_price" name="purchased_price" id="purchased_price" min="0" placeholder="{{ __('general_content.purchased_price_trans_key') }}" step=".001">
                                    </div>
                                    @error('purchased_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="selling_price" name="selling_price" id="selling_price" min="0" placeholder="{{ __('general_content.price_trans_key') }}" step=".001">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="material">{{ __('general_content.proprieties_trans_key') }}</label>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model.live="material" name="material" id="material"  placeholder="{{ __('general_content.material_trans_key') }}">
                                    </div>
                                    @error('material') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="thickness" name="thickness" id="thickness" min="0"  placeholder="{{ __('general_content.thickness_trans_key') }}" step=".001">
                                    </div>
                                    @error('thickness') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="weight" name="weight" id="weight" min="0"  placeholder="{{ __('general_content.weight_trans_key') }}" step=".001">
                                    </div>
                                    @error('weight') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model.live="finishing" name="finishing" id="finishing"  placeholder="{{ __('general_content.finishing_trans_key') }}">
                                    </div>
                                </div>
                                <div class="col-4">
                                </div>
                                <div class="col-4">
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
                                        <input type="number" class="form-control" wire:model.live="x_size" name="x_size" id="x_size" min="0"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
                                    </div>
                                    @error('x_size') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="y_size">Y</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="y_size" name="y_size" id="y_size" min="0"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
                                    </div>
                                    @error('y_size') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="z_size">Z</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="z_size" name="z_size" id="z_size" min="0"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
                                    </div>
                                    @error('z_size') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="x_oversize" name="x_oversize" id="x_oversize" min="0"   placeholder="{{ __('general_content.x_oversize_trans_key') }}" step=".001">
                                    </div>
                                    @error('x_oversize') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="y_oversize" name="y_oversize" id="y_oversize" min="0"  placeholder="{{ __('general_content.y_oversize_trans_key') }}" step=".001">
                                    </div>
                                    @error('diameter') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                        </div>
                                        <input type="number" class="form-control" wire:model.live="z_oversize" name="z_oversize" id="z_oversize" min="0"   placeholder="{{ __('general_content.z_oversize_trans_key') }}" step=".001">
                                    </div>
                                    @error('diameter') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <input type="number" class="form-control" wire:model.live="diameter" name="diameter" id="diameter" min="0"  placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                                    @error('diameter') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <input type="number" class="form-control" wire:model.live="diameter_oversize" name="diameter_oversize" id="diameter_oversize" min="0"  placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                                    @error('diameter_oversize') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <input type="number" class="form-control" wire:model.live="section_size" name="section_size" id="section_size" min="0" placeholder="{{ __('general_content.section_size_trans_key') }}" step=".001">
                                    @error('section_size') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="qty_eco_min">{{ __('general_content.other_information_trans_key') }}</label>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <input type="number" class="form-control" wire:model.live="qty_eco_min" name="qty_eco_min" id="qty_eco_min" min="0" placeholder="{{ __('general_content.quantite_eco_min_trans_key') }}" step=".001">
                                    @error('qty_eco_min') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <input type="number" class="form-control" wire:model.live="qty_eco_max" name="qty_eco_max" id="qty_eco_max" min="0" placeholder="{{ __('general_content.quantite_eco_max_trans_key') }}" step=".001">
                                    @error('qty_eco_max') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <textarea class="form-control" rows="3"  wire:model.live="comment" name="comment"  placeholder="..."></textarea>
                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                            <button type="Submit" wire:click.prevent="storeProduct()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-10">
                    @include('include.search-card')
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalProduct">
                        {{ __('general_content.new_product_trans_key') }}
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>{{__('general_content.created_at_trans_key') }}</th>
                            <th>{{ __('general_content.sold_trans_key') }}</th>
                            <th>{{__('general_content.purchase_trans_key') }}</th>
                            <th></th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Products as $Product)
                        <tr>
                            <td>{{ $Product->code }}</td>
                            <td>{{ $Product->label }}</td>
                            <td>{{ $Product->GetPrettyCreatedAttribute() }}</td>
                            <td>
                                @if($Product->sold == 1 )
                                <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                                @else
                                <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                                @endif
                            </td>
                            <td>
                                @if($Product->purchased == 1 )
                                <span class="badge badge-success"><i class="fa fa-lg fa-fw  fa-check"></i></span>
                                @else
                                <span class="badge badge-danger"><i class="fa fa-lg fa-fw  fa-times"></i></span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $Product->getAllTaskCountAttribute() }}</span>
                                </div>
                            </td>
                            <td>
                                <x-ButtonTextView route="{{ route('products.show', ['id' => $Product->id])}}" />
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="6" text=" {{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{__('general_content.id_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.created_at_trans_key') }}</th>
                            <th>{{ __('general_content.sold_trans_key') }}</th>
                            <th>{{__('general_content.purchase_trans_key') }}</th>
                            <th></th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.row -->
            {{ $Products->links() }}
            <!-- /.card-body -->
        <!-- /.card-body -->
        </div>
    <!-- /.card -->
    </div>
<!-- /.div -->
</div>