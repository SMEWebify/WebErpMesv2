@extends('adminlte::page')

@section('title', __('nests.declare'))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h1 class="mb-0">{{ __('nests.declare') }}
            <small class="text-body-secondary">— {{ $Product->code }} {{ $Product->label }}</small>
        </h1>
        <a href="{{ route('products.stockline.show', ['id' => $StockLocationProduct->id]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>{{ __('nests.back') }}
        </a>
    </div>
@stop

@section('content')
    @include('include.alert-result')
    <p class="text-body-secondary">{{ __('nests.declare_hint') }}</p>

    <form method="POST" action="{{ route('products.nests.store') }}">
        @csrf
        <input type="hidden" name="stock_location_products_id" value="{{ $StockLocationProduct->id }}">
        <div class="row">
            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">{{ __('nests.nest') }}</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nest_name" class="form-label">{{ __('nests.name') }}</label>
                            <input type="text" class="form-control" id="nest_name" name="name" required maxlength="255"
                                   value="{{ old('name') }}" placeholder="{{ __('nests.name_placeholder') }}">
                        </div>
                        <div class="mb-3">
                            <label for="nest_piece" class="form-label">{{ __('stock_remnants.piece') }}</label>
                            <select class="form-select" id="nest_piece" name="piece" required>
                                @foreach ($Pieces as $piece)
                                <option value="{{ $piece['key'] }}" @selected(old('piece') === $piece['key'])>
                                    {{ $piece['is_remnant'] ? __('stock_remnants.remnant') : __('stock_remnants.full_piece') }}
                                    {{ $piece['format'] ?? __('stock_remnants.format_standard') }}
                                    @if($piece['batch_code'] ?? $piece['tracability']) — {{ $piece['batch_code'] ?? $piece['tracability'] }} @endif
                                    ({{ $piece['qty'] + 0 }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="nest_sheet_qty" class="form-label">{{ __('nests.sheet_qty') }}</label>
                                <input type="number" class="form-control" id="nest_sheet_qty" name="sheet_qty" required
                                       min="0.001" step="any" value="{{ old('sheet_qty', 1) }}">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="nest_usage" class="form-label">{{ __('nests.usage_percent') }}</label>
                                <input type="number" class="form-control" id="nest_usage" name="usage_percent"
                                       min="0" max="100" step="any" value="{{ old('usage_percent') }}">
                            </div>
                        </div>
                        <div class="form-text mt-n2 mb-3">{{ __('nests.sheet_qty_hint') }}</div>

                        <label class="form-label">{{ __('stock_remnants.remnants_title') }}</label>
                        <div class="form-text mt-0 mb-1">{{ __('stock_remnants.remnants_hint') }}</div>
                        @for ($i = 0; $i < 3; $i++)
                        <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text">{{ __('stock_remnants.remnant_n', ['n' => $i + 1]) }}</span>
                            <input type="number" class="form-control" name="remnants[{{ $i }}][x_size]" placeholder="X" min="0" step="any" aria-label="X" value="{{ old("remnants.$i.x_size") }}">
                            <span class="input-group-text">×</span>
                            <input type="number" class="form-control" name="remnants[{{ $i }}][y_size]" placeholder="Y" min="0" step="any" aria-label="Y" value="{{ old("remnants.$i.y_size") }}">
                            <input type="number" class="form-control" style="max-width: 4.5rem" name="remnants[{{ $i }}][qty]" placeholder="1" min="1" step="1" aria-label="{{ __('stock_remnants.remnant_qty') }}" value="{{ old("remnants.$i.qty") }}">
                        </div>
                        @endfor

                        <div class="mt-3">
                            <label for="nest_comment" class="form-label">{{ __('nests.comment') }}</label>
                            <textarea class="form-control" id="nest_comment" name="comment" rows="2" maxlength="2000">{{ old('comment') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">{{ __('nests.parts') }}</h3></div>
                    <div class="card-body table-responsive">
                        <p class="small text-body-secondary">{{ __('nests.parts_hint') }}</p>
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('nests.order') }}</th>
                                    <th>{{ __('nests.line') }}</th>
                                    <th>{{ __('nests.task') }}</th>
                                    <th style="width: 8rem">{{ __('nests.qty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($Tasks as $task)
                                <tr>
                                    <td>{{ $task->OrderLines?->order?->code }}</td>
                                    <td>{{ $task->OrderLines?->label }}
                                        <span class="small text-body-secondary">× {{ $task->OrderLines?->qty + 0 }}</span>
                                    </td>
                                    <td>#{{ $task->id }} {{ $task->label }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" name="parts[{{ $task->id }}]"
                                               min="0" step="any" value="{{ old('parts.' . $task->id) }}" aria-label="{{ __('nests.qty') }} #{{ $task->id }}">
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-body-secondary">{{ __('nests.no_candidates') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>{{ __('nests.submit') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop
