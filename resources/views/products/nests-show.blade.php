@extends('adminlte::page')

@section('title', __('nests.nest') . ' ' . $Nest->name)

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h1 class="mb-0">{{ __('nests.nest') }} {{ $Nest->name }}
            <small class="text-body-secondary">— {{ __('nests.statuses.' . $Nest->status) }}</small>
        </h1>
        <a href="{{ route('products.nests') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>{{ __('nests.title') }}
        </a>
    </div>
@stop

@section('content')
@include('include.alert-result')
@php
    $currency = $Factory->curency ?? 'EUR';
    $money = fn ($v) => \Illuminate\Support\Number::currency((float) $v, $currency, config('app.locale'));
@endphp
<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('nests.nest') }}</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">{{ __('nests.source') }}</dt>
                    <dd class="col-sm-7">{{ __('nests.sources.' . $Nest->source) }}@if($Nest->external_id) #{{ $Nest->external_id }}@endif</dd>
                    <dt class="col-sm-5">{{ __('nests.product') }}</dt>
                    <dd class="col-sm-7">
                        @if($Nest->product)
                            @can('products-menu')
                                <a href="{{ route('products.show', ['id' => $Nest->product->id]) }}">{{ $Nest->product->code }}</a>
                            @else
                                {{ $Nest->product->code }}
                            @endcan
                        @endif
                        {{ $Nest->material }} @if($Nest->thickness) {{ $Nest->thickness + 0 }} mm @endif
                    </dd>
                    <dt class="col-sm-5">{{ __('nests.format') }}</dt>
                    <dd class="col-sm-7">@if($Nest->sheet_x && $Nest->sheet_y){{ $Nest->sheet_x + 0 }} × {{ $Nest->sheet_y + 0 }}@endif</dd>
                    <dt class="col-sm-5">{{ __('nests.sheet_qty') }}</dt>
                    <dd class="col-sm-7">{{ $Nest->sheet_qty + 0 }}</dd>
                    @if($Nest->usage_percent !== null)
                    <dt class="col-sm-5">{{ __('nests.usage_percent') }}</dt>
                    <dd class="col-sm-7">{{ $Nest->usage_percent + 0 }} %</dd>
                    @endif
                    @if($Nest->machine)
                    <dt class="col-sm-5">{{ __('nests.machine') }}</dt>
                    <dd class="col-sm-7">{{ $Nest->machine }}</dd>
                    @endif
                    <dt class="col-sm-5">{{ __('nests.cut_at') }}</dt>
                    <dd class="col-sm-7">{{ $Nest->cut_at?->format('d/m/Y H:i') }}</dd>
                    @if($Nest->user)
                    <dt class="col-sm-5">{{ __('nests.user') }}</dt>
                    <dd class="col-sm-7">{{ $Nest->user->name }}</dd>
                    @endif
                    @if($Nest->comment)
                    <dt class="col-sm-5">{{ __('nests.comment') }}</dt>
                    <dd class="col-sm-7">{{ $Nest->comment }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('nests.cost_title') }}</h3></div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ __('nests.cost_total') }} : {{ $money($Allocation['cost']) }}</strong></p>
                <p class="small text-body-secondary mb-2">{{ __('nests.cost_hint') }} {{ __('nests.basis.' . $Allocation['basis']) }}.</p>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>{{ __('nests.order') }}</th><th class="text-end">{{ __('nests.share') }}</th><th class="text-end">{{ __('nests.cost') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($Allocation['lines'] as $line)
                        <tr>
                            <td>
                                @if($line['order_id'])
                                    @can('orders-menu')
                                        <a href="{{ route('orders.show', ['id' => $line['order_id']]) }}">{{ $line['order_code'] }}</a>
                                    @else
                                        {{ $line['order_code'] }}
                                    @endcan
                                    <span class="small text-body-secondary">{{ $line['label'] }}</span>
                                @else
                                    <span class="text-warning">{{ __('nests.unmatched') }}</span> {{ $line['label'] }}
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($line['share'] * 100, 1, ',', ' ') }} %</td>
                            <td class="text-end">{{ $money($line['cost']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('nests.parts') }}</h3></div>
            <div class="card-body table-responsive">
                @if($Nest->source === 'n2p' && $Nest->parts->isEmpty())
                    <p class="text-body-secondary mb-0">{{ __('nests.n2p_pending') }}</p>
                @else
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('nests.order') }}</th>
                            <th>{{ __('nests.part_ref') }}</th>
                            <th>{{ __('nests.task') }}</th>
                            <th class="text-end">{{ __('nests.qty') }}</th>
                            <th class="text-end">{{ __('nests.good_qty') }}</th>
                            <th class="text-end">{{ __('nests.bad_qty') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($Nest->parts as $part)
                        <tr>
                            <td>
                                @if($part->orderLine?->order)
                                    {{ $part->orderLine->order->code }}
                                    <span class="small text-body-secondary">{{ $part->orderLine->label }}</span>
                                @else
                                    <span class="text-warning">{{ __('nests.unmatched') }}</span>
                                @endif
                            </td>
                            <td>{{ $part->part_ref }}</td>
                            <td>
                                @if($part->task_id)
                                    @can('scheduling-menu')
                                        <a href="{{ route('production.task.statu.id', ['id' => $part->task_id]) }}">#{{ $part->task_id }}</a>
                                    @else
                                        #{{ $part->task_id }}
                                    @endcan
                                @endif
                            </td>
                            <td class="text-end">{{ $part->qty + 0 }}</td>
                            <td class="text-end">{{ $part->good_qty !== null ? $part->good_qty + 0 : '' }}</td>
                            <td class="text-end">{{ $part->bad_qty !== null ? $part->bad_qty + 0 : '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('nests.moves') }}</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach ($Nest->stockMoves as $move)
                        @php $isRemnant = $move->remnant_source_move_id !== null; @endphp
                        <tr>
                            <td class="fw-semibold {{ $isRemnant ? 'text-success' : 'text-danger' }}">
                                {{ $isRemnant ? '+' : '−' }}{{ $move->qty + 0 }}
                            </td>
                            <td>{{ $isRemnant ? __('nests.move_remnant') : __('nests.move_exit') }}
                                @if($move->x_size && $move->y_size)<span class="small text-body-secondary">{{ $move->x_size + 0 }} × {{ $move->y_size + 0 }}</span>@endif
                            </td>
                            <td>{{ $move->batch?->heat_number ? __('batches.heat_short') . ' ' . $move->batch->heat_number : $move->tracability }}</td>
                            <td>
                                <a href="{{ route('products.stockline.show', ['id' => $move->stock_location_products_id]) }}">{{ $move->StockLocationProducts?->code }}</a>
                            </td>
                            <td class="text-end">{{ $move->formatted_component_price }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
