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
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')"   role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                        </th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }} @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($Deliveryslist as $Delivery)
                    <tr>
                        <td>{{ $Delivery->code }}</td>
                        <td>{{ $Delivery->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $Delivery->companies_id }}" label="{{ $Delivery->companie['label'] }}"  />
                        </td>
                        <td>{{ $Delivery->delivery_lines_count }}</td>
                        <td>
                            @if(1 == $Delivery->statu )  <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                            @if(2 == $Delivery->statu )  <span class="badge badge-success">{{ __('general_content.send_trans_key') }}</span>@endif
                        </td>
                        <td>
                            @if(1 == $Delivery->invoice_status )  <span class="badge badge-info">{{ __('general_content.chargeable_trans_key') }}</span>@endif
                            @if(2 == $Delivery->invoice_status )  <span class="badge badge-danger">{{ __('general_content.not_chargeable_trans_key') }}</span>@endif
                            @if(3 == $Delivery->invoice_status )  <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>@endif
                            @if(4 == $Delivery->invoice_status )  <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>@endif
                        </td>
                        <td><img src="{{ Avatar::create($Delivery->UserManagement['name'])->toBase64() }}" /></td>
                        <td>{{ $Delivery->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('deliverys.show', ['id' => $Delivery->id])}}" />
                            <x-ButtonTextPDF route="{{ route('pdf.delivery', ['Document' => $Delivery->id])}}" />
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $Deliveryslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>