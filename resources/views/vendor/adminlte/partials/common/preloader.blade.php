@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

{{-- Surcharge de la vue du package : le preloader affiche le sigle de la marque --}}
{{-- (WEM en open source, N2P en commercial via APP_COMMERCIAL) au lieu du logo --}}
{{-- d'AdminLTE. Voir config/branding.php et config/adminlte.php. --}}

@php
    $isCWrapperMode = $preloaderHelper->isPreloaderEnabled('cwrapper');

    // AdminLTE v4 ne fournit plus de style '.preloader' : le voile est construit
    // avec les utilitaires Bootstrap 5.3, comme dans la vue d'origine.

    $preloaderClasses = $preloaderHelper->makePreloaderClasses();
    $preloaderClasses .= ' d-flex w-100 h-100 top-0 start-0 bg-body';
    $preloaderClasses .= $isCWrapperMode ? '' : ' position-fixed';

    $preloaderStyles = array_filter([
        $preloaderHelper->makePreloaderStyle(),
        $isCWrapperMode ? '' : 'z-index:9999',
        'transition:opacity .3s ease-in-out',
    ]);

    // AdminLTE v4 ne fournit que les keyframes des effets 'animation__*' :
    // l'animation est donc appliquée en style inline.

    $effect = (string) config('adminlte.preloader.img.effect', 'animation__shake');
    $keyframes = preg_match('/^animation__(shake|wobble|flipInX|fadeIn|fadeOut|spin)$/', $effect, $m) ? $m[1] : null;

    $size = config('adminlte.preloader.img.width', 60);
@endphp

<div id="adminlte-preloader" class="{{ $preloaderClasses }}" style="{{ implode(';', $preloaderStyles) }}">

    @hasSection('preloader')

        {{-- Contenu personnalise par la page --}}
        @yield('preloader')

    @else

        <div class="d-flex align-items-center justify-content-center"
             role="img"
             aria-label="{{ config('branding.app_name') }}"
             style="width:{{ $size }}px; height:{{ $size }}px; flex-shrink:0; border-radius:50%;
                    background-color:#fff; color:#343a40; font-weight:700;
                    font-size:{{ round($size / 3.5) }}px; letter-spacing:.03em;
                    box-shadow:0 0 0 3px rgba(0, 0, 0, .05);
                    animation-iteration-count:infinite;
                    @if($keyframes) animation-name:{{ $keyframes }}; animation-duration:1s; @endif">
            {{ config('branding.logo_short', 'WEM') }}
        </div>

    @endif

</div>

<style>
    @media (prefers-reduced-motion: reduce) {
        #adminlte-preloader {
            transition: none !important;
        }

        #adminlte-preloader > div {
            animation: none !important;
        }
    }
</style>

{{-- Masque le preloader une fois la page chargee --}}
<script>
    (() => {
        'use strict';
        const preloader = document.getElementById('adminlte-preloader');

        if (! preloader) {
            return;
        }

        const hidePreloader = () => {
            preloader.style.opacity = '0';
            window.setTimeout(() => preloader.remove(), 300);
        };

        if (document.readyState === 'complete') {
            hidePreloader();
        } else {
            window.addEventListener('load', hidePreloader);
        }
    })();
</script>
