<div class="card-body">
    <div class="card">
        @if(session()->has('success'))
                <div class="alert alert-success" role="alert">
                    {{ session()->get('success') }}
                </div>
            @endif

            @if(session()->has('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session()->get('error') }}
                </div>
            @endif
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
                        </div>
                        @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-3">
                        <label for="label">Name of Delivery note</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="label" name="label"  id="label"  placeholder="Name of delivery note" required>
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
                    <div class="card-footer">
                        <div class="input-group">
                            <button type="Submit" wire:click.prevent="storeDeliveryNote()" class="btn btn-success">New delivery note</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('orders_id')" role="button" href="#">Order @include('include.sort-icon', ['field' => 'orders_id'])</a>
                            </th>
                            <th>Sort</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'code'])</a>
                            </th>
                            <th>Product</th>
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
                                <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $DeliverysRequestsLine->order['id']])}}">
                                    <i class="fas fa-folder"></i>
                                    View
                                </a>
                                {{ $DeliverysRequestsLine->order['code'] }}
                            </td>
                            <td>{{ $DeliverysRequestsLine->ORDRE }}</td>
                            <td>{{ $DeliverysRequestsLine->code }}</td>
                            <td>@if(1 == $DeliverysRequestsLine->product_id ) {{ $DeliverysRequestsLine->Product['label'] }}@endif</td>
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
                        <tr>
                            <td colspan="12">
                                <div class="flex justify-center items-center">
                                    <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No request found ...</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Order</th>
                            <th>Sort</th>
                            <th>External ID</th>
                            <th>Product</th>
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
    </form>
<!-- /.card-body -->
</div>