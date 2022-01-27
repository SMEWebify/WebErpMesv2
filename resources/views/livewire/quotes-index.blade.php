
<div class="card-body">
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="ModalQuote" tabindex="-1" role="dialog" aria-labelledby="ModalQuoteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalQuoteTitle">New Quote</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                    @csrf
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-3">
                                    <label for="code">External ID</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control" wire:model="code"  name="code" id="code" placeholder="External ID" >
                                    </div>
                                    @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-3">
                                    <label for="label">Name of quote</label>
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
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">Customer information</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-5">
                                    <label for="companies_id">Company</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        </div>
                                        <select class="form-control" wire:model="companies_id" name="companies_id" id="companies_id">
                                            <option value="">Select company</option>
                                            @forelse ($CompanieSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
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
                                        <input type="text" class="form-control" wire:model="customer_reference" name="customer_reference"  id="customer_reference" placeholder="Customer reference">
                                    </div>
                                    @error('customer_reference') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5">
                                    <label for="companies_addresses_id">Address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                        </div>
                                        <select class="form-control" wire:model="companies_addresses_id"  name="companies_addresses_id" id="companies_addresses_id">
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
                                <div class="col-5">
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
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <label for="InputWebSite">Date & Payment information</label>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-5">
                                    <label for="accounting_payment_conditions_id">Payment condition</label>
                                    <select class="form-control" wire:model="accounting_payment_conditions_id" name="accounting_payment_conditions_id" id="accounting_payment_conditions_id">
                                        <option value="">Select payement condition</option>
                                        @forelse ($AccountingConditionSelect as $item)
                                        <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                            <option value="">No payment conditions, please add in accounting page</option>
                                        @endforelse
                                    </select>
                                    @error('accounting_payment_conditions_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-5">
                                    <label for="accounting_payment_methods_id">Payment methods</label>
                                    <select class="form-control" wire:model="accounting_payment_methods_id" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                                        <option value="">Select payment methods</option>
                                        @forelse ($AccountingMethodsSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
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
                                            <option value="">Select deliveries</option>
                                        @forelse ($AccountingDeleveriesSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @empty
                                            <option value="">No delivery type, please add in accounting page</option>
                                        @endforelse
                                        </select>
                                    </div>
                                    @error('accounting_deliveries_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="col-5">
                                    <label for="label">Validity date</label>
                                    <input type="date" class="form-control" wire:model="validity_date" name="validity_date"  id="validity_date">
                                    @error('validity_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-10">
                                    <label>Comment</label>
                                    <textarea class="form-control" rows="3" wire:model="comment" name="comment"  placeholder="..."></textarea>
                                    @error('comment') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="Submit" wire:click.prevent="storeQuote()" class="btn btn-success">Add</button>
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
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'code'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('companies_id')" role="button" href="#">Companie @include('include.sort-icon', ['field' => 'companies_id'])</a>
                        </th>
                        <th>Customer reference</th>
                        <th>Lines count</th>
                        <th>Statu</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">Created At @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($Quoteslist as $Quote)
                    <tr>
                        <td>{{ $Quote->code }}</td>
                        <td>{{ $Quote->label }}</td>
                        <td>{{ $Quote->companie['label'] }}</td>
                        <td>{{ $Quote->customer_reference }}</td>
                        <td>{{ $Quote->quote_lines_count }}</td>
                        <td>
                            @if(1 == $Quote->statu )   <span class="badge badge-info"> Open</span>@endif
                            @if(2 == $Quote->statu )  <span class="badge badge-warning">Send</span>@endif
                            @if(3 == $Quote->statu )  <span class="badge badge-success">Win</span>@endif
                            @if(4 == $Quote->statu )  <span class="badge badge-danger">Lost</span>@endif
                            @if(5 == $Quote->statu )  <span class="badge badge-secondary">Closed</span>@endif
                            @if(6 == $Quote->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                        </td>
                        <td>{{ $Quote->GetPrettyCreatedAttribute() }}</td>
                        <td>
                        <a class="btn btn-primary btn-sm" href="{{ route('quote.show', ['id' => $Quote->id])}}">
                            <i class="fas fa-folder"></i>
                            View
                        </a>
                        <a class="btn btn-success btn-sm" href="{{ route('quote.print', ['id' => $Quote->id])}}">
                            <i class="fas fa-print"></i>
                            Print
                        </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="flex justify-center items-center">
                                <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No quotes found ...</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
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
        {{ $Quoteslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>
