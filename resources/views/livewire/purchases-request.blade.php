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
            <div class="card-body">
                <form>
                    @csrf
                    <div class="row">
                        <div class="card-header">
                            <label for="companies_id">Document type</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <select class="form-control"  wire:click.prevent="changeDocument()" wire:model="document_type" name="document_type" id="document_type">
                                    <option value="">Select your document</option>
                                    <option value="PU">Purchase order</option>
                                    <option value="PQ">Purchase quotation</option>
                                </select>
                            </div>
                            @error('document_type') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                    </div>
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
                        <div class="col-2">
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
                        <div class="card-footer">
                            <div class="input-group">
                                <button type="Submit" wire:click.prevent="storePurchase()" class="btn btn-success">New purchase document</button>
                            </div>
                        </div>
                    </div>
                </form>
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
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Service</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($PurchasesRequestsLineslist as $PurchasesRequestsLines)
                        <tr>
                            <td>
                                <x-OrderButton id="{{ $PurchasesRequestsLines->OrderLines->order->id }}" code="{{ $PurchasesRequestsLines->OrderLines->order->code }}"  />
                            </td>
                            <td>{{ $PurchasesRequestsLines->ORDER }}</td>
                            <td>{{ $PurchasesRequestsLines->id }} - {{ $PurchasesRequestsLines->label }}</td>
                            <td>@if($PurchasesRequestsLines->component_id ) {{ $PurchasesRequestsLines->Component['label'] }}@endif</td>
                            <td>{{ $PurchasesRequestsLines->qty }}</td>
                            <td>@if($PurchasesRequestsLines->methods_services_id ) {{ $PurchasesRequestsLines->service['label'] }}@endif</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $PurchasesRequestsLines->id }}" wire:model="data.{{ $PurchasesRequestsLines->id }}.task_id" id="data.{{ $PurchasesRequestsLines->id }}.task_id"  type="checkbox">
                                    <label for="data.{{ $PurchasesRequestsLines->id }}.task_id" class="custom-control-label">Add to new document </label>
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
                            <th>label</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Service</th>
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