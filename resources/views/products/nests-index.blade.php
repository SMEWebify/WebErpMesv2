@extends('adminlte::page')

@section('title', __('nests.title'))

@section('content_header')
    <h1>{{ __('nests.title') }}</h1>
@stop

@section('content')
    @include('include.alert-result')
    <p class="text-body-secondary">{{ __('nests.intro') }}</p>
    <div class="card mb-3">
        <div class="card-header">
            <form method="GET" action="{{ route('products.nests') }}" class="d-flex flex-wrap gap-2">
                <input type="search" name="search" value="{{ $search }}" class="form-control"
                       style="max-width: 420px" placeholder="{{ __('nests.search_placeholder') }}"
                       aria-label="{{ __('nests.search_placeholder') }}">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>{{ __('nests.search') }}</button>
                @if($search !== '')
                    <a href="{{ route('products.nests') }}" class="btn btn-outline-secondary">{{ __('nests.reset') }}</a>
                @endif
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('nests.cut_at') }}</th>
                        <th>{{ __('nests.name') }}</th>
                        <th>{{ __('nests.source') }}</th>
                        <th>{{ __('nests.product') }}</th>
                        <th class="text-end">{{ __('nests.sheet_qty') }}</th>
                        <th class="text-end">{{ __('nests.parts_count') }}</th>
                        <th>{{ __('nests.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nests as $nest)
                        <tr>
                            <td>{{ ($nest->cut_at ?? $nest->created_at)?->format('d/m/Y H:i') }}</td>
                            <td>{{ $nest->name }}</td>
                            <td>{{ __('nests.sources.' . $nest->source) }}</td>
                            <td>{{ $nest->product?->code }}</td>
                            <td class="text-end">{{ $nest->sheet_qty + 0 }}</td>
                            <td class="text-end">{{ $nest->parts_count }}</td>
                            <td>{{ __('nests.statuses.' . $nest->status) }}</td>
                            <td>
                                <a href="{{ route('products.nests.show', ['nest' => $nest->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('nests.nest') }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">{{ __('nests.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $nests->links() }}
        </div>
    </div>
@stop
