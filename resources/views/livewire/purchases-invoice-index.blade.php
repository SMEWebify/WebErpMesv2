
<div class="card-body">
    <div class="card">
        @include('include.search-card')
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
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
                    @forelse ($PurchasesInvoiceList as $PurchasesInvoice)
                    <tr>
                        <td>{{ $PurchasesInvoice->code }}</td>
                        <td>{{ $PurchasesInvoice->label }}</td>
                        <td>{{ $PurchasesInvoice->companie['label'] }}</td>
                        <td>{{ $PurchasesInvoice->purchase_invoice_lines_count }}</td>
                        <td>
                            @if(1 == $PurchasesInvoice->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $PurchasesInvoice->statu )  <span class="badge badge-warning">To be posted</span>@endif
                            @if(3 == $PurchasesInvoice->statu )  <span class="badge badge-success">Close</span>@endif
                        </td>
                        <td>{{ $PurchasesInvoice->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('purchase.invoice.show', ['id' => $PurchasesInvoice->id])}}" />
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="8" text="No Purchase found ..."  />
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
        {{ $PurchasesInvoiceList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>