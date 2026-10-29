@extends('adminlte::page')

@section('title', __('batches.batch') . ' ' . $Batch->code)

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h1 class="mb-0">
            {{ __('batches.batch') }} {{ $Batch->code }}
            @if($Batch->heat_number)
                <small class="text-body-secondary">— {{ __('batches.heat_short') }} {{ $Batch->heat_number }}</small>
            @endif
        </h1>
        <a href="{{ route('products.batches') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>{{ __('batches.title') }}
        </a>
    </div>
@stop

@section('content')
@php
    $receiptLine = $Batch->purchaseReceiptLine;
    $receipt = $receiptLine?->purchaseReceipt;
    $purchase = $receiptLine?->purchaseLines?->purchase;
    $incoming = [1, 3, 5, 12, 14];
@endphp
<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('batches.identity') }}</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">{{ __('batches.product') }}</dt>
                    <dd class="col-sm-7">
                        @if($Batch->product)
                            @can('products-menu')
                                <a href="{{ route('products.show', ['id' => $Batch->product->id]) }}">{{ $Batch->product->code }}</a>
                            @else
                                {{ $Batch->product->code }}
                            @endcan
                            — {{ $Batch->product->label }}
                        @else
                            {{ __('batches.none') }}
                        @endif
                    </dd>

                    <dt class="col-sm-5">{{ __('batches.heat_number') }}</dt>
                    <dd class="col-sm-7">{{ $Batch->heat_number ?? __('batches.none') }}</dd>

                    <dt class="col-sm-5">{{ __('batches.supplier_batch_code') }}</dt>
                    <dd class="col-sm-7">{{ $Batch->supplier_batch_code ?? __('batches.none') }}</dd>

                    <dt class="col-sm-5">{{ __('batches.certificate_type') }}</dt>
                    <dd class="col-sm-7">{{ $Batch->certificate_type ?? __('batches.none') }}</dd>

                    <dt class="col-sm-5">{{ __('batches.supplier') }}</dt>
                    <dd class="col-sm-7">{{ $Batch->supplier?->label ?? __('batches.none') }}</dd>

                    <dt class="col-sm-5">{{ __('batches.receipt') }}</dt>
                    <dd class="col-sm-7">
                        @if($receipt)
                            @can('purchases-menu')
                                <a href="{{ route('purchase.receipts.show', ['id' => $receipt->id]) }}">{{ $receipt->code }}</a>
                            @else
                                {{ $receipt->code }}
                            @endcan
                        @else
                            {{ __('batches.none') }}
                        @endif
                    </dd>

                    <dt class="col-sm-5">{{ __('batches.purchase') }}</dt>
                    <dd class="col-sm-7">
                        @if($purchase)
                            @can('purchases-menu')
                                <a href="{{ route('purchases.show', ['id' => $purchase->id]) }}">{{ $purchase->code }}</a>
                            @else
                                {{ $purchase->code }}
                            @endcan
                        @else
                            {{ __('batches.none') }}
                        @endif
                    </dd>

                    <dt class="col-sm-5">{{ __('batches.production_date') }}</dt>
                    <dd class="col-sm-7">{{ $Batch->production_date ?? __('batches.none') }}</dd>

                    <dt class="col-sm-5">{{ __('batches.expiration_date') }}</dt>
                    <dd class="col-sm-7">{{ $Batch->expiration_date ?? __('batches.none') }}</dd>

                    <dt class="col-sm-5"></dt>
                    <dd class="col-sm-7 mb-0">
                        @if($Batch->closed_at)
                            <span class="badge text-bg-secondary">{{ __('batches.closed') }}</span>
                        @else
                            <span class="badge text-bg-success">{{ __('batches.open') }}</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('batches.documents') }}</h3></div>
            <div class="card-body">
                <p class="text-body-secondary small">{{ __('batches.documents_hint') }}</p>
                @include('include.file-manager-mount', [
                    'fileableType' => 'batch',
                    'fileableId' => $Batch->id,
                    'fileRoles' => $fileRoles,
                ])
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">{{ __('batches.moves') }}</h3></div>
    <div class="card-body table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>{{ __('batches.date') }}</th>
                    <th>{{ __('batches.user') }}</th>
                    <th></th>
                    <th class="text-end">{{ __('batches.qty') }}</th>
                    <th>{{ __('batches.location') }}</th>
                    <th>{{ __('batches.task') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($Batch->stockMoves as $move)
                    <tr>
                        <td>{{ $move->GetPrettyCreatedAttribute() }}</td>
                        <td>{{ $move->UserManagement?->name }}</td>
                        <td>
                            @if(in_array((int) $move->typ_move, $incoming, true))
                                <span class="badge text-bg-success">{{ __('batches.move_in') }}</span>
                            @else
                                <span class="badge text-bg-warning">{{ __('batches.move_out') }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $move->qty }}</td>
                        <td>
                            @if($move->StockLocationProducts)
                                <a href="{{ route('products.stockline.show', ['id' => $move->stock_location_products_id]) }}">{{ $move->StockLocationProducts->code }}</a>
                            @endif
                        </td>
                        <td>
                            @if($move->task_id)
                                @can('scheduling-menu')
                                    <a href="{{ route('production.task.statu.id', ['id' => $move->task_id]) }}">#{{ $move->task_id }} {{ $move->Task?->label }}</a>
                                @else
                                    #{{ $move->task_id }} {{ $move->Task?->label }}
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-body-secondary">{{ __('batches.no_moves') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
