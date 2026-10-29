@extends('adminlte::page')

@section('title', __('batches.title'))

@section('content_header')
    <h1>{{ __('batches.title') }}</h1>
@stop

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <form method="GET" action="{{ route('products.batches') }}" class="d-flex flex-wrap gap-2">
                <input type="search" name="search" value="{{ $search }}" class="form-control"
                       style="max-width: 420px" placeholder="{{ __('batches.search_placeholder') }}"
                       aria-label="{{ __('batches.search_placeholder') }}">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>{{ __('batches.search') }}</button>
                @if($search !== '')
                    <a href="{{ route('products.batches') }}" class="btn btn-outline-secondary">{{ __('batches.reset') }}</a>
                @endif
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('batches.code') }}</th>
                        <th>{{ __('batches.product') }}</th>
                        <th>{{ __('batches.heat_number') }}</th>
                        <th>{{ __('batches.supplier_batch_code') }}</th>
                        <th>{{ __('batches.certificate_type') }}</th>
                        <th>{{ __('batches.supplier') }}</th>
                        <th>{{ __('batches.receipt') }}</th>
                        <th>{{ __('batches.production_date') }}</th>
                        <th>{{ __('batches.expiration_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batcheslist as $batch)
                        <tr>
                            <td>{{ $batch->code }}</td>
                            <td>{{ $batch->product->label ?? '' }}</td>
                            <td>{{ $batch->heat_number }}</td>
                            <td>{{ $batch->supplier_batch_code }}</td>
                            <td>
                                {{ $batch->certificate_type }}
                                @if($batch->certificates_count > 0)
                                    <i class="fa-solid fa-file-circle-check text-success ms-1" title="{{ __('batches.certificate_attached') }}"></i>
                                @endif
                            </td>
                            <td>{{ $batch->supplier->label ?? '' }}</td>
                            <td>{{ $batch->purchaseReceiptLine?->purchaseReceipt?->code }}</td>
                            <td>{{ $batch->production_date }}</td>
                            <td>{{ $batch->expiration_date }}</td>
                            <td>
                                <a href="{{ route('products.batches.show', ['batch' => $batch->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('batches.view') }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">{{ __('batches.no_batches') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $batcheslist->links() }}
        </div>
    </div>
@stop
