<div>
    <div class="card">
        @include('include.alert-result')
        <div class="card-body">
            <div class="row">
                <div class="col-md-10">
                    @include('include.search-card')
                </div>
            </div>
            <div class="card-body ">
                <div class="row">
                    <div class="btn-group">
                        <button 
                                class="btn btn-info float-sm-right"
                                type="button"
                                wire:click="export('csv')"
                                wire:loading.attr="disabled"  >
                            CSV
                        </button> 
                    </div>
                    <div class="btn-group">
                        <button 
                                class="btn btn-danger float-sm-right"
                                type="button"
                                wire:click="export('xlsx')"
                                wire:loading.attr="disabled"  >
                            XLS
                        </button> 
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.invoice_trans_key') }}</th>
                            <th>{{__('general_content.customer_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.select_to_export_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($InvoiceExportLineslist as $InvoiceExportLines)
                        <tr>
                            <td>
                                <x-OrderButton id="{{ $InvoiceExportLines->orderLine->order['id'] }}" code="{{ $InvoiceExportLines->orderLine->order['code'] }}"  />
                            </td>
                            <td>{{ $InvoiceExportLines->invoice['code'] }}</td>
                            <td>
                                <x-CompanieButton id="{{ $InvoiceExportLines->invoice->companies_id }}" label="{{ $InvoiceExportLines->invoice->companie['label'] }}"  />
                                </td>
                            <td>{{ $InvoiceExportLines->orderLine['code'] }}</td>
                            <td>{{ $InvoiceExportLines->orderLine['label'] }}</td>
                            <td>{{ $InvoiceExportLines->qty }}</td>
                            <td>{{ $InvoiceExportLines->OrderLine->Unit['label'] }}</td>
                            <td>{{ $InvoiceExportLines->OrderLine['selling_price'] }} {{ $Factory->curency }}</td>
                            <td>{{ $InvoiceExportLines->OrderLine['discount'] }} %</td>
                            <td>{{ $InvoiceExportLines->OrderLine->VAT['rate'] }} %</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" wire:model.live="selectedInvoiceLine.{{ $InvoiceExportLines->id }}" id="{{ $InvoiceExportLines->id }}.invoice_line_id"  type="checkbox">
                                    <label for="{{ $InvoiceExportLines->id }}.invoice_line_id" class="custom-control-label">{{ __('general_content.add_export_trans_key') }}</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.invoice_trans_key') }}</th>
                            <th>{{__('general_content.customer_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.select_to_export_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<!-- /.card-body -->
</div>