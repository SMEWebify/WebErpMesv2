{{--
    Lanceur d'applications mobile (grille façon Odoo).

    Remplace la sidebar AdminLTE en dessous de lg : ouvert par le bouton grille
    de la navbar (partials/navbar/menu-item-left-sidebar-toggler), ou directement
    sur les menus de l'application courante en touchant son nom.
    Les entrées viennent de $adminlte->menu('sidebar') : droits, URL et
    traductions sont ceux de la sidebar desktop.
--}}
@php
    $wemLauncherGroups = \App\Support\MobileLauncher::groups($adminlte->menu('sidebar'), request()->path());
    $wemLauncherExtras = collect($adminlte->menu('navbar-right'))
        ->filter(fn ($item) => isset($item['text'], $item['href']) && empty($item['submenu']) && empty($item['type']))
        ->values();
    $wemLauncherLanguages = collect($adminlte->menu('navbar-right'))
        ->first(fn ($item) => collect($item['submenu'] ?? [])->contains(fn ($child) => str_contains($child['icon'] ?? '', 'flag-icon')));
@endphp

<div id="wem-launcher" class="wem-launcher" role="dialog" aria-modal="true"
     aria-label="{{ __('general_content.applications_trans_key') }}" hidden>

    <div class="wem-launcher__bar">
        <div class="wem-launcher__search">
            <i class="fas fa-search"></i>
            <input type="search" class="wem-launcher__input" autocomplete="off"
                   placeholder="{{ __('general_content.search_menu_trans_key') }}"
                   aria-label="{{ __('general_content.search_menu_trans_key') }}">
        </div>
        <button type="button" class="wem-launcher__close" data-wem-launcher-close
                aria-label="{{ __('general_content.close_trans_key') }}">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="wem-launcher__body">

        {{-- Accueil : grille des applications --}}
        <section class="wem-launcher__view" data-wem-view="home">
            @foreach($wemLauncherGroups as $group)
                @if($group['title'])
                    <h6 class="wem-launcher__group">{{ $group['title'] }}</h6>
                @endif
                <div class="wem-launcher__grid">
                    @foreach($group['apps'] as $app)
                        @if($app['children'])
                            <button type="button" class="wem-tile {{ $app['active'] ? 'is-active' : '' }}"
                                    data-wem-app="{{ $app['text'] }}">
                        @else
                            <a href="{{ $app['href'] }}" class="wem-tile {{ $app['active'] ? 'is-active' : '' }}"
                               @if($app['target']) target="{{ $app['target'] }}" @endif>
                        @endif
                                <span class="wem-tile__icon" style="--wem-tile: {{ $app['color'] }}">
                                    <i class="{{ $app['icon'] }}"></i>
                                </span>
                                <span class="wem-tile__label">{{ $app['text'] }}</span>
                        @if($app['children'])
                            </button>
                        @else
                            </a>
                        @endif
                    @endforeach
                </div>
            @endforeach

            @if($wemLauncherExtras->isNotEmpty() || $wemLauncherLanguages)
                <div class="wem-launcher__footer">
                    @foreach($wemLauncherExtras as $extra)
                        <a href="{{ $extra['href'] }}" class="wem-chip"
                           @isset($extra['target']) target="{{ $extra['target'] }}" @endisset>{{ $extra['text'] }}</a>
                    @endforeach
                    @if($wemLauncherLanguages)
                        @foreach($wemLauncherLanguages['submenu'] as $language)
                            <a href="{{ $language['href'] }}" class="wem-chip" title="{{ $language['text'] }}">
                                <i class="{{ $language['icon'] }}"></i>
                            </a>
                        @endforeach
                    @endif
                </div>
            @endif
        </section>

        {{-- Résultats de recherche : toutes les entrées, à plat --}}
        <section class="wem-launcher__view" data-wem-view="search" hidden>
            <div class="wem-list">
                @foreach($wemLauncherGroups as $group)
                    @foreach($group['apps'] as $app)
                        @forelse($app['children'] as $child)
                            <a href="{{ $child['href'] }}" class="wem-list__item" data-wem-search="{{ $app['text'] }} {{ $child['text'] }}"
                               @if($child['target']) target="{{ $child['target'] }}" @endif>
                                <span class="wem-list__dot" style="--wem-tile: {{ $app['color'] }}"><i class="{{ $app['icon'] }}"></i></span>
                                <span class="wem-list__text"><small>{{ $app['text'] }}</small>{{ $child['text'] }}</span>
                            </a>
                        @empty
                            <a href="{{ $app['href'] }}" class="wem-list__item" data-wem-search="{{ $app['text'] }}"
                               @if($app['target']) target="{{ $app['target'] }}" @endif>
                                <span class="wem-list__dot" style="--wem-tile: {{ $app['color'] }}"><i class="{{ $app['icon'] }}"></i></span>
                                <span class="wem-list__text">{{ $app['text'] }}</span>
                            </a>
                        @endforelse
                    @endforeach
                @endforeach
            </div>
            <p class="wem-launcher__empty" hidden>{{ __('general_content.no_results_trans_key') }}</p>
        </section>

        {{-- Menus d'une application --}}
        @foreach($wemLauncherGroups as $group)
            @foreach($group['apps'] as $app)
                @if($app['children'])
                    <section class="wem-launcher__view" data-wem-view="{{ $app['text'] }}" hidden>
                        <button type="button" class="wem-launcher__back" data-wem-launcher-home>
                            <i class="fas fa-chevron-left"></i> {{ __('general_content.applications_trans_key') }}
                        </button>
                        <div class="wem-app-head">
                            <span class="wem-tile__icon" style="--wem-tile: {{ $app['color'] }}"><i class="{{ $app['icon'] }}"></i></span>
                            <h5>{{ $app['text'] }}</h5>
                        </div>
                        <div class="wem-list">
                            @foreach($app['children'] as $child)
                                <a href="{{ $child['href'] }}" class="wem-list__item {{ $child['active'] ? 'is-active' : '' }}"
                                   @if($child['target']) target="{{ $child['target'] }}" @endif>
                                    <span class="wem-list__text">{{ $child['text'] }}</span>
                                    @if(isset($child['label']) && $child['label'] !== '')
                                        <span class="badge badge-{{ $child['label_color'] }}">{{ $child['label'] }}</span>
                                    @endif
                                    <i class="fas fa-chevron-right wem-list__chevron"></i>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        @endforeach

    </div>
</div>

<style>
    /* ---- Navbar mobile ---------------------------------------------- */
    @media (max-width: 991.98px) {
        .main-header .wem-hide-mobile,
        .main-header [data-widget="fullscreen"] {
            display: none !important;
        }

        .main-header .wem-launcher-toggle i {
            font-size: 1.15rem;
        }

        .wem-launcher-current {
            min-width: 0;
        }

        .wem-launcher-current .nav-link {
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .wem-launcher-current__name {
            max-width: 38vw;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
    }

    /* ---- Overlay ---------------------------------------------------- */
    .wem-launcher {
        --wem-bg: linear-gradient(160deg, #f8fafc 0%, #e2e8f0 100%);
        --wem-fg: #1e293b;
        --wem-muted: #64748b;
        --wem-surface: #ffffff;
        --wem-border: rgba(15, 23, 42, .08);
        --wem-hover: rgba(15, 23, 42, .05);

        position: fixed;
        inset: 0;
        z-index: 1060;
        display: flex;
        flex-direction: column;
        background: var(--wem-bg);
        color: var(--wem-fg);
        padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        animation: wem-launcher-in .18s ease-out;
    }

    body.dark-mode .wem-launcher {
        --wem-bg: linear-gradient(160deg, #0f172a 0%, #1e293b 100%);
        --wem-fg: #e2e8f0;
        --wem-muted: #94a3b8;
        --wem-surface: #1e293b;
        --wem-border: rgba(255, 255, 255, .08);
        --wem-hover: rgba(255, 255, 255, .06);
    }

    .wem-launcher[hidden],
    .wem-launcher [hidden] {
        display: none !important;
    }

    @keyframes wem-launcher-in {
        from { opacity: 0; transform: scale(1.02); }
        to   { opacity: 1; transform: none; }
    }

    html.wem-launcher-open,
    html.wem-launcher-open body {
        overflow: hidden;
    }

    .wem-launcher__bar {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .75rem 1rem;
        max-width: 760px;
        width: 100%;
        margin: 0 auto;
    }

    .wem-launcher__search {
        flex: 1;
        display: flex;
        align-items: center;
        gap: .5rem;
        background: var(--wem-surface);
        border: 1px solid var(--wem-border);
        border-radius: 999px;
        padding: 0 1rem;
        color: var(--wem-muted);
    }

    .wem-launcher__input {
        flex: 1;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: var(--wem-fg);
        padding: .6rem 0;
        font-size: 1rem; /* 16px : évite le zoom auto d'iOS au focus */
    }

    .wem-launcher__close {
        width: 2.5rem;
        height: 2.5rem;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: var(--wem-fg);
        font-size: 1.25rem;
    }

    .wem-launcher__close:hover,
    .wem-launcher__close:focus-visible {
        background: var(--wem-hover);
    }

    .wem-launcher__body {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .wem-launcher__view {
        max-width: 760px;
        margin: 0 auto;
        padding: .5rem 1rem 2rem;
    }

    .wem-launcher__group {
        margin: 1.5rem .25rem .75rem;
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--wem-muted);
    }

    /* ---- Tuiles ----------------------------------------------------- */
    .wem-launcher__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(78px, 1fr));
        gap: 1.25rem .5rem;
    }

    .wem-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .4rem;
        padding: .25rem;
        border: 0;
        background: transparent;
        color: inherit;
        text-decoration: none;
        border-radius: 12px;
        -webkit-tap-highlight-color: transparent;
    }

    .wem-tile:hover,
    .wem-tile:focus-visible {
        color: inherit;
        text-decoration: none;
    }

    .wem-tile__icon {
        width: 56px;
        height: 56px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        color: #fff;
        font-size: 1.4rem;
        background: linear-gradient(145deg, color-mix(in srgb, var(--wem-tile) 80%, #fff), var(--wem-tile));
        background-color: var(--wem-tile);
        box-shadow: 0 4px 10px color-mix(in srgb, var(--wem-tile) 35%, transparent);
        transition: transform .12s ease;
    }

    .wem-tile:active .wem-tile__icon {
        transform: scale(.92);
    }

    .wem-tile.is-active .wem-tile__icon {
        outline: 3px solid color-mix(in srgb, var(--wem-tile) 40%, transparent);
        outline-offset: 2px;
    }

    .wem-tile__label {
        font-size: .78rem;
        line-height: 1.15;
        text-align: center;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    /* ---- Listes (menus d'une application, recherche) --------------- */
    .wem-launcher__back {
        border: 0;
        background: transparent;
        color: var(--wem-muted);
        padding: .25rem 0;
        margin-bottom: .75rem;
        font-size: .9rem;
    }

    .wem-app-head {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1rem;
    }

    .wem-app-head h5 {
        margin: 0;
        font-weight: 600;
    }

    .wem-app-head .wem-tile__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        font-size: 1.1rem;
    }

    .wem-list {
        background: var(--wem-surface);
        border: 1px solid var(--wem-border);
        border-radius: 14px;
        overflow: hidden;
    }

    .wem-list__item {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-height: 52px;
        padding: .6rem 1rem;
        color: var(--wem-fg);
        text-decoration: none;
        border-bottom: 1px solid var(--wem-border);
    }

    .wem-list__item:last-child {
        border-bottom: 0;
    }

    .wem-list__item:hover,
    .wem-list__item:focus-visible {
        color: var(--wem-fg);
        text-decoration: none;
        background: var(--wem-hover);
    }

    .wem-list__item.is-active {
        font-weight: 600;
        box-shadow: inset 3px 0 0 #3b82f6;
    }

    .wem-list__text {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .wem-list__text small {
        color: var(--wem-muted);
        font-size: .72rem;
    }

    .wem-list__chevron {
        color: var(--wem-muted);
        font-size: .75rem;
    }

    .wem-list__dot {
        width: 32px;
        height: 32px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: var(--wem-tile);
        color: #fff;
        font-size: .85rem;
    }

    .wem-launcher__empty {
        text-align: center;
        color: var(--wem-muted);
        margin-top: 2rem;
    }

    /* ---- Pied : liens navbar masqués sur mobile, langues ------------- */
    .wem-launcher__footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .5rem;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid var(--wem-border);
    }

    .wem-chip {
        display: inline-flex;
        align-items: center;
        padding: .35rem .8rem;
        border-radius: 999px;
        background: var(--wem-surface);
        border: 1px solid var(--wem-border);
        color: var(--wem-fg);
        font-size: .85rem;
        text-decoration: none;
    }

    .wem-chip:hover {
        color: var(--wem-fg);
        text-decoration: none;
        background: var(--wem-hover);
    }

    @media (prefers-reduced-motion: reduce) {
        .wem-launcher { animation: none; }
        .wem-tile__icon { transition: none; }
    }
</style>

<script>
    (function () {
        const launcher = document.getElementById('wem-launcher');

        if (! launcher) {
            return;
        }

        const input = launcher.querySelector('.wem-launcher__input');
        const views = launcher.querySelectorAll('[data-wem-view]');
        const results = launcher.querySelectorAll('[data-wem-search]');
        const empty = launcher.querySelector('.wem-launcher__empty');
        const root = document.documentElement;

        const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

        results.forEach((item) => {
            item.dataset.wemSearch = normalize(item.dataset.wemSearch);
        });

        const show = (name) => {
            views.forEach((view) => {
                view.hidden = view.dataset.wemView !== name;
            });
            launcher.querySelector('.wem-launcher__body').scrollTop = 0;
        };

        const isOpen = () => ! launcher.hidden;

        const hide = () => {
            launcher.hidden = true;
            root.classList.remove('wem-launcher-open');
        };

        const open = (app) => {
            if (! isOpen()) {
                // Une entrée d'historique : le bouton retour du téléphone ferme le lanceur
                history.pushState({ wemLauncher: true }, '');
            }

            input.value = '';
            show(app && launcher.querySelector('[data-wem-view="' + CSS.escape(app) + '"]') ? app : 'home');
            launcher.hidden = false;
            root.classList.add('wem-launcher-open');
        };

        const close = () => {
            if (history.state && history.state.wemLauncher) {
                history.back(); // popstate referme
            } else {
                hide();
            }
        };

        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-wem-launcher-open]');

            if (opener) {
                event.preventDefault();
                open(opener.dataset.wemLauncherOpen);
            }
        });

        launcher.addEventListener('click', (event) => {
            const app = event.target.closest('[data-wem-app]');

            if (app) {
                show(app.dataset.wemApp);
            } else if (event.target.closest('[data-wem-launcher-home]')) {
                show('home');
            } else if (event.target.closest('[data-wem-launcher-close]')) {
                close();
            }
        });

        input.addEventListener('input', () => {
            const query = normalize(input.value.trim());

            if (query === '') {
                show('home');
                return;
            }

            const terms = query.split(/\s+/);
            let visible = 0;

            results.forEach((item) => {
                const match = terms.every((term) => item.dataset.wemSearch.includes(term));
                item.hidden = ! match;
                visible += match ? 1 : 0;
            });

            empty.hidden = visible > 0;
            show('search');
        });

        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            const first = [...results].find((item) => ! item.hidden);

            if (input.value.trim() !== '' && first) {
                first.click();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) {
                close();
            }
        });

        window.addEventListener('popstate', () => {
            if (isOpen()) {
                hide();
            }
        });

        // Page restaurée depuis le cache avant/arrière avec le lanceur ouvert
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                hide();
            }
        });
    })();
</script>
