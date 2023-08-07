<div class="card-body">
    <div class="card">
        @include('include.alert-result')
            <div class="card-body">
                <form>
                    @csrf
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
                                <button type="Submit" wire:click.prevent="storeInvoice()" class="btn btn-success btn-block">New Invoice document</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Purchase order</th>
                            <th>Purchase receipt</th>
                            <th>Supplier</th>
                            <th>Description</th>
                            <th>Qty receipt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($PurchasesWaintingInvoiceLineslist as $PurchasesWaintingInvoiceLine)
                        <tr>
                            <td>
                                <x-OrderButton id="{{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->OrderLines->orders_id }}" code="{{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->OrderLines->order->code }}"  />
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('purchase.show', ['id' => $PurchasesWaintingInvoiceLine->purchaseLines->purchase->id])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $PurchasesWaintingInvoiceLine->purchaseLines->purchase->code }}
                                </a>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipt.show', ['id' => $PurchasesWaintingInvoiceLine->purchase_receipt_id])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $PurchasesWaintingInvoiceLine->purchaseReceipt->code }}
                                </a>
                            </td>
                            <td>
                                {{ $PurchasesWaintingInvoiceLine->purchaseReceipt->companie->code }} - {{ $PurchasesWaintingInvoiceLine->purchaseReceipt->companie->label }}
                            </td>
                            <td>#{{ $PurchasesWaintingInvoiceLine->purchaseLines->tasks->id }} {{ $PurchasesWaintingInvoiceLine->code }} {{ $PurchasesWaintingInvoiceLine->label }}</td>
                            <td>{{ $PurchasesWaintingInvoiceLine->receipt_qty }}</td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $PurchasesWaintingInvoiceLine->id }}" wire:model="data.{{ $PurchasesWaintingInvoiceLine->id }}.purchase_receipt_line_id" id="data.{{ $PurchasesWaintingInvoiceLine->id }}.purchase_receipt_line_id"  type="checkbox">
                                    <label for="data.{{ $PurchasesWaintingInvoiceLine->id }}.purchase_receipt_line_id" class="custom-control-label">Add to new document </label>
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
                            <th>Purchase order</th>
                            <th>Purchase receipt</th>
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