
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="purchase_quotation_id">{{ __('general_content.purchase_quotation_trans_key') }}</label>
                    <select id="purchase_quotation_id" class="form-control" wire:model.live="purchase_quotation_id">
                        <option value="">{{ __('general_content.select_trans_key') }}</option>
                        @foreach($PurchaseQuotationOptions as $quotationOption)
                            <option value="{{ $quotationOption->id }}">
                                {{ $quotationOption->code }} - {{ $quotationOption->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('purchase_quotation_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                </div>
            </div>

            @if($purchase_quotation_id)
                @if($OrderStatu == 1)
                    @if($updateLines)
                    <form wire:submit.prevent="updatePurchaseQuotationLine">
                        <input type="hidden" wire:model.live="purchase_quotation_line_id">
                    @else
                    <form wire:submit.prevent="storePurchaseQuotationLine">
                    @endif
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="ordre">{{ __('general_content.sort_trans_key') }} :</label>
                                <input type="number" class="form-control @error('ordre') is-invalid @enderror" id="ordre" min="0" wire:model.live="ordre">
                                @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="line_label">{{ __('general_content.description_trans_key') }}</label>
                                <input type="text" class="form-control @error('line_label') is-invalid @enderror" id="line_label" wire:model.live="line_label">
                                @error('line_label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-2">
                                <label for="qty_to_order">{{ __('general_content.qty_trans_key') }}</label>
                                <input type="number" class="form-control @error('qty_to_order') is-invalid @enderror" id="qty_to_order" min="0" wire:model.live="qty_to_order">
                                @error('qty_to_order') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-2">
                                <label for="unit_price">{{ __('general_content.price_trans_key') }}</label>
                                <input type="number" class="form-control @error('unit_price') is-invalid @enderror" id="unit_price" min="0" step="0.01" wire:model.live="unit_price">
                                @error('unit_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-block">{{ __('general_content.submit_trans_key') }}</button>
                            </div>
                        </div>
                    </form>
                @else
                <x-adminlte-alert theme="info" title="Info">
                    {{ __('general_content.info_statu_trans_key') }}
                </x-adminlte-alert>
                @endif
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
        </div>
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')"   role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                        </th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }} @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($PurchasesQuotationList as $PurchaseQuotation)
                    <tr>
                        <td>{{ $PurchaseQuotation->code }}</td>
                        <td>{{ $PurchaseQuotation->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $PurchaseQuotation->companies_id }}" label="{{ $PurchaseQuotation->companie['label'] }}"  />
                        </td>
                        <td>{{ $PurchaseQuotation->purchase_quotation_lines_count }}</td>
                        <td>
                            @if(1 == $PurchaseQuotation->statu )  <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                            @if(2 == $PurchaseQuotation->statu )  <span class="badge badge-primary">{{ __('general_content.send_trans_key') }}</span>@endif
                            @if(3 == $PurchaseQuotation->statu )  <span class="badge badge-secondary">{{ __('general_content.partly_received_trans_key') }}</span>@endif
                            @if(4 == $PurchaseQuotation->statu )  <span class="badge badge-info">{{ __('general_content.rceived_trans_key') }}</span>@endif
                            @if(5 == $PurchaseQuotation->statu )  <span class="badge badge-warning">{{ __('general_content.po_partly_created_trans_key') }}</span>@endif
                            @if(6 == $PurchaseQuotation->statu )  <span class="badge badge-success">{{ __('general_content.po_created_trans_key') }}</span>@endif
                        </td>
                        <td>{{ $PurchaseQuotation->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('purchases.quotations.show', ['id' => $PurchaseQuotation->id])}}" />
                            @if( $PurchaseQuotation->companies_contacts_id != 0 & $PurchaseQuotation->companies_addresses_id !=0)
                            <x-ButtonTextPDF route="{{ route('pdf.purchase.quotation', ['Document' => $PurchaseQuotation->id])}}" />
                            @endif
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $PurchasesQuotationList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>
