<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('meta_tags')
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>
    @yield('adminlte_css_pre')

    @if(config('adminlte.enabled_laravel_mix', false))
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @else
        @switch(config('adminlte.laravel_asset_bundling', false))
            @case('mix')
                <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_css_path', 'css/app.css')) }}">
            @break
            @case('vite')
                @vite([config('adminlte.laravel_css_path', 'resources/css/app.css'), config('adminlte.laravel_js_path', 'resources/js/app.js')])
            @break
            @case('vite_js_only')
                @vite(config('adminlte.laravel_js_path', 'resources/js/app.js'))
            @break
            @default
                <link rel="stylesheet" href="/vendor/fontawesome-free/css/all.min.css">
                <link rel="stylesheet" href="/vendor/overlayScrollbars/css/OverlayScrollbars.min.css">
                <link rel="stylesheet" href="/vendor/adminlte/dist/css/adminlte.min.css">
                @if(config('adminlte.google_fonts.allowed', true))
                    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
                @endif
        @endswitch
    @endif

    @include('adminlte::plugins', ['type' => 'css'])

    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireStyles
        @else
            <livewire:styles />
        @endif
    @endif

    @yield('adminlte_css')

    @if(config('adminlte.use_ico_only'))
        <link rel="shortcut icon" href="/favicons/favicon.ico" />
    @elseif(config('adminlte.use_full_favicon'))
        <link rel="shortcut icon" href="/favicons/favicon.ico" />
        <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="/favicons/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="/favicons/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="/favicons/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="/favicons/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/favicons/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/favicons/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/favicons/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="/favicons/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/favicons/android-icon-192x192.png">
        <link rel="manifest" crossorigin="use-credentials" href="/favicons/manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="/favicons/ms-icon-144x144.png">
    @endif
</head>
<body class="@yield('classes_body')" @yield('body_data')>
    @yield('body')

    @if(config('adminlte.enabled_laravel_mix', false))
        <script src="{{ mix(config('adminlte.laravel_mix_js_path', 'js/app.js')) }}"></script>
    @else
        @switch(config('adminlte.laravel_asset_bundling', false))
            @case('mix')
                <script src="{{ mix(config('adminlte.laravel_js_path', 'js/app.js')) }}"></script>
            @break
            @case('vite')
            @case('vite_js_only')
            @break
            @default
                <script src="/vendor/jquery/jquery.min.js"></script>
                <script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
                <script src="/vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
                <script src="/vendor/adminlte/dist/js/adminlte.min.js"></script>
        @endswitch
    @endif

    @include('adminlte::plugins', ['type' => 'js'])

    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireScripts
        @else
            <livewire:scripts />
        @endif
    @endif

    @yield('adminlte_js')
</body>
</html>
