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
                            <label for="code">Delivery note number</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                </div>
                                <input type="text" class="form-control" wire:model="deliveryNoteNumber" name="delivery_note_number" id="delivery_note_number" placeholder="Delivery note number">
                            </div>
                            @error('delivery_note_number') <span class="text-danger">{{ $message }}<br/></span>@enderror
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
                                <button type="Submit" wire:click.prevent="storeReciep()" class="btn btn-success">New reciept document</button>
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
                            <th>Purchase order</th>
                            <th>Supplier</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($PurchasesWaintingRecieptLineslist as $PurchasesWaintingRecieptLine)
                        <tr>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $PurchasesWaintingRecieptLine->tasks->OrderLines->orders_id])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $PurchasesWaintingRecieptLine->tasks->OrderLines->order->code }}
                                </a>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('purchase.show', ['id' => $PurchasesWaintingRecieptLine->purchases_id])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $PurchasesWaintingRecieptLine->purchase->code }}
                                </a>
                            </td>
                            <td>
                                {{ $PurchasesWaintingRecieptLine->purchase->companie->code }} - {{ $PurchasesWaintingRecieptLine->purchase->companie->label }}
                            </td>
                            <td>#{{ $PurchasesWaintingRecieptLine->tasks->id }} {{ $PurchasesWaintingRecieptLine->code }} {{ $PurchasesWaintingRecieptLine->label }}</td>
                            <td>{{ $PurchasesWaintingRecieptLine->qty }}</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $PurchasesWaintingRecieptLine->id }}" wire:model="data.{{ $PurchasesWaintingRecieptLine->id }}.purchase_line_id" id="data.{{ $PurchasesWaintingRecieptLine->id }}.purchase_line_id"  type="checkbox">
                                    <label for="data.{{ $PurchasesWaintingRecieptLine->id }}.purchase_line_id" class="custom-control-label">Add to new document </label>
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
                            <th>Purchase order</th>
                            <th>Supplier</th>
                            <th>Description</th>
                            <th>Qty</th>
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