@extends('adminlte::page')

@section('title', $guide->title.' — '.__('guides.title'))

@section('content_header')
    <div class="guide-actions d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <a href="{{ route('guides.index') }}" class="small text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>{{ __('guides.back') }}
            </a>
            <h1 class="mb-1 mt-1">{{ $guide->title }}</h1>
            <div class="small text-body-secondary d-flex flex-wrap gap-3">
                <span><i class="fas fa-folder me-1"></i>{{ $guide->category }}</span>
                @if ($guide->duration)
                    <span><i class="far fa-clock me-1"></i>{{ __('guides.minutes', ['count' => $guide->duration]) }}</span>
                @endif
                @if ($guide->updated)
                    <span><i class="far fa-calendar me-1"></i>{{ __('guides.updated', ['date' => \Illuminate\Support\Carbon::parse($guide->updated)->translatedFormat('j F Y')]) }}</span>
                @endif
            </div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i>{{ __('guides.print') }}
        </button>
    </div>
@stop

@section('content')
    <div class="row g-3">
        <div class="col-lg-9">
            <div class="card mb-3">
                <article class="card-body guide-content">
                    {!! $html !!}
                </article>
            </div>
        </div>
        @if (count($toc) > 1)
            <aside class="col-lg-3 d-none d-lg-block guide-toc">
                <nav class="card mb-3 guide-toc__card">
                    <div class="card-header fw-semibold">{{ __('guides.contents') }}</div>
                    <ul class="nav flex-column py-2">
                        @foreach ($toc as $entry)
                            <li class="nav-item">
                                <a class="nav-link py-1" href="#{{ $entry['id'] }}">{{ $entry['text'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif
    </div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
