
<div>
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
                        <th>{{ __('general_content.rfq_group_trans_key') }}</th>
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
                    @php
                        $previousGroup = null;
                    @endphp
                    @forelse ($PurchasesQuotationList as $PurchaseQuotation)
                    @php
                        $currentGroup = $PurchaseQuotation->rfq_group_id;
                    @endphp
                    @if ($currentGroup && $currentGroup !== $previousGroup)
                        <tr class="table-active">
                            <td colspan="8">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div>
                                        <strong>{{ __('general_content.rfq_group_trans_key') }}:</strong>
                                        {{ $PurchaseQuotation->rfqGroup?->label ?? $PurchaseQuotation->rfqGroup?->code }}
                                        @if($PurchaseQuotation->rfqGroup?->code)
                                            <span class="text-muted">({{ $PurchaseQuotation->rfqGroup->code }})</span>
                                        @endif
                                    </div>
                                    @if($PurchaseQuotation->rfq_group_id)
                                        <a class="btn btn-outline-primary btn-sm" href="{{ route('purchases.quotations.compare', ['group' => $PurchaseQuotation->rfq_group_id]) }}">
                                            <i class="fas fa-balance-scale"></i> {{ __('general_content.compare_rfq_trans_key') }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                    <tr @if($currentGroup) style="border-left: 4px solid #6c757d;" @endif>
                        <td>{{ $PurchaseQuotation->code }}</td>
                        <td>{{ $PurchaseQuotation->label }}</td>
                        <td>
                            @if($PurchaseQuotation->rfqGroup)
                                <span class="badge badge-light">{{ $PurchaseQuotation->rfqGroup->code }}</span>
                                <div class="text-muted small">{{ $PurchaseQuotation->rfqGroup->label }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
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
                    @php
                        $previousGroup = $currentGroup;
                    @endphp
                    @empty
                        <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{ __('general_content.rfq_group_trans_key') }}</th>
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
