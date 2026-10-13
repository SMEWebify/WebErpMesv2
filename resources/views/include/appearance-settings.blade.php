{{--
    Onglet « Apparence » du profil : habillage AdminLTE 4 et couleur principale,
    propres à l'utilisateur connecté (App\Support\UiTheme).
    Chaque aperçu est dessiné avec les classes de l'habillage lui-même.
--}}
@php
    $currentPreset = \App\Support\UiTheme::preset(auth()->user());
    $currentPrimary = auth()->user()->ui_primary;
    $presets = collect(\App\Support\UiTheme::PRESETS)->groupBy(fn ($preset) => $preset['group'], true);
    // Pastilles : la couleur de Bootstrap et celle de la marque sont figées en
    // hexadécimal, var(--bs-primary) suivant le choix en cours.
    $swatch = fn (?string $color) => match ($color) {
        null => 'background-color:#0366d6',
        'primary' => 'background-color:#0d6efd',
        default => '',
    };
@endphp

<form method="POST" action="{{ route('profile.appearance.update') }}" class="wem-appearance">
    @csrf
    @method('PUT')

    <h5 class="mb-1">{{ __('appearance.preset') }}</h5>
    <p class="text-muted small mb-3">{{ __('appearance.preset_hint') }}</p>

    @foreach(\App\Support\UiTheme::GROUPS as $group)
        @continue(! $presets->has($group))
        <h6 class="text-uppercase text-muted small fw-semibold mt-3 mb-2">{{ __('appearance.groups.'.$group) }}</h6>
        <div class="d-flex flex-wrap gap-3">
            @foreach($presets[$group] as $key => $preset)
                @php([$headerClasses, $darkHeader] = $preset['header'])
                @php([$sidebarClasses, $sidebarTheme] = $preset['sidebar'])
                <div>
                    <input type="radio" class="btn-check" name="ui_preset" value="{{ $key }}"
                           id="ui-preset-{{ $key }}" autocomplete="off" @checked($currentPreset === $key)>
                    <label class="wem-preset" for="ui-preset-{{ $key }}">
                        <span class="wem-preset__frame" aria-hidden="true">
                            <span class="wem-preset__sidebar {{ $sidebarClasses }}"
                                  @if($sidebarTheme) data-bs-theme="{{ $sidebarTheme }}" @endif>
                                <span></span><span></span><span></span>
                            </span>
                            <span class="wem-preset__main">
                                <span class="wem-preset__header {{ $headerClasses }}"></span>
                                <span class="wem-preset__swatch {{ $preset['primary'] && $preset['primary'] !== 'primary' ? 'text-bg-'.$preset['primary'] : '' }}"
                                      style="{{ $swatch($preset['primary']) }}"></span>
                            </span>
                        </span>
                        <span class="wem-preset__name">{{ __('appearance.presets.'.$key) }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    @endforeach

    <h5 class="mt-4 mb-1">{{ __('appearance.primary') }}</h5>
    <p class="text-muted small mb-3">{{ __('appearance.primary_hint') }}</p>

    <div class="d-flex flex-wrap gap-2">
        <div>
            <input type="radio" class="btn-check" name="ui_primary" value="" id="ui-primary-default"
                   autocomplete="off" @checked(! $currentPrimary)>
            <label class="btn btn-outline-secondary btn-sm" for="ui-primary-default">{{ __('appearance.primary_default') }}</label>
        </div>
        @foreach(\App\Support\UiTheme::PRIMARIES as $color)
            <div>
                <input type="radio" class="btn-check" name="ui_primary" value="{{ $color }}"
                       id="ui-primary-{{ $color }}" autocomplete="off" @checked($currentPrimary === $color)>
                <label class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" for="ui-primary-{{ $color }}">
                    <span class="wem-color-dot {{ $color !== 'primary' ? 'text-bg-'.$color : '' }}" style="{{ $swatch($color) }}" aria-hidden="true"></span>
                    {{ __('appearance.colors.'.$color) }}
                </label>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> {{ __('appearance.save') }}
        </button>
    </div>
</form>

<style>
    .wem-preset {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
        padding: .4rem;
        border: 2px solid transparent;
        border-radius: .5rem;
        cursor: pointer;
    }

    .btn-check:checked + .wem-preset {
        border-color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), .08);
    }

    .btn-check:focus-visible + .wem-preset {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }

    .wem-preset__frame {
        display: flex;
        width: 120px;
        height: 72px;
        overflow: hidden;
        border: 1px solid var(--bs-border-color);
        border-radius: .35rem;
        background-color: var(--bs-tertiary-bg);
    }

    .wem-preset__sidebar {
        display: flex;
        flex-direction: column;
        gap: 5px;
        width: 30%;
        padding: 8px 6px;
    }

    .wem-preset__sidebar span {
        height: 4px;
        border-radius: 2px;
        background-color: currentColor;
        opacity: .45;
    }

    .wem-preset__main {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .wem-preset__header {
        height: 16px;
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }

    .wem-preset__swatch {
        width: 28px;
        height: 14px;
        margin: 10px;
        border-radius: 3px;
    }

    .wem-preset__name {
        font-size: .8rem;
    }

    .wem-color-dot {
        display: inline-block;
        width: .9rem;
        height: .9rem;
        border-radius: 50%;
        border: 1px solid var(--bs-border-color-translucent);
    }
</style>
