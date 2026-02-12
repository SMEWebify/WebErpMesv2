@extends('adminlte::page')

@section('title', 'Pré-commandes importées')

@section('content_header')
    <h1>Pré-commandes importées</h1>
@stop

@section('content')
    <x-adminlte-card title="Pré-commandes" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fichier source</th>
                        <th>PDF source</th>
                        <th>Nb lignes</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preOrders as $preOrder)
                        <tr>
                            <td>{{ $preOrder->id }}</td>
                            <td>{{ $preOrder->importBatch?->file_name }}</td>
                            <td>{{ $preOrder->source_pdf }}</td>
                            <td>{{ $preOrder->lines_count }}</td>
                            <td>{{ number_format((float) $preOrder->computed_total_price, 2, ',', ' ') }}</td>
                            <td>
                                @if($preOrder->status === \App\Models\Workflow\PreOrder::STATUS_CONVERTED)
                                    <span class="badge badge-success">Convertie</span>
                                @else
                                    <span class="badge badge-warning">À traiter</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('pre-orders.show', $preOrder) }}" class="btn btn-xs btn-default text-primary">
                                    <i class="fa fa-lg fa-fw fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Aucune pré-commande importée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $preOrders->links() }}
        </div>
    </x-adminlte-card>
@stop
