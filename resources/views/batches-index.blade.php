@extends('adminlte::page')

@section('title', __('Batches'))

@section('content_header')
    <h1>{{ __('Batches') }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Production Date') }}</th>
                        <th>{{ __('Expiration Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batcheslist as $batch)
                        <tr>
                            <td>{{ $batch->code }}</td>
                            <td>{{ $batch->product->label ?? '' }}</td>
                            <td>{{ $batch->production_date }}</td>
                            <td>{{ $batch->expiration_date }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">{{ __('No batches found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $batcheslist->links() }}
        </div>
    </div>
@stop
