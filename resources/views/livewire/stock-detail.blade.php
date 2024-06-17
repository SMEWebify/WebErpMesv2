<div>
    <div class="card-body" >
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
            </div>
            <input type="text" class="form-control" wire:model.live="search" placeholder="{{ __('general_content.search_task_trans_key') }}">
        </div>
    </div>
    
    @include('include.alert-result')

    @empty($StockDetails)
    <h1>No Stock</h1> 
    @else
    <x-adminlte-card title="Stock - {{ $StockDetails->StockLocationProducts->product->code }}" theme="primary" maximizable>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{__('general_content.date_time_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.task_trans_key') }}</th>
                    <th>{{__('general_content.po_receipt_trans_key') }}</th>
                    <th>{{ __('general_content.type_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $StockDetails->UserManagement['name'] }}</td>
                    <td>{{ $StockDetails->GetPrettyCreatedAttribute() }}</td>
                    <td>{{ $StockDetails->qty }}</td>
                    <td>
                        @if($StockDetails->order_line_id)
                        <x-OrderButton id="{{ $StockDetails->OrderLine->order['id'] }}" code="{{ $StockDetails->OrderLine->order['code'] }}"  />
                        @endif
                        </td>
                        <td>
                        @if($StockDetails->task_id)
                        <a href="{{ route('production.task.statu.id', ['id' => $StockDetails->task_id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                        @endif
                        </td>
                        <td>
                        @if($StockDetails->purchase_receipt_line_id)
                        <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipts.show', ['id' => $StockDetails->purchaseReceiptLines->purchase_receipt_id])}}">
                            <i class="fas fa-folder"></i>
                            {{ $StockDetails->purchaseReceiptLines->purchaseReceipt->code }}
                        </a>
                        @endif
                    </td>
                    <td>
                        @if(1 == $StockDetails->typ_move ){{__('general_content.inventories_trans_key') }} @endif
                        @if(2 == $StockDetails->typ_move ){{__('general_content.task_allocation_trans_key') }} @endif
                        @if(3 == $StockDetails->typ_move ){{__('general_content.purchase_order_reception_trans_key') }} @endif
                        @if(4 == $StockDetails->typ_move ){{__('general_content.inter_stock_mvts_trans_key') }} @endif
                        @if(5 == $StockDetails->typ_move ){{__('general_content.manual_stock_recep_trans_key') }} @endif
                        @if(6 == $StockDetails->typ_move ){{__('general_content.manual_stock_dispatching_trans_key') }} @endif
                        @if(7 == $StockDetails->typ_move ){{__('general_content.reservation_trans_key') }} @endif
                        @if(8 == $StockDetails->typ_move ){{__('general_content.reservation_cancellation_trans_key') }} @endif
                        @if(9 == $StockDetails->typ_move ){{__('general_content.part_delivery_trans_key') }} @endif
                        @if(10 == $StockDetails->typ_move ){{__('general_content.in_production_trans_key') }} @endif
                        @if(11 == $StockDetails->typ_move ){{__('general_content.reservation_component_production_trans_key') }} @endif
                        @if(12 == $StockDetails->typ_move ){{__('general_content.manufactured_component_entry_trans_key') }} @endif
                        @if(13 == $StockDetails->typ_move ){{__('general_content.direct_inventory_trans_key') }} @endif
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </x-adminlte-card>

    <x-adminlte-card title="Stock - {{ __('general_content.view_details_trans_key') }}" theme="warning" maximizable>
        <form method="POST" action="{{ route('products.stock.detail.update', ['id' => $StockDetails->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="x_size">X</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control" value="{{ $StockDetails->x_size }}" name="x_size" id="x_size"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="y_size">Y</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control" value="{{ $StockDetails->y_size }}"  name="y_size" id="y_size"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="z_size">Z</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control" value="{{ $StockDetails->z_size }}" name="z_size" id="z_size"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="surface_perc">M²</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="number" class="form-control" value="{{ $StockDetails->surface_perc }}"  name="surface_perc" id="surface_perc"  placeholder="M²" step=".001">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label for="tracability">{{ __('general_content.tracability_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                        </div>
                        <input type="text" class="form-control" value="{{ $StockDetails->tracability }}" name="tracability" id="tracability"  placeholder="{{ __('general_content.tracability_trans_key') }}" step=".001">
                    </div>
                </div>
                <div class="form-group col-md-4">
                </div>
            </div>

            <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
            </div>
        </form>
    </x-adminlte-card>


    <x-adminlte-card title="{{ __('BARECODE') }}" theme="secondary" maximizable>
        @php echo '<img src="data:image/jpeg;base64,' . DNS1D::getBarcodePNG  (strval($StockDetails->id), $Factory->task_barre_code,4,60,array(1,1,1), true) . '" alt="barcode"   />'; @endphp
    </x-adminlte-card>

    <div class="row">
        <div class="col-md-12">
            <x-adminlte-card title="{{ __('general_content.photos_trans_key') }}" theme="info" maximizable>
                <form action="{{ route('photo.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="input-group">
                        <div class="input-group-prepend">
                        
                        </div>
                        <div class="custom-file">
                            <input type="hidden" name="stock_move_id" value="{{ $StockDetails->id }}" >
                            <input type="file" name="file" accept="image/*" capture="camera" class="custom-file-input" id="chooseFile">
                            <label class="custom-file-label" for="chooseFile">{{ __('general_content.take_photo_trans_key') }}</label>
                        </div>
                        <div class="input-group-append">
                            <button type="submit" name="submit" class="btn btn-success">
                                {{ __('general_content.upload_trans_key') }} 
                            </button>
                        </div>
                    </div>
                </form>
            </x-adminlte-card>
        </div>
    </div>
    <div class="row">
        @foreach($StockDetails->photos as $photo)
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="{{ asset('photo/' . $photo->name) }}" class="card-img-top" alt="Photo" width="100">
                    <div class="card-body">
                        <p class="card-text">{{ $photo->original_file_name }}</p>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    @endempty
</div>
