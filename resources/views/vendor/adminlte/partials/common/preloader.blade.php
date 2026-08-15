@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

{{-- Surcharge de la vue du package : le preloader affiche le sigle de la marque --}}
{{-- (WEM en open source, N2P en commercial via APP_COMMERCIAL) au lieu du logo --}}
{{-- "A" d'AdminLTE. Voir config/branding.php et config/adminlte.php. --}}

@php($wrapperStyle = trim($preloaderHelper->makePreloaderStyle() . ';overflow:hidden', ';'))

{{-- overflow:hidden car AdminLTE anime la hauteur du preloader vers 0 avant de --}}
{{-- masquer ses enfants (200 ms) : sans ca le sigle deborde en haut de l'ecran. --}}
<div class="{{ $preloaderHelper->makePreloaderClasses() }}" style="{{ $wrapperStyle }}">

    @hasSection('preloader')

        {{-- Contenu personnalise par la page --}}
        @yield('preloader')

    @else

        @php($size = config('adminlte.preloader.img.width', 60))

        <div class="d-flex align-items-center justify-content-center {{ config('adminlte.preloader.img.effect', 'animation__shake') }}"
             role="img"
             aria-label="{{ config('branding.app_name') }}"
             style="width:{{ $size }}px; height:{{ $size }}px; flex-shrink:0; border-radius:50%;
                    background-color:#fff; color:#343a40; font-weight:700;
                    font-size:{{ round($size / 3.5) }}px; letter-spacing:.03em;
                    box-shadow:0 0 0 3px rgba(0, 0, 0, .05);
                    animation-iteration-count:infinite;">
            {{ config('branding.logo_short', 'WEM') }}
        </div>

    @endif

</div>
