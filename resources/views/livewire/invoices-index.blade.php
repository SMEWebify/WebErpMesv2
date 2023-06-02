
<div class="card-body">
    <div class="card">
        @include('include.search-card')
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
                        <th>Total price</th>
                        <th>Statu</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('created_at')" role="button" href="#">Created At @include('include.sort-icon', ['field' => 'created_at'])</a>
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($InvoicesList as $Invoice)
                    <tr>
                        <td>{{ $Invoice->code }}</td>
                        <td>{{ $Invoice->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $Invoice->companies_id }}" label="{{ $Invoice->companie['label'] }}"  />
                        </td>
                        <td>{{ $Invoice->invoice_lines_count }}</td>
                        <td>{{ $Invoice->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                        <td>
                            @if(1 == $Invoice->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $Invoice->statu )  <span class="badge badge-warning">Sent</span>@endif
                            @if(3 == $Invoice->statu )  <span class="badge badge-success">Invoiced</span>@endif
                            @if(4 == $Invoice->statu )  <span class="badge badge-danger">Partially invoiced</span>@endif
                        </td>
                        <td>{{ $Invoice->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('invoices.show', ['id' => $Invoice->id])}}" />
                            <x-ButtonTextPDF route="{{ route('pdf.invoice', ['Document' => $Invoice->id])}}" />
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="8" text="No Invoice found ..."  />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>Code</th>
                        <th>Label</th>
                        <th>Companie</th>
                        <th>Lines count</th>
                        <th>Total price</th>
                        <th>Statu</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $InvoicesList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>