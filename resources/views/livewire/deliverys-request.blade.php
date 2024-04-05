<div >
    <div class="card">
        @include('include.alert-result')
        <form>
            @csrf
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="companies_id">{{ __('general_content.sort_companie_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                            </div>
                            <select class="form-control" wire:model.live="companies_id" name="companies_id" id="companies_id">
                                <option value="">{{ __('general_content.select_company_trans_key') }}</option>
                            @forelse ($CompaniesSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                            @empty
                                <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
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
                        <label for="label">{{ __('general_content.name_of_deliverys_notes_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model.live="label" name="label"  id="label"  placeholder="{{ __('general_content.name_of_deliverys_notes_trans_key') }}" required>
                        </div>
                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
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
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                            </select>
                        </div>
                        @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="companies_addresses_id">{{ __('general_content.adress_name_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <select class="form-control" wire:model.live="companies_addresses_id" name="companies_addresses_id" id="companies_addresses_id">
                                <option value="">{{ __('general_content.select_address_trans_key') }}</option>
                            @forelse ($AddressSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->label }} - {{ $item->adress }}</option>
                            @empty
                                <option value="">{{ __('general_content.no_address_trans_key') }}</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_addresses_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="companies_contacts_id">{{ __('general_content.contact_name_trans_key') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" wire:model.live="companies_contacts_id" name="companies_contacts_id" id="companies_contacts_id">
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
                    <div class="form-group col-md-3">
                        <label for="RemoveFromStock">{{ __('general_content.remove_component_lines_stock_trans_key') }}</label>
                        <input type="checkbox" id="RemoveFromStock" wire:model.live="RemoveFromStock" >
                        <label for="CreateSerialNumber">{{ __('general_content.create_serial_number_trans_key') }}</label>
                        <input type="checkbox" id="CreateSerialNumber" wire:model.live="CreateSerialNumber" >
                    </div>
                    
                    <div class="form-group col-md-3"><br/>
                        <div class="input-group">
                            <button type="Submit" wire:click.prevent="storeDeliveryNote()" class="btn btn-success">{{ __('general_content.new_delivery_note_trans_key') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('orders_id')" role="button" href="#">{{__('general_content.order_trans_key') }} @include('include.sort-icon', ['field' => 'orders_id'])</a>
                        </th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{ __('general_content.external_id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                        </th>
                        <th>{{ __('general_content.tasks_status_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.scum_qty_trans_key') }}</th>
                        <th>{{ __('general_content.unit_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                        <th>{{ __('general_content.discount_trans_key') }}</th>
                        <th>{{ __('general_content.vat_trans_key') }}</th>
                        <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i=1 @endphp
                    @forelse ($DeliverysRequestsLineslist as $DeliverysRequestsLine)
                    <tr>
                        <td>
                            <x-OrderButton id="{{ $DeliverysRequestsLine->order['id'] }}" code="{{ $DeliverysRequestsLine->order['code'] }}"  />
                        </td>
                        <td>
                            @if($DeliverysRequestsLine->order->type == 1 )
                            <x-CompanieButton id="{{ $DeliverysRequestsLine->order->companies_id }}" label="{{ $DeliverysRequestsLine->order->companie['label'] }}"  />
                            @else
                            {{ __('general_content.internal_order_trans_key') }}
                            @endif
                        </td>
                        <td>{{ $DeliverysRequestsLine->code }}</td>
                        <td>{{ $DeliverysRequestsLine->label }}</td>
                        
                        <td>
                            @if(1 == $DeliverysRequestsLine->tasks_status )  <span class="badge badge-info">{{ __('general_content.no_task_trans_key') }}</span>@endif
                            @if(2 == $DeliverysRequestsLine->tasks_status )  <span class="badge badge-warning">{{ __('general_content.created_trans_key') }}</span>@endif
                            @if(3 == $DeliverysRequestsLine->tasks_status )  <span class="badge badge-success">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                            @if(4 == $DeliverysRequestsLine->tasks_status )  <span class="badge badge-danger">{{ __('general_content.finished_task_trans_key') }}</span>@endif
                        </td>
                        <td>{{ $DeliverysRequestsLine->delivered_remaining_qty }}</td>
                        <td>
                            <input class="form-control" wire:model.live="data.{{ $DeliverysRequestsLine->id }}.scumQty" placeholder="{{ __('general_content.qty_trans_key') }}" type="number">
                        </td>
                        <td>{{ $DeliverysRequestsLine->Unit['label'] }}</td>
                        <td>{{ $DeliverysRequestsLine->selling_price }}</td>
                        <td>{{ $DeliverysRequestsLine->discount }}</td>
                        <td>{{ $DeliverysRequestsLine->VAT['label'] }}</td>
                        <td>{{ $DeliverysRequestsLine->delivery_date }}</td>
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" value="{{ $DeliverysRequestsLine->id }}" wire:model.live="data.{{ $DeliverysRequestsLine->id }}.order_line_id" id="data.{{ $DeliverysRequestsLine->id }}.order_line_id"  type="checkbox">
                                <label for="data.{{ $DeliverysRequestsLine->id }}.order_line_id" class="custom-control-label">Add to new delivery note</label>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="14" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{__('general_content.customer_trans_key') }}</th>
                        <th>{{ __('general_content.external_id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{ __('general_content.tasks_status_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.scum_qty_trans_key') }}</th>
                        <th>{{ __('general_content.unit_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                        <th>{{ __('general_content.discount_trans_key') }}</th>
                        <th>{{ __('general_content.vat_trans_key') }}</th>
                        <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                        <th>{{__('general_content.action_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>