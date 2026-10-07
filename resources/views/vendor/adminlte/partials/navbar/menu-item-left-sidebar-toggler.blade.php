{{-- Surcharge du toggler AdminLTE : la sidebar reste sur grand écran, --}}
{{-- en dessous de lg elle est remplacée par le lanceur d'applications --}}
{{-- (include/mobile-app-launcher.blade.php). --}}
<li class="nav-item d-none d-lg-block">
    <a class="nav-link" data-widget="pushmenu" href="#"
        @if(config('adminlte.sidebar_collapse_remember'))
            data-enable-remember="true"
        @endif
        @if(!config('adminlte.sidebar_collapse_remember_no_transition'))
            data-no-transition-after-reload="false"
        @endif
        @if(config('adminlte.sidebar_collapse_auto_size'))
            data-auto-collapse-size="{{ config('adminlte.sidebar_collapse_auto_size') }}"
        @endif>
        <i class="fas fa-bars"></i>
        <span class="sr-only">{{ __('adminlte::adminlte.toggle_navigation') }}</span>
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
                    <i class="fas fa-chevron-down ml-1 small"></i>
                @endif
            </a>
        </li>
    @endif
@endisset
