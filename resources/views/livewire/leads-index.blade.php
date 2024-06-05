<div class="card-body">
    <!-- View toggle button -->
    <div class="mb-3">
        <button class="btn btn-primary" wire:click="changeView('table')">
            <i class="fas fa-table mr-1"></i> Table
        </button>
        <button class="btn btn-secondary" wire:click="changeView('cards')">
            <i class="fas fa-th-large mr-1"></i> Cards
        </button>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalLead" tabindex="-1" role="dialog" aria-labelledby="ModalLeadTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="ModalLeadTitle">{{ __('general_content.new_leads_trans_key') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="card card-body">
                            @include('include.alert-result')
                            <div class="row">
                                <div class="col-12">
                                    <label for="companies_id">{{ __('general_content.companie_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.change="companies_id" name="companies_id" id="companies_id">
                                            <option value="">{{ __('general_content.select_company_trans_key') }}</option>
                                            @forelse ($CompanieSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
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
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="source">{{ __('general_content.source_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control @error('source') is-invalid @enderror" wire:model.live="source"  name="source" id="source" placeholder="{{ __('general_content.source_trans_key') }}" >
                                    </div>
                                    @error('source') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="campaign">{{ __('general_content.campaign_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control @error('campaign') is-invalid @enderror" wire:model.live="campaign"  name="campaign" id="campaign" placeholder="{{ __('general_content.campaign_trans_key') }}" >
                                    </div>
                                    @error('campaign') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                                    
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="user_id" name="user_id" id="user_id">
                                            <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                                        @foreach ($UsersSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                    @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="priority">{{ __('general_content.priority_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-text bg-gradient-success">
                                            <i class="fas fa-exclamation"></i>
                                        </div>
                                        <select class="form-control" wire:model.live="priority" name="priority" id="priority">
                                            <option value="1" >{{ __('general_content.burning_trans_key') }}</option>
                                            <option value="2" >{{ __('general_content.hot_trans_key') }}</option>
                                            <option value="3" >{{ __('general_content.lukewarm_trans_key') }}</option>
                                            <option value="4" >{{ __('general_content.cold_trans_key') }}</option>
                                        </select>
                                    </div>
                                    @error('priority') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label>{{ __('general_content.comment_trans_key') }}</label>
                                    <textarea class="form-control" rows="3" wire:model.live="comment" name="comment"  placeholder="..."></textarea>
                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                            <button type="Submit" wire:click.prevent="storeLead()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->
    @if($viewType === 'table')
    <div class="card">
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{ __('general_content.contact_name_trans_key') }}</th>
                        <th>{{ __('general_content.adress_name_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{ __('general_content.source_trans_key') }}</th>
                        <th>{{ __('general_content.priority_trans_key') }}</th>
                        <th>{{ __('general_content.campaign_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('statu')" role="button" href="#">{{ __('general_content.status_trans_key') }} @include('include.sort-icon', ['field' => 'statu'])</a>
                        </th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($Leadslist as $Lead)
                    <tr>
                        <td>#{{ $Lead->id }}</td>
                        <td><x-CompanieButton id="{{ $Lead->companies_id }}" label="{{ $Lead->companie['label'] }}"  /></td>
                        <td>{{ $Lead->companie['first_name'] }} {{ $Lead->contact['name'] }}</td>
                        <td>{{ $Lead->adresse['adress'] }} {{ $Lead->adresse['zipcode'] }}  {{ $Lead->adresse['city'] }}</td>
                        <td><img src="{{ Avatar::create($Lead->UserManagement['name'])->toBase64() }}" /></td>
                        <td>{{ $Lead->source }}</td>
                        <td>
                            @if(1 == $Lead->priority )  <span class="badge badge-danger">{{ __('general_content.burning_trans_key') }}</span>@endif
                            @if(2 == $Lead->priority )  <span class="badge badge-warning">{{ __('general_content.hot_trans_key') }}</span>@endif
                            @if(3 == $Lead->priority )  <span class="badge badge-primary">{{ __('general_content.lukewarm_trans_key') }}</span>@endif
                            @if(4 == $Lead->priority )  <span class="badge badge-success">{{ __('general_content.cold_trans_key') }}</span>@endif
                        </td>
                        <td>{{ $Lead->campaign }}</td>
                        <td>
                            @if(1 == $Lead->statu )  <span class="badge badge-info">{{ __('general_content.new_trans_key') }}</span>@endif
                            @if(2 == $Lead->statu )  <span class="badge badge-warning">{{ __('general_content.assigned_trans_key') }}</span>@endif
                            @if(3 == $Lead->statu )  <span class="badge badge-primary">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                            @if(4 == $Lead->statu )  <span class="badge badge-success">{{ __('general_content.converted_trans_key') }}</span>@endif
                            @if(5 == $Lead->statu )  <span class="badge badge-danger">{{ __('general_content.lost_trans_key') }}</span>@endif 
                        </td>
                        <td> 
                            <div class="input-group-prepend">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <div class="dropdown-menu">
                                    <a href="#" class="dropdown-item" wire:click="inProgress({{$Lead->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i>{{ __('general_content.in_progress_trans_key') }}</span></a>
                                    <a href="#" class="dropdown-item " wire:click="storeOpportunity({{$Lead->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i>{{ __('general_content.converted_trans_key') }}</span></a>
                                    <a href="#" class="dropdown-item" wire:click="lost({{$Lead->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.lost_trans_key') }}</span></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="10" text=" {{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{ __('general_content.contact_name_trans_key') }}</th>
                        <th>{{ __('general_content.adress_name_trans_key') }}</th>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{ __('general_content.source_trans_key') }}</th>
                        <th>{{ __('general_content.priority_trans_key') }}</th>
                        <th>{{ __('general_content.campaign_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        <!-- /.row -->
        </div>
    <!-- /.card -->
    </div>
    @else
        <!-- Vue en cartes -->
        <div class="row">
            @forelse ($Leadslist as $lead)
                <div class="col-md-3 ">
                    <div class="card">
                        
                        @if(1 == $Lead->statu )  @php $backgroud="bg-info" @endphp @endif
                        @if(2 == $Lead->statu )  @php $backgroud="bg-warning" @endphp @endif
                        @if(3 == $Lead->statu )  @php $backgroud="bg-primary" @endphp @endif
                        @if(4 == $Lead->statu )  @php $backgroud="bg-success" @endphp @endif
                        @if(5 == $Lead->statu )  @php $backgroud="bg-danger" @endphp @endif

                        <div class="card-header {{ $backgroud }}">
                            <div class="row">
                                <div class="col-4">
                                    <img src="{{ Avatar::create($Lead->UserManagement['name'])->toBase64() }}" />
                                </div>
                                <div class="col-4">
                                    <x-CompanieButton id="{{ $Lead->companies_id }}" label="{{ $Lead->companie['label'] }}"  />
                                </div>
                                <div class="col-4">
                                    <p class="card-text"><strong>{{ __('general_content.priority_trans_key') }}</strong>
                                        @if(1 == $Lead->priority )  <span class="badge badge-danger">{{ __('general_content.burning_trans_key') }}</span>@endif
                                        @if(2 == $Lead->priority )  <span class="badge badge-warning">{{ __('general_content.hot_trans_key') }}</span>@endif
                                        @if(3 == $Lead->priority )  <span class="badge badge-primary">{{ __('general_content.lukewarm_trans_key') }}</span>@endif
                                        @if(4 == $Lead->priority )  <span class="badge badge-success">{{ __('general_content.cold_trans_key') }}</span>@endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ $Lead->companie['first_name'] }} {{ $Lead->contact['name'] }}</p>
                            <p class="card-text">{{ $Lead->adresse['adress'] }} {{ $Lead->adresse['zipcode'] }}  {{ $Lead->adresse['city'] }}</p>
                            <p class="card-text"><strong>{{ __('general_content.source_trans_key') }}</strong> {{ $Lead->source }}</p>
                            <p class="card-text"><strong>{{ __('general_content.campaign_trans_key') }}</strong>{{ $Lead->campaign }}</p>
                            <p class="card-text"><strong>{{ __('general_content.status_trans_key') }}</strong>
                                @if(1 == $Lead->statu )  <span class="badge badge-info">{{ __('general_content.new_trans_key') }}</span>@endif
                                @if(2 == $Lead->statu )  <span class="badge badge-warning">{{ __('general_content.assigned_trans_key') }}</span>@endif
                                @if(3 == $Lead->statu )  <span class="badge badge-primary">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                                @if(4 == $Lead->statu )  <span class="badge badge-success">{{ __('general_content.converted_trans_key') }}</span>@endif
                                @if(5 == $Lead->statu )  <span class="badge badge-danger">{{ __('general_content.lost_trans_key') }}</span>@endif
                            </p>
                        </div>
                        <div class="card-footer bg-secondary">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="actionMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Actions
                            </button>
                            <div class="dropdown-menu" aria-labelledby="actionMenu">
                                <a class="dropdown-item" wire:click="inProgress({{ $Lead->id }})">{{ __('general_content.in_progress_trans_key') }}</a>
                                <a class="dropdown-item" wire:click="storeOpportunity({{ $Lead->id }})">{{ __('general_content.converted_trans_key') }}</a>
                                <a class="dropdown-item" wire:click="lost({{ $Lead->id }})">{{ __('general_content.lost_trans_key') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning">{{ __('general_content.no_data_trans_key') }}</div>
                </div>
            @endforelse
        </div>
    @endif
    
    {{ $Leadslist->links() }}
<!-- /.card-body -->
</div>