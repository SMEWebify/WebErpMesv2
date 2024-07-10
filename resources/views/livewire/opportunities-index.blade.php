<div>
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalOpportunity" tabindex="-1" role="dialog" aria-labelledby="ModalOpportunityTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="ModalOpportunityTitle">{{ __('general_content.new_opportunities_trans_key') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                    @csrf
                        <div class="card card-body">
                            @include('include.alert-result')
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="label">{{ __('general_content.name_opportunity_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  wire:model.live="label" name="label"  id="label"  placeholder="{{ __('general_content.name_opportunity_trans_key') }}" required>
                                    </div>
                                    @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control"  wire:model.live="user_id" name="user_id" id="user_id">
                                            <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                                            @foreach ($UsersSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="CutomerInfo">{{ __('general_content.customer_info_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="col-12">
                                    <x-adminlte-select2 name="companies_id" id="companies_id" label="{{ __('general_content.companie_trans_key') }}" label-class="text-lightblue"
                                        igroup-size="lg" data-placeholder="{{ __('general_content.select_task_trans_key') }}">
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text bg-gradient-info">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        </x-slot>
                                        <option value="null">{{ __('general_content.no_select_company_trans_key') }}</option>
                                        @forelse ($CompanieSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                        <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                                        @endforelse
                                    </x-adminlte-select2>
                                    
                                    

                                    @push('scripts')
                                        <script>
                                            $(document).ready(function () {
                                                $('#companies_id').select2();
                                                $('#companies_id').on('change', function (e) {
                                                    let data = $(this).val();
                                                    @this.set('companies_id', data);
                                                });
                                                window.livewire.on('productStore', () => {
                                                    $('#companies_id').select2();
                                                });
                                            });  
                                        </script>
                                    @endpush

                                    @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="companies_addresses_id">{{ __('general_content.adress_name_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.change="companies_addresses_id"  name="companies_addresses_id" id="companies_addresses_id">
                                            <option value="">{{ __('general_content.select_address_trans_key') }}</option>
                                            @forelse ($AddressSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->label }} - {{ $item->adress }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_contact_trans_key') }}</option>
                                        @endforelse
                                        </select>
                                    </div>
                                    @error('companies_addresses_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="companies_contacts_id">{{ __('general_content.contact_name_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.change="companies_contacts_id" name="companies_contacts_id" id="companies_contacts_id">
                                            <option value="">{{ __('general_content.select_contact_trans_key') }}</option>
                                            @forelse ($ContactSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->first_name }} - {{ $item->name }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_contact_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    @error('companies_contacts_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="GeneralInfo">{{ __('general_content.general_information_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="accounting_vats_id">{{ __('general_content.probality_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                        </div>
                                        <input type="number" class="form-control @error('probality') is-invalid @enderror" wire:model.live="probality" name="probality"  id="probality"  placeholder="50" required>
                                    </div>
                                    @error('probality') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="accounting_vats_id">{{ __('general_content.budget_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control @error('budget') is-invalid @enderror" id="budget" placeholder="0" min="0" wire:model.live="budget" step=".001" value="0">
                                    </div>
                                    @error('budget') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label>{{ __('general_content.comment_trans_key') }}</label>
                                    <textarea class="form-control" rows="3"  wire:model.live="comment" name="comment"  placeholder="..."></textarea>
                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                            <button type="Submit" wire:click.prevent="storeOpportunity()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <!-- View toggle button -->
                <div class="col-2">
                    <button class="btn btn-primary" wire:click="changeView('table')">
                        <i class="fas fa-table mr-1"></i> Table
                    </button>
                    <button class="btn btn-secondary" wire:click="changeView('cards')">
                        <i class="fas fa-th-large mr-1"></i> Cards
                    </button>
                </div>
                <div class="col-6">
                    @include('include.search-card')
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="searchIdStatus" id="searchIdStatus" wire:model.live="searchIdStatus">
                                <option value="" selected>{{ __('general_content.select_statu_trans_key') }}</option>
                                <option value="1">{{ __('general_content.new_trans_key') }}</option>
                                <option value="2">{{ __('general_content.quote_made_trans_key') }}</option>
                                <option value="3">{{ __('general_content.negotiation_trans_key') }}</option>
                                <option value="4">{{ __('general_content.closed_won_trans_key') }}</option>
                                <option value="5">{{ __('general_content.closed_lost_trans_key') }}</option>
                                <option value="6">{{ __('general_content.informational_trans_key') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalOpportunity">
                        {{ __('general_content.new_opportunities_trans_key') }}
                    </button>
                </div>
            </div>
            @if($viewType === 'table')
                
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')" role="button" href="#">{{__('general_content.customer_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                            </th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">{{__('general_content.created_at_trans_key') }}@include('include.sort-icon', ['field' => 'created_at'])</a>
                            </th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Opportunities as $Opportunity)
                        <tr>
                            <td>{{ $Opportunity->label }}</td>
                            <td>
                                <x-CompanieButton id="{{ $Opportunity->companies_id }}" label="{{ $Opportunity->companie['label'] }}"  />
                            </td>
                            <td>
                                @if(1 == $Opportunity->statu )   <span class="badge badge-info"> {{ __('general_content.new_trans_key') }}</span>@endif
                                @if(2 == $Opportunity->statu )  <span class="badge badge-primary">{{ __('general_content.quote_made_trans_key') }}</span>@endif
                                @if(3 == $Opportunity->statu )  <span class="badge badge-warning">{{ __('general_content.negotiation_trans_key') }}</span>@endif
                                @if(4 == $Opportunity->statu )  <span class="badge badge-success">{{ __('general_content.closed_won_trans_key') }}</span>@endif
                                @if(5 == $Opportunity->statu )  <span class="badge badge-danger">{{ __('general_content.closed_lost_trans_key') }}</span>@endif
                                @if(6 == $Opportunity->statu )   <span class="badge badge-secondary">{{ __('general_content.informational_trans_key') }}</span>@endif
                            </td>
                            <td><img src="{{ Avatar::create($Opportunity->UserManagement['name'])->toBase64() }}" /></td>
                            <td>{{ $Opportunity->GetPrettyCreatedAttribute() }}</td>
                            <td>
                                <x-ButtonTextView route="{{ route('opportunities.show', ['id' => $Opportunity->id])}}" />
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.customer_trans_key') }}</th>
                            <th>{{__('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th>{{__('general_content.created_at_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
                @else
                <!-- Vue en cartes -->
                <div class="row">
                    @forelse ($Opportunities as $Opportunity)
                        <div class="col-md-3 ">
                            <div class="card">
                                
                                @if(1 == $Opportunity->statu )  @php $backgroud="bg-info" @endphp @endif
                                @if(2 == $Opportunity->statu )  @php $backgroud="bg-primary" @endphp @endif
                                @if(3 == $Opportunity->statu )  @php $backgroud="bg-warning" @endphp @endif
                                @if(4 == $Opportunity->statu )  @php $backgroud="bg-success" @endphp @endif
                                @if(5 == $Opportunity->statu )  @php $backgroud="bg-danger" @endphp @endif
                                @if(6 == $Opportunity->statu )  @php $backgroud="bg-secondary" @endphp @endif

                                <div class="card-header {{ $backgroud }}">
                                    <div class="row">
                                        <div class="col-2">
                                            <img src="{{ Avatar::create($Opportunity->UserManagement['name'])->toBase64() }}" />
                                        </div>
                                        <div class="col-8">
                                            {{ $Opportunity->label }}
                                        </div>
                                        <div class="col-2">
                                            <x-ButtonTextView route="{{ route('opportunities.show', ['id' => $Opportunity->id])}}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><strong>{{ __('general_content.probality_trans_key') }}</strong> {{ $Opportunity->probality }} %</p>
                                    <p class="card-text"><strong>{{ __('general_content.status_trans_key') }}</strong>
                                        @if(1 == $Opportunity->statu )   <span class="badge badge-info"> {{ __('general_content.new_trans_key') }}</span>@endif
                                        @if(2 == $Opportunity->statu )  <span class="badge badge-primary">{{ __('general_content.quote_made_trans_key') }}</span>@endif
                                        @if(3 == $Opportunity->statu )  <span class="badge badge-warning">{{ __('general_content.negotiation_trans_key') }}</span>@endif
                                        @if(4 == $Opportunity->statu )  <span class="badge badge-success">{{ __('general_content.closed_won_trans_key') }}</span>@endif
                                        @if(5 == $Opportunity->statu )  <span class="badge badge-danger">{{ __('general_content.closed_lost_trans_key') }}</span>@endif
                                        @if(6 == $Opportunity->statu )   <span class="badge badge-secondary">{{ __('general_content.informational_trans_key') }}</span>@endif
                                    </p>
                                </div>
                                <div class="card-footer bg-secondary">
                                    <x-CompanieButton id="{{ $Opportunity->companies_id }}" label="{{ $Opportunity->companie['label'] }}"  />
                                </div>
                            </div>
                        </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-warning">{{ __('general_content.no_data_trans_key') }}</div>
                            </div>
                        @endforelse
                @endif
            </div>
        <!-- /.card-body -->
        </div>
    <!-- /.card -->
    </div>

    

    
    {{ $Opportunities->links() }}
</div>
