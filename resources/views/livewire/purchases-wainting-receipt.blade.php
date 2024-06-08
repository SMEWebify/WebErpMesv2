<div>
    <div class="card">
        @include('include.alert-result')
            <div class="card-body">
                <form>
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="companies_id">{{ __('general_content.sort_companie_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                </div>
                                <select class="form-control" wire:model.live="companies_id" name="companies_id" id="companies_id">
                                    <option value="">{{ __('general_content.select_company_trans_key') }}</option>
                                @forelse ($CompaniesSelect as $item)
                                    <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                @empty
                                    <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                                @endforelse
                                </select>
                            </div>
                            @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                </div>
                                <input type="text" class="form-control" wire:model.live="code" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                            </div>
                            @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="code">{{ __('general_content.delivery_note_number_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                </div>
                                <input type="text" class="form-control" wire:model.live="deliveryNoteNumber" name="delivery_note_number" id="delivery_note_number" placeholder="{{ __('general_content.delivery_note_number_trans_key') }}">
                            </div>
                            @error('delivery_note_number') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" wire:model.live="user_id" name="user_id" id="user_id">
                                    <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                                @foreach ($userSelect as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                                </select>
                            </div>
                            @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="card-footer">
                            <div class="input-group">
                                <button type="Submit" wire:click.prevent="storeReciep()" class="btn btn-success btn-block">{{ __('general_content.new_receipt_document_trans_key') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                            <th>{{ __('general_content.supplier_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($PurchasesWaintingReceiptLineslist as $PurchasesWaintingReceiptLine)
                        <tr>
                            <td>
                                @if($PurchasesWaintingReceiptLine->tasks->OrderLines ?? null)
                                    <x-OrderButton id="{{ $PurchasesWaintingReceiptLine->tasks->OrderLines->orders_id }}" code="{{ $PurchasesWaintingReceiptLine->tasks->OrderLines->order->code }}"  /> 
                                @else
                                    {{__('general_content.generic_trans_key') }} 
                                @endif
                            </td>
                            <td>
                                @if($PurchasesWaintingReceiptLine->tasks->OrderLines ?? null)
                                    {{ $PurchasesWaintingReceiptLine->tasks->OrderLines->qty }} x 
                                @else
                                    {{__('general_content.generic_trans_key') }} 
                                @endif
                            </td>
                            <td>
                                @if($PurchasesWaintingReceiptLine->tasks->OrderLines ?? null)
                                    {{ $PurchasesWaintingReceiptLine->tasks->OrderLines->label }}
                                @else
                                    {{__('general_content.generic_trans_key') }} 
                                @endif
                            </td>
                            <td>
                                @if($PurchasesWaintingReceiptLine->tasks_id ?? null)
                                    <a href="{{ route('production.task.statu.id', ['id' => $PurchasesWaintingReceiptLine->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                    #{{ $PurchasesWaintingReceiptLine->tasks->id }} - {{ $PurchasesWaintingReceiptLine->tasks->label }}
                                    @if($PurchasesWaintingReceiptLine->tasks->component_id )
                                        - {{ $PurchasesWaintingReceiptLine->tasks->Component['label'] }}
                                    @endif
                                @else
                                    {{ $PurchasesWaintingReceiptLine->label }}
                                @endif
                            </td>
                            
                            <td>
                                @if($PurchasesWaintingReceiptLine->tasks_id ?? null)
                                    @if($PurchasesWaintingReceiptLine->tasks->component_id ) 
                                    <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchasesWaintingReceiptLine->tasks->component_id])}}" />
                                    @endif
                                @else
                                    @if($PurchasesWaintingReceiptLine->product_id ) 
                                        <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchasesWaintingReceiptLine->product_id])}}" />
                                    @endif
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('purchases.show', ['id' => $PurchasesWaintingReceiptLine->purchases_id])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $PurchasesWaintingReceiptLine->purchase->code }}
                                </a>
                            </td>
                            <td>
                                {{ $PurchasesWaintingReceiptLine->purchase->companie->code }} - {{ $PurchasesWaintingReceiptLine->purchase->companie->label }}
                            </td>
                            <td> {{ $PurchasesWaintingReceiptLine->code }} {{ $PurchasesWaintingReceiptLine->label }}</td>
                            <td>{{ $PurchasesWaintingReceiptLine->qty }}</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $PurchasesWaintingReceiptLine->id }}" wire:model.live="data.{{ $PurchasesWaintingReceiptLine->id }}.purchase_line_id" id="data.{{ $PurchasesWaintingReceiptLine->id }}.purchase_line_id"  type="checkbox">
                                    <label for="data.{{ $PurchasesWaintingReceiptLine->id }}.purchase_line_id" class="custom-control-label">{{ __('general_content.add_to_document_trans_key') }}</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="13" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                            <th>{{ __('general_content.supplier_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <!-- /.card -->
        </div>
    </form>
<!-- /.card-body -->
</div>