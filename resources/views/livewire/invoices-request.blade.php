<div class="card-body">
    <div class="card">
        @include('include.alert-result')
        <form>
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
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
                    <div class="col-3">
                        <label for="code">External ID</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="code" name="code" id="code" placeholder="External ID">
                            @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                    </div>
                    <div class="col-3">
                        <label for="label">Name of Delivery note</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="label" name="label"  id="label"  placeholder="Name of quote" required>
                        </div>
                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-3">
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
                </div>
                <div class="row">
                    <div class="col-3">
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
                    <div class="col-3">
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
                    <div class="col-3"><br/>
                        <div class="input-group">
                            <button type="Submit" wire:click.prevent="storeInvoice()" class="btn btn-success">New invoice</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('orders.id')" role="button" href="#">Order @include('include.sort-icon', ['field' => 'orders_id'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('deliverys_id')" role="button" href="#">Delivery @include('include.sort-icon', ['field' => 'orders_id'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'code'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Selling price</th>
                            <th>Discount</th>
                            <th>VAT type</th>
                            <th>Action</th>
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
                                    <input class="custom-control-input" value="{{ $InvoicesRequestsLine->id }}" wire:model="data.{{ $InvoicesRequestsLine->id }}.deliverys_id" id="data.{{ $InvoicesRequestsLine->id }}.deliverys_id"  type="checkbox">
                                    <label for="data.{{ $InvoicesRequestsLine->id }}.deliverys_id" class="custom-control-label">Add to new invoice</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="12" text="No request found ..."  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Order</th>
                            <th>Delivery</th>
                            <th>code</th>
                            <th>label</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Selling price</th>
                            <th>Discount</th>
                            <th>VAT type</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <!-- /.card -->
        </div>
    </form>
<!-- /.card-body -->
</div>