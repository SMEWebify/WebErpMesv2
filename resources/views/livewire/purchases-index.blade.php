
<div class="card-body">
    <div class="card">
        @include('include.search-card')
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
                    @forelse ($PurchasesList as $Purchase)
                    <tr>
                        <td>{{ $Purchase->code }}</td>
                        <td>{{ $Purchase->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $Purchase->companies_id }}" label="{{ $Purchase->companie['label'] }}"  />
                        </td>
                        <td>{{ $Purchase->purchase_lines_count }}</td>
                        <td>
                            @if(1 == $Purchase->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $Purchase->statu )  <span class="badge badge-warning">Ordered</span>@endif
                            @if(3 == $Purchase->statu )  <span class="badge badge-success">Partly received</span>@endif
                            @if(4 == $Purchase->statu )  <span class="badge badge-danger">Received</span>@endif
                            @if(5 == $Purchase->statu )  <span class="badge badge-danger">Canceled</span>@endif
                        </td>
                        <td>{{ $Purchase->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('purchase.show', ['id' => $Purchase->id])}}" />
                            <x-ButtonTextPrint route="{{ route('print.purchase', ['Document' => $Purchase->id])}}" />
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
        {{ $PurchasesList->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>