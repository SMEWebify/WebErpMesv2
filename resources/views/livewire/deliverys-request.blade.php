<div >
    <div class="card">
        @include('include.alert-result')
        <form>
            @csrf
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="companies_id">Sort Companie</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                            </div>
                            <select class="form-control" wire:model="companies_id" name="companies_id" id="companies_id">
                                <option value="">Select company</option>
                            @forelse ($CompaniesSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                            @empty
                                <option value="">No company, please add</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="code">External ID</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="code" name="code" id="code" placeholder="External ID">
                        </div>
                        @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="label">Name of Delivery note</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="label" name="label"  id="label"  placeholder="Name of delivery note" required>
                        </div>
                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="user_id">User management</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" wire:model="user_id" name="user_id" id="user_id">
                                <option value="">Select user management</option>
                            @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                            </select>
                        </div>
                        @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="companies_addresses_id">Adress</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <select class="form-control" wire:model="companies_addresses_id" name="companies_addresses_id" id="companies_addresses_id">
                                <option value="">Select address</option>
                            @forelse ($AddressSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->label }} - {{ $item->adress }}</option>
                            @empty
                                <option value="">No address, please add</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_addresses_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="companies_contacts_id">Contact</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" wire:model="companies_contacts_id" name="companies_contacts_id" id="companies_contacts_id">
                                <option value="">Select contact</option>
                            @forelse ($ContactSelect as $item)
                                <option value="{{ $item->id }}">{{ $item->first_name }} - {{ $item->name }}</option>
                            @empty
                                <option value="">No contact, please add</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_contacts_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3"><br/>
                        <div class="input-group">
                            <button type="Submit" wire:click.prevent="storeDeliveryNote()" class="btn btn-success">New Delivery note</button>
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
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('orders_id')" role="button" href="#">Order @include('include.sort-icon', ['field' => 'orders_id'])</a>
                        </th>
                        <th>Companie</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">External ID @include('include.sort-icon', ['field' => 'code'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
                        </th>
                        <th>Qty</th>
                        <th>Scum qty</th>
                        <th>Unit</th>
                        <th>Selling price</th>
                        <th>Discount</th>
                        <th>VAT type</th>
                        <th>Delivery date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($DeliverysRequestsLineslist as $DeliverysRequestsLine)
                    <tr>
                        <td>
                            <x-OrderButton id="{{ $DeliverysRequestsLine->order['id'] }}" code="{{ $DeliverysRequestsLine->order['code'] }}"  />
                        </td>
                        <td>
                            @if($DeliverysRequestsLine->order->type == 1 )
                            <x-CompanieButton id="{{ $DeliverysRequestsLine->order->companies_id }}" label="{{ $DeliverysRequestsLine->order->companie['label'] }}"  />
                            @else
                            Internal order
                            @endif
                        </td>
                        <td>{{ $DeliverysRequestsLine->code }}</td>
                        <td>{{ $DeliverysRequestsLine->label }}</td>
                        <td>
                            {{ $DeliverysRequestsLine->delivered_remaining_qty }}
                        </td>
                        <td>
                            <input class="form-control" wire:model="data.{{ $DeliverysRequestsLine->id }}.scumQty" placeholder="Quantity" type="number">
                        </td>
                        <td>{{ $DeliverysRequestsLine->Unit['label'] }}</td>
                        <td>{{ $DeliverysRequestsLine->selling_price }}</td>
                        <td>{{ $DeliverysRequestsLine->discount }}</td>
                        <td>{{ $DeliverysRequestsLine->VAT['label'] }}</td>
                        <td>{{ $DeliverysRequestsLine->delivery_date }}</td>
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" value="{{ $DeliverysRequestsLine->id }}" wire:model="data.{{ $DeliverysRequestsLine->id }}.order_line_id" id="data.{{ $DeliverysRequestsLine->id }}.order_line_id"  type="checkbox">
                                <label for="data.{{ $DeliverysRequestsLine->id }}.order_line_id" class="custom-control-label">Add to new delivery note</label>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="13" text="No request found ..."  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>Order</th>
                        <th>Companie</th>
                        <th>External ID</th>
                        <th>Label</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Scum qty</th>
                        <th>Unit</th>
                        <th>Selling price</th>
                        <th>Discount</th>
                        <th>VAT type</th>
                        <th>Delivery date</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>