<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Title --}}
    <title>
        @hasSection('title')
            @yield('title') -
        @endif
        {{ $Factory->name ?? config('app.name', 'WebErpMesv2') }}
    </title>

    {{-- Base Stylesheets --}}
    @if(!config('adminlte.enabled_laravel_mix'))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

        @if(config('adminlte.google_fonts.allowed', true))
            <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
        @endif
    @else
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    @stack('styles')
</head>
<body class="bg-light">
    @php($customer = auth('customer')->user())
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('customer.dashboard') }}">
                {{ $Factory->name ?? config('app.name', 'WebErpMesv2') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavbar" aria-controls="customerNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="customerNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('customer.dashboard') }}">{{ __('general_content.dashboard_trans_key') ?? 'Dashboard' }}</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    @if($customer)
                        <span class="text-muted small">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ trim(($customer->first_name ?? '') . ' ' . ($customer->name ?? '')) ?: $customer->mail }}
                        </span>
                    @endif
                    <form action="{{ route('customer.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sign-out-alt me-1"></i>{{ __('adminlte::adminlte.sign_out') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
