
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
                    @forelse ($Deliveryslist as $Delivery)
                    <tr>
                        <td>{{ $Delivery->code }}</td>
                        <td>{{ $Delivery->label }}</td>
                        <td>
                            <x-CompanieButton id="{{ $Delivery->companies_id }}" label="{{ $Delivery->companie['label'] }}"  />
                        </td>
                        <td>{{ $Delivery->delivery_lines_count }}</td>
                        <td>
                            @if(1 == $Delivery->statu )  <span class="badge badge-info">In progress</span>@endif
                            @if(2 == $Delivery->statu )  <span class="badge badge-success">Sent</span>@endif
                        </td>
                        <td>{{ $Delivery->GetPrettyCreatedAttribute() }}</td>
                        <td>
                            <x-ButtonTextView route="{{ route('deliverys.show', ['id' => $Delivery->id])}}" />
                            <x-ButtonTextPrint route="{{ route('print.delivery', ['Document' => $Delivery->id])}}" />
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="8" text="No Delivery found ..."  />
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