
<div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
        </div>
        <div class="table-responsive p-0">
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
                            <x-ButtonTextView route="{{ route('purchase.quotation.show', ['id' => $PurchaseQuotation->id])}}" />
                            @if( $PurchaseQuotation->companies_contacts_id != 0 & $PurchaseQuotation->companies_addresses_id !=0)
                            <x-ButtonTextPDF route="{{ route('pdf.purchase.quotation', ['Document' => $PurchaseQuotation->id])}}" />
                            @endif
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="8" text=" No Purchase quotation found ..."  />
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