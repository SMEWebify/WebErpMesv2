
<div class="card-body">
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalOrder" tabindex="-1" role="dialog" aria-labelledby="ModalOrderTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalOrderTitle">New Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        @csrf
                        <div class="row">
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
                                <label for="LABEL">Name of order</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <input type="text" class="form-control" wire:model="LABEL" name="LABEL"  id="LABEL" placeholder="Name of order" required>
                                    @error('LABEL') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="col-3">
                                <label for="user_id">User management</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <select class="form-control" wire:model="user_id" name="user_id" id="user_id">
                                    @foreach ($userSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                    </select>
                                </div>
                                @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <label for="InputWebSite">Customer information</label>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-5">
                                <label for="companies_id">Companie</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    </div>
                                    <select class="form-control" wire:model="companies_id" name="companies_id" id="companies_id">
                                    @forelse ($CompanieSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                                    @empty
                                        <option value="">No company, please add</option>
                                    @endforelse
                                    </select>
                                </div>
                                @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-5">
                                <label for="customer_reference">Customer reference</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    </div>
                                    <input type="text" class="form-control" wire:model="customer_reference"  name="customer_reference"  id="customer_reference" placeholder="Customer reference">
                                    @error('customer_reference') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-5">
                                <label for="companies_addresses_id">Adress</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                    </div>
                                    <select class="form-control" wire:model="companies_addresses_id" name="companies_addresses_id" id="companies_addresses_id">
                                    @forelse ($AddressSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->LABEL }} - {{ $item->ADRESS }}</option>
                                    @empty
                                        <option value="">No address, please add</option>
                                    @endforelse
                                    </select>
                                </div>
                                @error('companies_addresses_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-5">
                                <label for="companies_contacts_id">Contact</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <select class="form-control" wire:model="companies_contacts_id" name="companies_contacts_id" id="companies_contacts_id">
                                    @forelse ($ContactSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->FIRST_NAME }} - {{ $item->NAME }}</option>
                                    @empty
                                        <option value="">No contact, please add</option>
                                    @endforelse
                                    </select>
                                </div>
                                @error('companies_contacts_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <label for="InputWebSite">Date & Payment information</label>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-5">
                                <label for="accounting_payment_conditions_id">Payment condition</label>
                                <select class="form-control" wire:model="accounting_payment_conditions_id"  name="accounting_payment_conditions_id" id="accounting_payment_conditions_id">
                                    @forelse ($AccountingConditionSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                                    @empty
                                        <option value="">No payment conditions, please add in accounting page</option>
                                    @endforelse
                                </select>
                                @error('accounting_payment_conditions_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-5">
                                <label for="accounting_payment_methods_id">Payment methods</label>
                                <select class="form-control" wire:model="accounting_payment_methods_id" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                                    @forelse ($AccountingMethodsSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                                    @empty
                                        <option value="">No payment methods, please add in accounting page</option>
                                    @endforelse
                                </select>
                                @error('accounting_payment_methods_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-5">
                                <label for="accounting_deliveries_id">Delevery method</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                    </div>
                                    <select class="form-control" wire:model="accounting_deliveries_id" name="accounting_deliveries_id" id="accounting_deliveries_id">
                                    @forelse ($AccountingDeleveriesSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                                    @empty
                                        <option value="">No delivery type, please add in accounting page</option>
                                    @endforelse
                                    </select>
                                </div>
                                @error('accounting_deliveries_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                            <div class="col-5">
                                <label for="LABEL">Validity date</label>
                                <input type="date" class="form-control" wire:model="validity_date"  name="validity_date"  id="validity_date">
                                @error('validity_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-10">
                                <label>Comment</label>
                                <textarea class="form-control" rows="3" wire:model="comment" name="comment"  placeholder=" ..."></textarea>
                                @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="Submit" wire:click.prevent="storeOrder()" class="btn btn-success">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <div class="card">
        <div class="card-body">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                </div>
                <input type="text" class="form-control" wire:model="search" placeholder="Search line">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('CODE')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'CODE'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('LABEL')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'LABEL'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')" role="button" href="#">Companie @include('include.sort-icon', ['field' => 'companies_id'])</a>
                        </th>
                        <th>Customer reference</th>
                        <th>Lines count</th>
                        <th>Statu</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Orderslist as $Order)
                    <tr>
                        <td>{{ $Order->CODE }}</td>
                        <td>{{ $Order->LABEL }}</td>
                        <td>{{ $Order->companie['LABEL'] }}</td>
                        <td>{{ $Order->customer_reference }}</td>
                        <td>{{ $Order->order_lines_count }}</td>
                        <td>
                            @if(1 == $Order->statu )   <span class="badge badge-info"> Open</span>@endif
                            @if(2 == $Order->statu )  <span class="badge badge-warning">Send</span>@endif
                            @if(3 == $Order->statu )  <span class="badge badge-success">Win</span>@endif
                            @if(4 == $Order->statu )  <span class="badge badge-danger">Lost</span>@endif
                            @if(5 == $Order->statu )  <span class="badge badge-secondary">Closed</span>@endif
                            @if(6 == $Order->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                        </td>
                        <td>{{ $Order->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $Order->id])}}">
                                <i class="fas fa-folder"></i>
                                View
                            </a>
                            <a class="btn btn-success btn-sm" href="{{ route('order.print', ['id' => $Order->id])}}">
                                <i class="fas fa-print"></i>
                                Print
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Code</th>
                        <th>Label</th>
                        <th>Companie</th>
                        <th>Customer reference</th>
                        <th>Lines count</th>
                        <th>Statu</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $Orderslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>