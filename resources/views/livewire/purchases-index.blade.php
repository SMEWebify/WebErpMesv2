<div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                @include('include.search-card')
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="searchIdStatus" id="searchIdStatus" wire:model.live="searchIdStatus">
                                <option value="" selected>{{ __('general_content.select_statu_trans_key') }}</option>
                                <option value="1">{{ __('general_content.in_progress_trans_key') }}</option>
                                <option value="2">{{ __('general_content.ordered_trans_key') }}</option>
                                <option value="3">{{ __('general_content.partly_received_trans_key') }}</option>
                                <option value="4">{{ __('general_content.rceived_trans_key') }}</option>
                                <option value="5">{{ __('general_content.canceled_trans_key') }}</option>
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
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')"   role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                        </th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }} @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($PurchasesList as $Purchase)
                    <tr>
                        <td>{{ $Purchase->code }}</td>
                        <td>{{ $Purchase->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $Purchase->companies_id }}" label="{{ $Purchase->companie['label'] }}"  />
                        </td>
                        <td>{{ $Purchase->purchase_lines_count }}</td>
                        <td>
                            @if(1 == $Purchase->statu )  <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                            @if(2 == $Purchase->statu )  <span class="badge badge-warning">{{ __('general_content.ordered_trans_key') }}</span>@endif
                            @if(3 == $Purchase->statu )  <span class="badge badge-success">{{ __('general_content.partly_received_trans_key') }}</span>@endif
                            @if(4 == $Purchase->statu )  <span class="badge badge-danger">{{ __('general_content.rceived_trans_key') }}</span>@endif
                            @if(5 == $Purchase->statu )  <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span>@endif
                        </td>
                        <td><img src="{{ Avatar::create($Purchase->UserManagement['name'])->toBase64() }}" /></td>
                        <td>{{ $Purchase->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('purchases.show', ['id' => $Purchase->id])}}" />
                            @if( $Purchase->companies_contacts_id != 0 & $Purchase->companies_addresses_id !=0)
                            <x-ButtonTextPDF route="{{ route('pdf.purchase', ['Document' => $Purchase->id])}}" />
                            @endif
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{__('general_content.id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{__('general_content.lines_count_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $PurchasesList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>