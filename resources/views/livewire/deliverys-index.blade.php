
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
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($Deliveryslist as $Delivery)
                    <tr>
                        <td>{{ $Delivery->CODE }}</td>
                        <td>{{ $Delivery->LABEL }}</td>
                        <td>{{ $Delivery->companie['LABEL'] }}</td>
                        <td>{{ $Delivery->delivery_lines_count }}</td>
                        <td>
                            @if(1 == $Delivery->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $Delivery->statu )  <span class="badge badge-success">Sent</span>@endif
                        </td>
                        <td>{{ $Delivery->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('delivery.show', ['id' => $Delivery->id])}}">
                                <i class="fas fa-folder"></i>
                                View
                            </a>
                            <a class="btn btn-success btn-sm" href="{{ route('delivery.print', ['id' => $Delivery->id])}}">
                                <i class="fas fa-print"></i>
                                Print
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="flex justify-center items-center">
                                <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No Delivery found ...</span>
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
        {{ $Deliveryslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>