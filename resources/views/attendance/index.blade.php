<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Base Meta Tags --}}
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

    {{-- AdminLTE --}}
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

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    {{-- Favicon --}}
    @if(config('adminlte.use_ico_only'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
    @elseif(config('adminlte.use_full_favicon'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('favicons/apple-icon-57x57.png') }}">
        <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('favicons/apple-icon-60x60.png') }}">
        <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('favicons/apple-icon-72x72.png') }}">
        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('favicons/apple-icon-76x76.png') }}">
        <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('favicons/apple-icon-114x114.png') }}">
        <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('favicons/apple-icon-120x120.png') }}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('favicons/apple-icon-144x144.png') }}">
        <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('favicons/apple-icon-152x152.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-icon-180x180.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicons/favicon-96x96.png') }}">
        <link rel="icon" type="image/png" sizes="192x192"  href="{{ asset('favicons/android-icon-192x192.png') }}">
        <link rel="manifest" crossorigin="use-credentials" href="{{ asset('favicons/manifest.json') }}">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    @endif

    <style>
        /* Page background + centering */
        body {
            background: #f1f5f9; /* proche slate-100 */
        }
        .attendance-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }

        /* Card */
        .attendance-card {
            width: 100%;
            max-width: 420px;
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(0,0,0,.12);
            background: #fff;
        }

        /* Header */
        .attendance-header {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .attendance-icon {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.7);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .attendance-subtitle {
            opacity: .85;
            font-size: .9rem;
            margin-top: .25rem;
        }

        /* Clock */
        .attendance-clock {
            background: #0f172a; /* slate-900 */
            color: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            text-align: center;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: .35em;
            box-shadow: inset 0 2px 10px rgba(0,0,0,.35);
        }

        /* Inputs */
        .attendance-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b; /* slate-500 */
            margin-bottom: .4rem;
        }
        .attendance-select {
            border-radius: 14px;
            padding: .7rem .9rem;
            background: #f8fafc;
        }

        /* Buttons (big like image) */
        .attendance-btn {
            border-radius: 14px;
            padding: 18px 14px;
            font-weight: 700;
            box-shadow: 0 10px 18px rgba(0,0,0,.12);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .attendance-btn svg { width: 22px; height: 22px; }

        /* Success message readable */
        .attendance-status {
            border-radius: 14px;
            padding: 10px 12px;
            background: #ecfdf5; /* emerald-50 */
            color: #065f46;      /* emerald-800 */
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>

<body>
<div class="attendance-wrap">
    <div class="attendance-card">
        <div class="attendance-header">
            <div class="attendance-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 7v6l3 3"></path>
                </svg>
            </div>

            <h1 class="mt-3 mb-1" style="font-size: 1.5rem; font-weight: 700;">
                {{ __('general_content.attendance_trans_key') }}
            </h1>
            <div class="attendance-subtitle">
                {{ now()->translatedFormat('l j F Y') }}
            </div>
        </div>

        <div class="p-4 p-md-4" style="display:flex; flex-direction:column; gap: 18px;">
            {{-- Message success lisible --}}
            @if (session('status'))
                <div class="attendance-status">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Clock --}}
            <div class="attendance-clock" data-clock>00:00:00</div>

            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf

                <div class="mb-3">
                    <div class="attendance-label">
                        {{ __('general_content.user_trans_key') }}
                    </div>

                    <select id="user_id" name="user_id" class="form-select attendance-select" required>
                        <option value="">{{ __('general_content.attendance_select_user_trans_key') }}</option>
                        @foreach($userSelect as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('user_id')
                        <div class="text-danger mt-2" style="font-size:.9rem;">{{ $message }}</div>
                    @enderror
                </div>

                @error('direction')
                    <div class="text-danger mb-2" style="font-size:.9rem;">{{ $message }}</div>
                @enderror

                <div class="row g-3">
                    <div class="col-6">
                        <button type="submit" name="direction" value="in" class="btn btn-success w-100 attendance-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 12h12"></path>
                                <path d="M10 6l6 6-6 6"></path>
                            </svg>
                            {{ __('general_content.attendance_entry_trans_key') }}
                        </button>
                    </div>

                    <div class="col-6">
                        <button type="submit" name="direction" value="out" class="btn btn-danger w-100 attendance-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 12H8"></path>
                                <path d="M14 6l-6 6 6 6"></path>
                            </svg>
                            {{ __('general_content.attendance_exit_trans_key') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const clock = document.querySelector('[data-clock]');
    if (!clock) return;

    const updateClock = () => {
        const now = new Date();
        clock.textContent = now.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    };

    updateClock();
    setInterval(updateClock, 1000);
});
</script>

</body>
</html>
