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
                                <option value="1">{{ __('general_content.pending_trans_key') }}</option>
                                <option value="2">{{ __('general_content.approved_trans_key') }}</option>
                                <option value="3">{{ __('general_content.rejected_trans_key') }}</option>
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
                    @forelse ($CreditNotesList as $CreditNotes)
                    <tr>
                        <td>{{ $CreditNotes->code }}</td>
                        <td>{{ $CreditNotes->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $CreditNotes->companies_id }}" label="{{ $CreditNotes->companie['label'] }}"  />
                        </td>
                        <td>{{ $CreditNotes->CreditNotes_lines_count }}</td>
                        <td>{{ $CreditNotes->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                        <td>
                            @if(1 == $CreditNotes->statu )  <span class="badge badge-danger">{{ __('general_content.pending_trans_key') }}</span>@endif
                            @if(2 == $CreditNotes->statu )  <span class="badge badge-success">{{ __('general_content.approved_trans_key') }}</span>@endif
                            @if(3 == $CreditNotes->statu )  <span class="badge badge-warning">{{ __('general_content.rejected_trans_key') }}</span>@endif
                        </td>
                        <td><img src="{{ Avatar::create($CreditNotes->UserManagement['name'])->toBase64() }}" /></td>
                        <td>{{ $CreditNotes->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('credit.notes.show', ['id' => $CreditNotes->id])}}" />
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
        {{ $CreditNotesList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>