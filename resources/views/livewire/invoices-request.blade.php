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
                                <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                            @empty
                                <option value="">No company, please add</option>
                            @endforelse
                            </select>
                        </div>
                        @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-3">
                        <label for="CODE">External ID</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="CODE" name="CODE" id="CODE" placeholder="External ID">
                            @error('CODE') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                    </div>
                    <div class="col-3">
                        <label for="LABEL">Name of Delivery note</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="LABEL" name="LABEL"  id="LABEL"  placeholder="Name of quote" required>
                        </div>
                        @error('LABEL') <span class="text-danger">{{ $message }}<br/></span>@enderror
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
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('CODE')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'CODE'])</a>
                            </th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('LABEL')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'LABEL'])</a>
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
                                <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $InvoicesRequestsLine->orderLine->order['id'] ]) }}">
                                    <i class="fas fa-folder"></i>
                                    View
                                </a>
                                {{ $InvoicesRequestsLine->orderLine->order['CODE'] }}
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('delivery.show', ['id' => $InvoicesRequestsLine->deliverys_id ]) }}">
                                    <i class="fas fa-folder"></i>
                                    View
                                </a>
                                {{ $InvoicesRequestsLine->delivery['CODE'] }}
                            </td>
                            <td>{{ $InvoicesRequestsLine->orderLine['CODE'] }}</td>
                            <td>{{ $InvoicesRequestsLine->orderLine['LABEL'] }}</td>
                            <td>
                                {{ $InvoicesRequestsLine->qty }}
                            </td>
                            <td>{{ $InvoicesRequestsLine->orderLine->Unit['LABEL'] }}</td>
                            <td>{{ $InvoicesRequestsLine->orderLine['selling_price'] }}</td>
                            <td>{{ $InvoicesRequestsLine->orderLine['discount'] }} %</td>
                            <td>{{ $InvoicesRequestsLine->orderLine->VAT['LABEL'] }} %</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $InvoicesRequestsLine->id }}" wire:model="data.{{ $InvoicesRequestsLine->id }}.deliverys_id" id="data.{{ $InvoicesRequestsLine->id }}.deliverys_id"  type="checkbox">
                                    <label for="data.{{ $InvoicesRequestsLine->id }}.deliverys_id" class="custom-control-label">Add to new invoice</label>
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
                            <th>Delivery</th>
                            <th>CODE</th>
                            <th>LABEL</th>
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