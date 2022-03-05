
<div class="card-body">
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
                        <th>Lines count</th>
                        <th>Statu</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">Created At @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($PurchasesQuotationList as $PurchaseQuotation)
                    <tr>
                        <td>{{ $PurchaseQuotation->code }}</td>
                        <td>{{ $PurchaseQuotation->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $PurchaseQuotation->companies_id }}" label="{{ $PurchaseQuotation->companie['label'] }}"  />
                        </td>
                        <td>{{ $PurchaseQuotation->purchase_quotation_lines_count }}</td>
                        <td>
                            @if(1 == $PurchaseQuotation->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $PurchaseQuotation->statu )  <span class="badge badge-warning">Sent</span>@endif
                            @if(3 == $PurchaseQuotation->statu )  <span class="badge badge-success">Partly received</span>@endif
                            @if(4 == $PurchaseQuotation->statu )  <span class="badge badge-danger">Received</span>@endif
                            @if(5 == $PurchaseQuotation->statu )  <span class="badge badge-danger">PO partly created</span>@endif
                            @if(5 == $PurchaseQuotation->statu )  <span class="badge badge-danger">PO Created</span>@endif
                        </td>
                        <td>{{ $PurchaseQuotation->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('purchase.quotation.show', ['id' => $PurchaseQuotation->id])}}">
                                <i class="fas fa-folder"></i>
                                View
                            </a>
                            <a class="btn btn-success btn-sm" href="{{ route('invoice.print', ['id' => $PurchaseQuotation->id])}}">
                                <i class="fas fa-print"></i>
                                Print
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="flex justify-center items-center">
                                <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No Purchase quotation found ...</span>
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
                        <th>Lines count</th>
                        <th>Statu</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $PurchasesQuotationList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>