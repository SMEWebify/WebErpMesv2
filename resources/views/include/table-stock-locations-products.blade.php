    <div class="table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                <th>{{__('general_content.id_trans_key') }}</th>
                <th>{{ __('general_content.user_management_trans_key') }}</th>
                <th>{{ __('general_content.product_trans_key') }}</th>
                <th></th>
                <th>{{ __('general_content.qty_trans_key') }}</th>
                <!--<th>Qty reserve</th>-->
                <th>{{ __('general_content.qty_mini_trans_key') }}</th>
                <th>{{ __('general_content.end_date_trans_key') }}</th>
                <th>{{ __('general_content.addressing_trans_key') }}</th>
                <th>{{__('general_content.action_trans_key') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($StockLocationsProducts as $StockLocationsProduct)
            
                <tr>
                <td>{{ $StockLocationsProduct->code }}</td>
                <td>{{ $StockLocationsProduct->UserManagement['name'] }}</td>
                <td>{{ $StockLocationsProduct->Product['label'] }}</td>
                <td><x-ButtonTextView route="{{ route('products.show', ['id' => $StockLocationsProduct->Product['id']]) }}" /></td>
                    @if($StockLocationsProduct->getCurrentStockMove() > $StockLocationsProduct->mini_qty)
                    <td class="bg-success color-palette">
                    @elseif($StockLocationsProduct->getCurrentStockMove() < $StockLocationsProduct->mini_qty)
                    <td class="bg-danger color-palette">
                    @elseif($StockLocationsProduct->getCurrentStockMove() == $StockLocationsProduct->mini_qty)
                    <td class="bg-warning color-palette">
                    @endif
                    {{ $StockLocationsProduct->getCurrentStockMove() }}
                </td>
                <!--<td>{{ $StockLocationsProduct->reserve_qty }}</td>-->
                <td>{{ $StockLocationsProduct->mini_qty }}</td>
                <td>{{ $StockLocationsProduct->end_date }}</td>
                <td>{{ $StockLocationsProduct->addressing }}</td>
                <td class=" py-0 align-middle">
                    <div class="btn-group btn-group-sm">
                    <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                    </div>
                    <!-- Button Modal -->
                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#StockLocationsProduct{{ $StockLocationsProduct->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                    </button>
                    <!-- Modal {{ $StockLocationsProduct->id }} -->
                    <x-adminlte-modal id="StockLocationsProduct{{ $StockLocationsProduct->id }}" title="Update {{ $StockLocationsProduct->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('products.stockline.update', ['id' => $StockLocationsProduct->id]) }}" >
                        @csrf
                        <div class="form-group">
                            <label for="service_id">{{ __('general_content.user_management_trans_key') }}</label>
                            <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="user_id" id="user_id">
                                @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}" @if($StockLocationsProduct->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                @endforeach
                            </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="mini_qty">{{ __('general_content.qty_mini_trans_key') }} :</label>
                            <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                            <input type="number" class="form-control" name="mini_qty" id="mini_qty" placeholder="{{ __('general_content.qty_mini_trans_key') }} ex: 1" step="1" value="{{ $StockLocationsProduct->mini_qty }}">
                            <input type="hidden" name="stock_locations_id"  id="stock_locations_id"  value="{{ $StockLocationsProduct->stock_locations_id }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                            <input type="date" class="form-control" name="end_date"  id="end_date" value="{{ $StockLocationsProduct->end_date }}">
                        </div>
                        <div class="form-group">
                            <label for="addressing">{{ __('general_content.addressing_trans_key') }}</label>
                            <input type="text" class="form-control" name="addressing" id="addressing" placeholder="{{ __('general_content.addressing_trans_key') }}" value="{{ $StockLocationsProduct->addressing }}">
                        </div>
                        <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                        </div>
                    </form>
                    </x-adminlte-modal>
                </td>
                </tr>
                @empty
                <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                <th>{{__('general_content.id_trans_key') }}</th>
                <th>{{ __('general_content.user_management_trans_key') }}</th>
                <th>{{ __('general_content.product_trans_key') }}</th>
                <th></th>
                <th>{{ __('general_content.qty_trans_key') }}</th>
                <!--<th>Qty reserve</th>-->
                <th>{{ __('general_content.qty_mini_trans_key') }}</th>
                <th>{{ __('general_content.end_date_trans_key') }}</th>
                <th>{{ __('general_content.addressing_trans_key') }}</th>
                <th>{{__('general_content.action_trans_key') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>