
<div>
    @php
        $nest2prodUrl = rtrim((string) app(\App\Services\Settings\SettingsService::class)->get('n2p_base_url'), '/');
    @endphp
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalOrder" tabindex="-1" role="dialog" aria-labelledby="ModalOrderTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="ModalOrderTitle">{{ __('general_content.new_order_trans_key') }}</h5>
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
                                    <label for="label">{{ __('general_content.name_order_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model.live="label" name="label"  id="label" placeholder="{{ __('general_content.name_order_trans_key') }}" required>
                                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                    </div>
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
                                            <option value="{{ $item->id }}" >{{ $item->name }}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                    @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="user_id">{{ __('general_content.order_type_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control" wire:click.prevent="changeLabel()" wire:model.live="type" name="type" id="type">)
                                            <option value="1" >{{ __('general_content.customer_type_order_trans_key') }}</option>
                                            <option value="2" >{{ __('general_content.internal_type_order_trans_key') }}</option>
                                        </select>
                                    </div>
                                    @error('type') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">{{ __('general_content.customer_info_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="companies_id">{{ __('general_content.companie_trans_key') }}</label> 
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="companies_id" name="companies_id" id="companies_id" @if($type == 2) disabled @endif>
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
                                <div class="form-group col-md-6">
                                    <label for="customer_reference">{{ __('general_content.customer_reference_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model.live="customer_reference"  name="customer_reference"  id="customer_reference" placeholder="{{ __('general_content.customer_reference_trans_key') }}" @if($type == 2) disabled @endif>
                                        @error('customer_reference') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="companies_addresses_id">{{ __('general_content.adress_name_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="companies_addresses_id" name="companies_addresses_id" id="companies_addresses_id" @if($type == 2) disabled @endif>
                                        <option value="">{{ __('general_content.select_address_trans_key') }}</option>
                                        @forelse ($AddressSelect as $item)
                                            <option value="{{ $item->id }}" >{{ $item->label }} - {{ $item->adress }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_address_trans_key') }}</option>
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
                                        <select class="form-control" wire:model.live="companies_contacts_id" name="companies_contacts_id" id="companies_contacts_id" @if($type == 2) disabled @endif>
                                            <option value="">{{ __('general_content.select_contact_trans_key') }}</option>
                                        @forelse ($ContactSelect as $item)
                                            <option value="{{ $item->id }}" >{{ $item->first_name }} - {{ $item->name }}</option>
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
                                <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="accounting_payment_conditions_id">{{ __('general_content.payment_conditions_trans_key') }}</label>
                                    <select class="form-control" wire:model.live="accounting_payment_conditions_id"  name="accounting_payment_conditions_id" id="accounting_payment_conditions_id" @if($type == 2) disabled @endif>
                                        <option value="">{{ __('general_content.select_payement_condition_trans_key') }}</option>
                                    @forelse ($AccountingConditionSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                    @empty
                                        <option value="">{{ __('general_content.no_payment_conditions_trans_key') }}</option>
                                    @endforelse
                                    </select>
                                    @error('accounting_payment_conditions_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="accounting_payment_methods_id">{{ __('general_content.select_payement_methods_trans_key') }}</label>
                                    <select class="form-control" wire:model.live="accounting_payment_methods_id" name="accounting_payment_methods_id" id="accounting_payment_methods_id" @if($type == 2) disabled @endif>
                                        <option value="">{{ __('general_content.select_payement_methods_trans_key') }}</option>
                                    @forelse ($AccountingMethodsSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                    @empty
                                        <option value="">{{ __('general_content.no_payment_methods_trans_key') }}</option>
                                    @endforelse
                                    </select>
                                    @error('accounting_payment_methods_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="accounting_deliveries_id">{{ __('general_content.delevery_method_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                        </div>
                                        <select class="form-control" wire:model.live="accounting_deliveries_id" name="accounting_deliveries_id" id="accounting_deliveries_id" @if($type == 2) disabled @endif>
                                            <option value="">{{ __('general_content.select_delivery_trans_key') }}</option>
                                        @forelse ($AccountingDeleveriesSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                            <option value="">{{ __('general_content.no_delivery_trans_key') }}</option>
                                        @endforelse
                                        </select>
                                    </div>
                                    @error('accounting_deliveries_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="label">{{ __('general_content.delivery_date_trans_key') }}</label>
                                    <input type="date" class="form-control" wire:model.live="validity_date"  name="validity_date"  id="validity_date">
                                    @error('validity_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-12">
                                    <label>{{ __('general_content.comment_trans_key') }}</label>
                                    <textarea class="form-control" rows="3" wire:model.live="comment" name="comment"  placeholder=" ..."></textarea>
                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                            <button type="Submit" wire:click.prevent="storeOrder()" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
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
                    <button class="btn {{ $viewType === 'table' ? 'btn-primary' : 'btn-secondary' }}" wire:click="changeView('table')">
                        <i class="fas fa-table mr-1"></i> Table
                    </button>
                    <button class="btn {{ $viewType === 'cards' ? 'btn-primary' : 'btn-secondary' }}" wire:click="changeView('cards')">
                        <i class="fas fa-th-large mr-1"></i> Cards
                    </button>
                    <button class="btn {{ $viewType === 'kanban' ? 'btn-primary' : 'btn-secondary' }}" wire:click="changeView('kanban')">
                        <i class="fas  fa-tasks mr-1"></i> Kanban
                    </button>
                </div>
                <div class="col-6">
                    @include('include.search-card')
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="searchIdStatus" id="searchIdStatus" wire:model.live="searchIdStatus" multiple>
                                <option value="1">{{ __('general_content.open_trans_key') }}</option>
                                <option value="2">{{ __('general_content.in_progress_trans_key') }}</option>
                                <option value="3">{{ __('general_content.delivered_trans_key') }}</option>
                                <option value="4">{{ __('general_content.partly_delivered_trans_key') }}</option>
                                <option value="5">{{ __('general_content.stopped_trans_key') }}</option>
                                <option value="6">{{ __('general_content.canceled_trans_key') }}</option>
                            </select>
                        </div>
                        <small class="form-text text-muted">{{ __('general_content.select_statu_trans_key') }}</small>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalOrder">
                        {{ __('general_content.new_order_trans_key') }}
                    </button>
                </div>
            </div>
            @if($viewType === 'table')
                <!-- Vue en table -->
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
                                    <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')"   role="button" href="#">{{__('general_content.customer_trans_key') }} @include('include.sort-icon', ['field' => 'companies_id'])</a>
                                </th>
                                <th>
                                    <a class="btn btn-secondary" wire:click.prevent="sortBy('validity_date')" role="button" href="#">{{ __('general_content.delivery_date_trans_key') }} @include('include.sort-icon', ['field' => 'validity_date'])</a>
                                </th>
                                <th>{{__('general_content.lines_count_trans_key') }}</th>
                                <th>{{ __('general_content.progress_trans_key') }}</th>
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
                            @forelse ($Orderslist as $Order)
                            <tr>
                                <td>{{ $Order->code }}</td>
                                <td>{{ $Order->label }}</td>
                                <td>
                                    @if($Order->type == 1 )
                                    <x-CompanieButton id="{{ $Order->companies_id }}" label="{{ $Order->companie['label'] }}"  />
                                    @else
                                    {{ __('general_content.internal_order_trans_key') }}
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $deliveryDate = $Order->validity_date ? \Carbon\Carbon::parse($Order->validity_date) : null;
                                        $today = \Carbon\Carbon::today();
                                        $deliveryBadgeClass = 'badge-secondary';

                                        if ($deliveryDate) {
                                            if ($deliveryDate->lt($today)) {
                                                $deliveryBadgeClass = 'badge-danger';
                                            } elseif ($deliveryDate->equalTo($today)) {
                                                $deliveryBadgeClass = 'badge-warning';
                                            } else {
                                                $deliveryBadgeClass = 'badge-success';
                                            }
                                        }
                                    @endphp
                                    @if($deliveryDate)
                                        <span class="badge {{ $deliveryBadgeClass }}">{{ $deliveryDate->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $Order->order_lines_count }}</td>
                                <td><x-adminlte-progress theme="teal" value="{{ $Order->getAveragePercentProgressLinesAttribute() }}" with-label animated/></td>
                                <td>{{ $Order->formatted_total_price }}</td>
                                <td>
                                    @if(1 == $Order->statu )  <span class="badge badge-info">{{ __('general_content.open_trans_key') }}</span>@endif
                                    @if(2 == $Order->statu )  <span class="badge badge-warning">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                                    @if($Order->type == 1 )
                                        @if(3 == $Order->statu )  <span class="badge badge-success">{{ __('general_content.delivered_trans_key') }}</span>@endif
                                        @if(4 == $Order->statu )  <span class="badge badge-danger">{{ __('general_content.partly_delivered_trans_key') }}</span>@endif
                                    @else
                                        @if(3 == $Order->statu )  <span class="badge badge-success">{{ __('general_content.stock_trans_key') }}</span>@endif
                                        @if(4 == $Order->statu )  <span class="badge badge-danger">{{ __('general_content.partly_stored_trans_key') }}</span>@endif
                                    @endif
                                    
                                    @if(5 == $Order->statu )  <span class="badge badge-danger">{{ __('general_content.stopped_trans_key') }}</span>@endif
                                    @if(6 == $Order->statu )  <span class="badge badge-warning">{{ __('general_content.canceled_trans_key') }}</span>@endif
                                </td>
                                <td><img src="{{ Avatar::create($Order->UserManagement['name'])->toBase64() }}" /></td>
                                <td>{{ $Order->GetPrettyCreatedAttribute() }}</td>
                                <td>
                                    <x-ButtonTextView route="{{ route('orders.show', ['id' => $Order->id])}}" />
                                    <x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" />
                                    @if($Order->statu != 1 && $nest2prodUrl !== '')
                                        <a href="{{ $nest2prodUrl }}/fr/orders/{{ rawurlencode($Order->code) }}" rel="noopener" target="_blank" class="btn btn-info btn-sm">
                                            <i class="fas fa-external-link-alt"></i> Nest2Prod
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                                <x-EmptyDataLine col="11" text="{{ __('general_content.no_data_trans_key') }}"  />
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{{__('general_content.id_trans_key') }}</th>
                                <th>{{__('general_content.label_trans_key') }}</th>
                                <th>{{__('general_content.customer_trans_key') }}</th>
                                <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                                <th>{{__('general_content.lines_count_trans_key') }}</th>
                                <th>{{ __('general_content.progress_trans_key') }}</th>
                                <th>{{__('general_content.total_price_trans_key') }}</th>
                                <th>{{__('general_content.status_trans_key') }}</th>
                                <th>{{ __('general_content.user_trans_key') }}</th>
                                <th>{{__('general_content.created_at_trans_key') }}</th>
                                <th>{{__('general_content.action_trans_key') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    {{ $Orderslist->links() }}
                </div>
                <!-- /.row -->
            @elseif($viewType === 'cards')
                <div class="row">
                    @forelse ($Orderslist as $Order)
                        <div class="col-md-3 ">
                            <div class="card">
                                
                                @if(1 == $Order->statu )  @php $backgroud="bg-info" @endphp @endif
                                @if(2 == $Order->statu )  @php $backgroud="bg-warning" @endphp @endif
                                @if(3 == $Order->statu )  @php $backgroud="bg-success" @endphp @endif
                                @if(4 == $Order->statu )  @php $backgroud="bg-danger" @endphp @endif
                                @if(5 == $Order->statu )  @php $backgroud="bg-danger" @endphp @endif
                                @if(6 == $Order->statu )  @php $backgroud="bg-warning" @endphp @endif

                                <div class="card-header {{ $backgroud }}">
                                    <div class="row">
                                        <div class="col-2">
                                            <img src="{{ Avatar::create($Order->UserManagement['name'])->toBase64() }}" />
                                        </div>
                                        <div class="col-10">
                                            {{ $Order->code }}
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><strong>{{__('general_content.progress_trans_key') }}</strong> : <x-adminlte-progress theme="teal" value="{{ $Order->getAveragePercentProgressLinesAttribute() }}" with-label animated/></p>
                                    <p class="card-text"><strong>{{__('general_content.total_price_trans_key') }}</strong> : {{ $Order->formatted_total_price }}</p>
                                </div>
                                <div class="card-footer bg-secondary">
                                    <div class="row">
                                        <div class="col-8">
                                            @if($Order->type == 1 )
                                            <x-CompanieButton id="{{ $Order->companies_id }}" label="{{ $Order->companie['label'] }}"  />
                                            @else
                                            {{ __('general_content.internal_order_trans_key') }}
                                            @endif
                                        </div>
                                        <div class="col-4">
                                            <x-ButtonTextView route="{{ route('orders.show', ['id' => $Order->id])}}" />
                                            @if($Order->statu != 1 && $nest2prodUrl !== '')
                                                <a href="{{ $nest2prodUrl }}/fr/orders/{{ rawurlencode($Order->code) }}" rel="noopener" target="_blank" class="btn btn-info btn-sm mt-1">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            @endif
                                        </div>
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
                
                <div class="row">
                    <div class="col-12">
                        {{ $Orderslist->links() }}
                    </div> 
                </div>
            @elseif($viewType === 'kanban')
                <!-- Kanban View -->
                <div wire:sortable="updateColumnOrder" wire:sortable-group="updateTaskOrder" style="display: flex; flex-wrap: wrap;z-index: 0;">
                    @foreach($statuses as $status)
                        <div wire:sortable.item="{{ $status['id'] }}" wire:key="status-{{ $status['id'] }}" class="col-12 col-lg-6 col-xl-2" >
                            <div class="card">
                                {{-- Gestion des couleurs en fonction du statut --}}
                                @php
                                    $backgroud = '';
                                    switch ($status['id']) {
                                        case 1:
                                            $backgroud = 'bg-info';
                                            break;
                                        case 2:
                                            $backgroud = 'bg-primary';
                                            break;
                                        case 3:
                                            $backgroud = 'bg-warning';
                                            break;
                                        case 4:
                                            $backgroud = 'bg-success';
                                            break;
                                        case 5:
                                            $backgroud = 'bg-danger';
                                            break;
                                        case 6:
                                            $backgroud = 'bg-secondary';
                                            break;
                                    }
                                @endphp
                
                                <div class="card-header {{ $backgroud }}">
                                    <div class="row">
                                        <div class="col-10">
                                            <h5 wire:sortable.handle>{{ $status['title'] }}</h5>
                                        </div>
                                    </div>
                                </div>
                
                                <div class="card-body">
                                    <ul wire:sortable-group.item-group="{{ $status['id'] }}" >
                                        @forelse ($status['Orders'] as $Order)
                                            <li wire:key="task-{{ $Order['id'] }}" wire:sortable-group.item="{{ $Order['id'] }}" class="card bg-light" style="z-index: 10;">
                                                <div wire:sortable-group.handle >
                                                    <div class="card-header bg-lightblue disabled color-palette">
                                                        <div class="row">
                                                            <div class="col-2">
                                                                <img src="{{ Avatar::create($Order->UserManagement['name'])->toBase64() }}" />
                                                            </div>
                                                            <div class="col-10">
                                                                {{ $Order->code }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="card-text"><strong>{{__('general_content.progress_trans_key') }}</strong> : <x-adminlte-progress theme="teal" value="{{ $Order->getAveragePercentProgressLinesAttribute() }}" with-label animated/></p>
                                                        <p class="card-text"><strong>{{__('general_content.total_price_trans_key') }}</strong> : {{ $Order->formatted_total_price }}</p>
                                                    </div>
                                                    <div class="card-footer bg-secondary">
                                                        <div class="row">
                                                            <div class="col-8">
                                                                @if($Order->type == 1 )
                                                                <x-CompanieButton id="{{ $Order->companies_id }}" label="{{ $Order->companie['label'] }}"  />
                                                                @else
                                                                {{ __('general_content.internal_order_trans_key') }}
                                                                @endif
                                                            </div>
                                                            <div class="col-4">
                                                                <x-ButtonTextView route="{{ route('orders.show', ['id' => $Order->id])}}" />
                                                                @if($Order->statu != 1 && $nest2prodUrl !== '')
                                                                    <a href="{{ $nest2prodUrl }}/fr/orders/{{ rawurlencode($Order->code) }}" rel="noopener" target="_blank" class="btn btn-info btn-sm mt-1">
                                                                        <i class="fas fa-external-link-alt"></i>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            @empty
                                            <div class="card-header">
                                                {{ __('general_content.no_data_trans_key') }}
                                            </div>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <!-- /.card -->
    </div>
<!-- /.div -->
</div>
