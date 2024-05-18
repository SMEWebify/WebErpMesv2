<div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    @include('include.search-card')
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="searchIdStatus" id="searchIdStatus" wire:model.live="searchIdStatus">
                                <option value="" selected>{{ __('general_content.select_statu_trans_key') }}</option>
                                <option value="1">{{ __('general_content.in_progress_trans_key') }}</option>
                                <option value="2">{{ __('general_content.send_trans_key') }}</option>
                                <option value="3">{{ __('general_content.pending_trans_key') }}</option>
                                <option value="4">{{ __('general_content.unpaid_trans_key') }}</option>
                                <option value="5">{{ __('general_content.paid_trans_key') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
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
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')" role="button" href="#">{{__('general_content.customer_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                        </th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.total_price_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }} @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($InvoicesList as $Invoice)
                    <tr>
                        <td>{{ $Invoice->code }}</td>
                        <td>{{ $Invoice->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $Invoice->companies_id }}" label="{{ $Invoice->companie['label'] }}"  />
                        </td>
                        <td>{{ $Invoice->invoice_lines_count }}</td>
                        <td>{{ $Invoice->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                        <td>
                            @if(1 == $Invoice->statu )  <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                            @if(2 == $Invoice->statu )  <span class="badge badge-primary">{{ __('general_content.send_trans_key') }}</span>@endif
                            @if(3 == $Invoice->statu )  <span class="badge badge-warning">{{ __('general_content.pending_trans_key') }}</span>@endif
                            @if(4 == $Invoice->statu )  <span class="badge badge-danger">{{ __('general_content.unpaid_trans_key') }}</span>@endif
                            @if(5 == $Invoice->statu )  <span class="badge badge-success">{{ __('general_content.paid_trans_key') }}</span>@endif
                        </td>
                        <td><img src="{{ Avatar::create($Invoice->UserManagement['name'])->toBase64() }}" /></td>
                        <td>{{ $Invoice->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('invoices.show', ['id' => $Invoice->id])}}" />
                            <x-ButtonTextPDF route="{{ route('pdf.invoice', ['Document' => $Invoice->id])}}" />
                            <a class="btn btn-warning btn-sm" href="{{ route('pdf.facturex', ['Document' => $Invoice->id])}}">
                                <i class="fas fa-file-pdf"></i>
                                Factur-X
                            </a>
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
                        <th>{{__('general_content.total_price_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $InvoicesList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>