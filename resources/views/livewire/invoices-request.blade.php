<div>
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
                            @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="label">Name of new invoice</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model.live="label" name="label"  id="label"  placeholder="Name of quote" required>
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
                    <div class="form-group col-md-3"><br/>
                        <div class="input-group">
                            <button type="Submit" wire:click.prevent="storeInvoice()" class="btn btn-success">{{ __('general_content.new_invoice_trans_key') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('deliverys_id')" role="button" href="#">{{ __('general_content.delivery_notes_trans_key') }} @include('include.sort-icon', ['field' => 'orders_id'])</a>
                            </th>
                            <th>{{__('general_content.customer_trans_key') }}</th>
                            <th>{{__('general_content.id_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($InvoicesRequestsLineslist as $InvoicesRequestsLine)
                        <tr>
                            <td>
                                <x-OrderButton id="{{ $InvoicesRequestsLine->orderLine->order['id'] }}" code="{{ $InvoicesRequestsLine->orderLine->order['code'] }}"  />
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('deliverys.show', ['id' => $InvoicesRequestsLine->deliverys_id ]) }}">
                                    <i class="fas fa-folder"></i>
                                    {{ $InvoicesRequestsLine->delivery['code'] }}
                            </td>
                            <td>
                                @if($InvoicesRequestsLine->orderLine->order->type == 1 )
                                <x-CompanieButton id="{{ $InvoicesRequestsLine->orderLine->order->companies_id }}" label="{{ $InvoicesRequestsLine->orderLine->order->companie['label'] }}"  />
                                @else
                                {{ __('general_content.internal_order_trans_key') }}
                                @endif
                            </td>
                            <td>{{ $InvoicesRequestsLine->orderLine['code'] }}</td>
                            <td>{{ $InvoicesRequestsLine->orderLine['label'] }}</td>
                            <td>
                                {{ $InvoicesRequestsLine->qty }}
                            </td>
                            <td>{{ $InvoicesRequestsLine->orderLine->Unit['label'] }}</td>
                            <td>{{ $InvoicesRequestsLine->orderLine['selling_price'] }}</td>
                            <td>{{ $InvoicesRequestsLine->orderLine['discount'] }} %</td>
                            <td>{{ $InvoicesRequestsLine->orderLine->VAT['label'] }} %</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $InvoicesRequestsLine->id }}" wire:model.live="data.{{ $InvoicesRequestsLine->id }}.deliveryLine_id" id="data.{{ $InvoicesRequestsLine->id }}.deliveryLine_id"  type="checkbox">
                                    <label for="data.{{ $InvoicesRequestsLine->id }}.deliveryLine_id" class="custom-control-label">{{ __('general_content.add_to_document_trans_key') }}</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_notes_trans_key') }}</th>
                            <th>{{__('general_content.customer_trans_key') }}</th>
                            <th>{{__('general_content.id_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>