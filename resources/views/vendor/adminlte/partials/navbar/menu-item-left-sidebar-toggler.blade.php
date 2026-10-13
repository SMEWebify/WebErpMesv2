{{-- Surcharge du toggler AdminLTE : la sidebar reste sur grand écran, --}}
{{-- en dessous de lg elle est remplacée par le lanceur d'applications --}}
{{-- (include/mobile-app-launcher.blade.php). Le plugin PushMenu v4 lit sa --}}
{{-- configuration (mémorisation, point de rupture) sur l'élément .app-sidebar. --}}
<li class="nav-item d-none d-lg-block">
    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"
       aria-label="{{ __('adminlte::adminlte.toggle_navigation') }}">
        <i class="fas fa-bars"></i>
        <span class="visually-hidden">{{ __('adminlte::adminlte.toggle_navigation') }}</span>
    </a>
</li>

@isset($adminlte)
    @php($wemLauncherCurrent = \App\Support\MobileLauncher::current(\App\Support\MobileLauncher::groups($adminlte->menu('sidebar'), request()->path())))
    <li class="nav-item d-lg-none">
        <a class="nav-link wem-launcher-toggle" href="#" role="button" data-wem-launcher-open
           aria-label="{{ __('general_content.applications_trans_key') }}">
            <i class="fas fa-th"></i>
        </a>
    </li>
    @if($wemLauncherCurrent)
        <li class="nav-item d-lg-none wem-launcher-current">
            <a class="nav-link" href="#" role="button"
               data-wem-launcher-open="{{ $wemLauncherCurrent['children'] ? $wemLauncherCurrent['text'] : '' }}">
                <span class="wem-launcher-current__name">{{ $wemLauncherCurrent['text'] }}</span>
                @if($wemLauncherCurrent['children'])
                    <i class="fas fa-chevron-down ms-1 small"></i>
                @endif
            </a>
        </li>
    @endif
@endisset
