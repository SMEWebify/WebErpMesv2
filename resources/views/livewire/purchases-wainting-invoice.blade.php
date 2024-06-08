<div >
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
                            <button type="Submit" wire:click.prevent="storeInvoice()" class="btn btn-success btn-block">{{ __('general_content.new_invoice_document_trans_key') }}</button>
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
                        <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                        <th>{{ __('general_content.purchase_receipt_trans_key') }}</th>
                        <th>{{ __('general_content.supplier_trans_key') }}</th>
                        <th>{{ __('general_content.description_trans_key') }}</th>
                        <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($PurchasesWaintingInvoiceLineslist as $PurchasesWaintingInvoiceLine)
                    <tr>
                        <td>
                            @if($PurchasesWaintingInvoiceLine->purchaseLines->tasks->OrderLines ?? null)
                                <x-OrderButton id="{{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->OrderLines->orders_id }}" code="{{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->OrderLines->order->code }}"  />
                            @else
                                {{__('general_content.generic_trans_key') }} 
                            @endif
                            </td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('purchases.show', ['id' => $PurchasesWaintingInvoiceLine->purchaseLines->purchase->id])}}">
                                <i class="fas fa-folder"></i>
                                {{ $PurchasesWaintingInvoiceLine->purchaseLines->purchase->code }}
                            </a>
                        </td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipts.show', ['id' => $PurchasesWaintingInvoiceLine->purchase_receipt_id])}}">
                                <i class="fas fa-folder"></i>
                                {{ $PurchasesWaintingInvoiceLine->purchaseReceipt->code }}
                            </a>
                        </td>
                        <td>
                            {{ $PurchasesWaintingInvoiceLine->purchaseReceipt->companie->code }} - {{ $PurchasesWaintingInvoiceLine->purchaseReceipt->companie->label }}
                        </td>
                        <td>
                            @if($PurchasesWaintingInvoiceLine->purchaseLines->tasks_id ?? null)
                                <a href="{{ route('production.task.statu.id', ['id' => $PurchasesWaintingInvoiceLine->purchaseLines->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                #{{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->id }} {{ $PurchasesWaintingInvoiceLine->purchaseLines->code }} {{ $PurchasesWaintingInvoiceLine->purchaseLines->label }}
                                @if($PurchasesWaintingInvoiceLine->purchaseLines->tasks->component_id )
                                    - {{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->Component['label'] }}
                                @endif
                            @else
                                {{ $PurchasesWaintingInvoiceLine->purchaseLines->label }}
                            @endif
                        </td>
                        <td>{{ $PurchasesWaintingInvoiceLine->receipt_qty }}</td>
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" value="{{ $PurchasesWaintingInvoiceLine->id }}" wire:model.live="data.{{ $PurchasesWaintingInvoiceLine->id }}.purchase_receipt_line_id" id="data.{{ $PurchasesWaintingInvoiceLine->id }}.purchase_receipt_line_id"  type="checkbox">
                                <label for="data.{{ $PurchasesWaintingInvoiceLine->id }}.purchase_receipt_line_id" class="custom-control-label">{{ __('general_content.add_to_document_trans_key') }} </label>
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
                        <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                        <th>{{ __('general_content.purchase_receipt_trans_key') }}</th>
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
<!-- /.card-body -->
</div>