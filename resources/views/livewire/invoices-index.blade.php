
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
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('CODE')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'CODE'])</a>
                        </th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('LABEL')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'LABEL'])</a>
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
                    @forelse ($InvoicesList as $Invoice)
                    <tr>
                        <td>{{ $Invoice->CODE }}</td>
                        <td>{{ $Invoice->LABEL }}</td>
                        <td>{{ $Invoice->companie['LABEL'] }}</td>
                        <td>{{ $Invoice->invoice_lines_count }}</td>
                        <td>
                            @if(1 == $Invoice->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $Invoice->statu )  <span class="badge badge-warning">Sent</span>@endif
                            @if(3 == $Invoice->statu )  <span class="badge badge-success">Invoiced</span>@endif
                            @if(4 == $Invoice->statu )  <span class="badge badge-danger">Partially invoiced</span>@endif
                        </td>
                        <td>{{ $Invoice->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('invoice.show', ['id' => $Invoice->id])}}">
                                <i class="fas fa-folder"></i>
                                View
                            </a>
                            <a class="btn btn-success btn-sm" href="{{ route('invoice.print', ['id' => $Invoice->id])}}">
                                <i class="fas fa-print"></i>
                                Print
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="flex justify-center items-center">
                                <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No Invoice found ...</span>
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
        {{ $InvoicesList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>