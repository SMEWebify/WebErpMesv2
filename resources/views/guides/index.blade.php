@extends('adminlte::page')

@section('title', __('guides.title'))

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="mb-0">{{ __('guides.title') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('guides.intro') }}</p>
        </div>
        @if ($categories->isNotEmpty())
            <div class="guide-search">
                <i class="fas fa-search guide-search__icon"></i>
                <input type="search" id="guide-search" class="form-control" placeholder="{{ __('guides.search') }}" autocomplete="off">
            </div>
        @endif
    </div>
@stop

@section('content')
    @if ($categories->isEmpty())
        <div class="card mb-3">
            <div class="card-body text-center text-body-secondary py-5">
                <i class="fas fa-book-open fa-2x mb-3 d-block"></i>
                {{ __('guides.empty') }}
            </div>
        </div>
    @else
        @foreach ($categories as $category => $guides)
            <section class="guide-category mb-4" data-guide-category>
                <h2 class="h5 text-body-secondary mb-3">{{ $category }}</h2>
                <div class="row g-3">
                    @foreach ($guides as $guide)
                        <div class="col-md-6 col-xl-4" data-guide-card
                             data-search="{{ Str::lower($guide->title.' '.$guide->summary.' '.$guide->category) }}">
                            <a href="{{ route('guides.show', $guide->slug) }}" class="card guide-card h-100 text-reset text-decoration-none">
                                <div class="card-body d-flex gap-3">
                                    <span class="guide-card__icon"><i class="{{ $guide->icon }}"></i></span>
                                    <div>
                                        <h3 class="h6 mb-1">{{ $guide->title }}</h3>
                                        @if ($guide->summary)
                                            <p class="small text-body-secondary mb-0">{{ $guide->summary }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent small text-body-secondary d-flex justify-content-between">
                                    <span>
                                        @if ($guide->duration)
                                            <i class="far fa-clock me-1"></i>{{ __('guides.minutes', ['count' => $guide->duration]) }}
                                        @endif
                                    </span>
                                    <span class="guide-card__read ms-auto">{{ __('guides.read') }} <i class="fas fa-arrow-right ms-1"></i></span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
        <p id="guide-no-match" class="text-center text-body-secondary py-4 d-none">{{ __('guides.no_match') }}</p>
    @endif
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
    <script>
        // Filtre instantané des cartes (titre, résumé, catégorie).
        document.getElementById('guide-search')?.addEventListener('input', (event) => {
            const query = event.target.value.trim().toLowerCase();
            let visible = 0;
            document.querySelectorAll('[data-guide-category]').forEach((section) => {
                let inSection = 0;
                section.querySelectorAll('[data-guide-card]').forEach((card) => {
                    const match = card.dataset.search.includes(query);
                    card.classList.toggle('d-none', !match);
                    inSection += match ? 1 : 0;
                });
                section.classList.toggle('d-none', inSection === 0);
                visible += inSection;
            });
            document.getElementById('guide-no-match').classList.toggle('d-none', visible > 0);
        });
    </script>
@stop
