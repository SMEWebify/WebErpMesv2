@extends('adminlte::master')

@php
    $brandName = 'Nest2Prod ERP';
    $brandLogo = asset('images/nest2prodERP-logo.png');
@endphp

@section('classes_body', 'n2p-auth-page')

@section('adminlte_css')
    @stack('css')
    @yield('css')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --n2p-bg:        #f6f8fc;
            --n2p-surface:   #ffffff;
            --n2p-surface-2: #f1f5f9;
            --n2p-border:    #e2e8f0;
            --n2p-border-2:  #cbd5e1;
            --n2p-text:      #0f172a;
            --n2p-muted:     #64748b;
            --n2p-dim:       #94a3b8;
            --n2p-sky:       #0ea5e9;
            --n2p-sky-l:     #38bdf8;
            --n2p-blue:      #3b5bdb;
            --n2p-amber:     #f59e0b;
            --n2p-orange:    #f06418;
            --n2p-emerald:   #10b981;
            --n2p-fr-blue:   #0055A4;
            --n2p-fr-red:    #EF4135;
        }

        body.n2p-auth-page {
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--n2p-text);
            background: var(--n2p-bg);
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .n2p-shell {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Ambient background */
        .n2p-bg-grid {
            position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.09) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.09) 1px, transparent 1px);
            background-size: 34px 34px;
            -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, black 30%, transparent 100%);
                    mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, black 30%, transparent 100%);
        }
        .n2p-blob {
            position: absolute; border-radius: 9999px;
            filter: blur(120px); opacity: .55; pointer-events: none;
        }
        .n2p-blob-1 {
            width: 520px; height: 520px; top: -180px; left: -140px;
            background: radial-gradient(circle, rgba(56,189,248,.45), transparent 65%);
        }
        .n2p-blob-2 {
            width: 460px; height: 460px; bottom: -180px; right: -160px;
            background: radial-gradient(circle, rgba(245,158,11,.35), transparent 65%);
        }

        /* Header — dégradé bleu-blanc-rouge */
        .n2p-header {
            position: relative; z-index: 5;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding: 14px 28px;
            border-bottom: 1px solid var(--n2p-border);
            background: linear-gradient(90deg,
                var(--n2p-fr-blue) 0%,
                var(--n2p-fr-blue) 12%,
                rgba(0, 85, 164, 0.55) 22%,
                rgba(255, 255, 255, 0.92) 42%,
                rgba(255, 255, 255, 0.92) 58%,
                rgba(239, 65, 53, 0.55) 78%,
                var(--n2p-fr-red) 88%,
                var(--n2p-fr-red) 100%);
        }
        .n2p-brand {
            display: inline-flex; align-items: center; gap: 10px;
            color: #ffffff; text-decoration: none;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .25);
        }
        .n2p-brand img {
            height: 34px; width: auto; display: block;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, .25));
        }
        .n2p-brand-name {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 15px; font-weight: 700; letter-spacing: -.01em;
        }
        .n2p-header-right {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px; color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .25);
        }
        .n2p-fr-flag { display: inline-flex; gap: 2px; }
        .n2p-fr-flag span { display: block; height: 6px; width: 12px; border-radius: 1px; }
        .n2p-fr-flag .fb { background: var(--n2p-fr-blue); }
        .n2p-fr-flag .fw { background: #fff; border: 1px solid var(--n2p-border); }
        .n2p-fr-flag .fr { background: var(--n2p-fr-red); }

        /* Main split */
        .n2p-main {
            position: relative; z-index: 3;
            flex: 1;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            gap: 64px;
            padding: 60px 48px;
            max-width: 1320px;
            width: 100%;
            margin: 0 auto;
            align-items: center;
        }

        /* Left hero */
        .n2p-hero { display: flex; flex-direction: column; gap: 26px; max-width: 560px; }
        .n2p-kicker {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px; letter-spacing: .35em;
            text-transform: uppercase; color: var(--n2p-sky);
        }
        .n2p-h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 700; letter-spacing: -.02em; line-height: 1.05;
            color: var(--n2p-text);
            margin: 0;
        }
        .n2p-h1 .l2 {
            display: block;
            background: linear-gradient(100deg, #0ea5e9 0%, #3b5bdb 45%, #f59e0b 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: transparent;
        }
        .n2p-lead {
            font-size: 16px; color: var(--n2p-muted); max-width: 500px;
        }
        .n2p-kpis {
            margin-top: 8px; padding-top: 22px;
            border-top: 1px solid var(--n2p-border);
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px;
        }
        .n2p-kpi-n {
            font-family: 'JetBrains Mono', monospace;
            font-size: 22px; font-weight: 600; color: var(--n2p-text);
        }
        .n2p-kpi-n .sky   { color: var(--n2p-sky); }
        .n2p-kpi-n .amber { color: var(--n2p-amber); }
        .n2p-kpi-n .green { color: var(--n2p-emerald); }
        .n2p-kpi-l { font-size: 11px; color: var(--n2p-dim); margin-top: 4px; }

        /* Right card */
        .n2p-card-wrap { display: flex; justify-content: center; }
        .n2p-card {
            width: 100%; max-width: 460px;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(16px);
            border: 1px solid var(--n2p-border);
            border-radius: 24px;
            box-shadow:
                0 40px 90px -30px rgba(15, 23, 42, .18),
                0 12px 30px -12px rgba(59, 91, 219, .12);
            padding: 34px 34px 30px;
        }
        .n2p-card-head { display: flex; flex-direction: column; gap: 6px; margin-bottom: 22px; }
        .n2p-card-eyebrow {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px; letter-spacing: .3em; text-transform: uppercase;
            color: var(--n2p-sky);
        }
        .n2p-card-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 22px; font-weight: 700; letter-spacing: -.01em;
            color: var(--n2p-text);
            margin: 0;
        }
        .n2p-card-sub {
            font-size: 13px; color: var(--n2p-muted);
        }

        /* Form styling overlay */
        .n2p-card .form-control {
            background: var(--n2p-surface);
            border: 1px solid var(--n2p-border);
            color: var(--n2p-text);
            border-radius: 10px 0 0 10px;
            height: 46px;
            font-size: 14px;
        }
        .n2p-card .form-control:focus {
            border-color: var(--n2p-sky);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .18);
        }
        .n2p-card select.form-control {
            padding-right: 10px;
        }
        .n2p-card .input-group-text {
            background: var(--n2p-surface-2);
            border: 1px solid var(--n2p-border);
            border-left: 0;
            color: var(--n2p-muted);
            border-radius: 0 10px 10px 0;
            padding: 0 14px;
        }
        .n2p-card .invalid-feedback {
            font-size: 12px;
        }
        .n2p-card .alert {
            border-radius: 10px;
            font-size: 13px;
            border: 1px solid transparent;
        }
        .n2p-card .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .n2p-card .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        /* Primary gradient button */
        .n2p-card .btn.btn-primary,
        .n2p-card .btn.btn-flat.btn-primary {
            background: linear-gradient(90deg, var(--n2p-sky), var(--n2p-blue));
            border: 0;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            height: 46px;
            border-radius: 12px;
            letter-spacing: .01em;
            box-shadow: 0 10px 22px -8px rgba(59, 91, 219, .55);
            transition: transform .12s ease, box-shadow .18s ease, filter .18s ease;
        }
        .n2p-card .btn.btn-primary:hover,
        .n2p-card .btn.btn-flat.btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px -8px rgba(59, 91, 219, .55);
            filter: brightness(1.02);
        }
        .n2p-card .btn.btn-primary:focus,
        .n2p-card .btn.btn-flat.btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .35);
        }
        .n2p-card .btn .fas,
        .n2p-card .btn .fa {
            margin-right: 6px;
        }

        /* icheck (remember me) */
        .n2p-card .icheck-primary label { color: var(--n2p-muted); font-size: 13px; }

        /* Footer of the card (links) */
        .n2p-card-footer {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid var(--n2p-border);
            display: flex; flex-direction: column; gap: 6px;
        }
        .n2p-card-footer p { margin: 0; font-size: 13px; }
        .n2p-card-footer a {
            color: var(--n2p-blue);
            font-weight: 500;
            text-decoration: none;
        }
        .n2p-card-footer a:hover { color: var(--n2p-sky); text-decoration: underline; }
        .n2p-card-footer .text-muted { color: var(--n2p-dim) !important; font-size: 11px; }

        /* Feature list under card on mobile fallback */
        .n2p-badges {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .n2p-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 999px;
            background: var(--n2p-surface);
            border: 1px solid var(--n2p-border);
            font-size: 11px; color: var(--n2p-muted);
        }
        .n2p-badge .dot {
            width: 6px; height: 6px; border-radius: 999px;
            background: var(--n2p-emerald);
            box-shadow: 0 0 0 3px rgba(16,185,129,.15);
        }

        /* Footer */
        .n2p-footer {
            position: relative; z-index: 5;
            padding: 18px 28px;
            border-top: 1px solid var(--n2p-border);
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px; flex-wrap: wrap;
            font-size: 12px; color: var(--n2p-muted);
            background: rgba(255,255,255,.6);
        }
        .n2p-footer a { color: var(--n2p-muted); text-decoration: none; }
        .n2p-footer a:hover { color: var(--n2p-text); }
        .n2p-footer .n2p-footer-links { display: inline-flex; gap: 18px; flex-wrap: wrap; }

        @keyframes n2pFadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: none; }
        }
        .n2p-anim { animation: n2pFadeUp .55s ease both; }
        .n2p-anim-2 { animation: n2pFadeUp .6s .1s ease both; }
        .n2p-anim-3 { animation: n2pFadeUp .65s .2s ease both; }

        @media (max-width: 992px) {
            .n2p-main {
                grid-template-columns: 1fr;
                gap: 48px;
                padding: 40px 24px;
            }
            .n2p-hero { max-width: 100%; }
            .n2p-card-wrap { justify-content: center; }
        }
        @media (max-width: 576px) {
            .n2p-header { padding: 14px 18px; }
            .n2p-header-right { display: none; }
            .n2p-card { padding: 26px 22px; border-radius: 20px; }
            .n2p-kpis { grid-template-columns: 1fr 1fr; }
            .n2p-footer { justify-content: center; text-align: center; }
        }
    </style>
@stop

@section('body')
    <div class="n2p-shell">
        <div class="n2p-bg-grid"></div>
        <div class="n2p-blob n2p-blob-1"></div>
        <div class="n2p-blob n2p-blob-2"></div>

        <header class="n2p-header">
            <a href="{{ url('/') }}" class="n2p-brand">
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}">
            </a>
            <div class="n2p-header-right">
                <span class="n2p-fr-flag" aria-hidden="true">
                    <span class="fb"></span><span class="fw"></span><span class="fr"></span>
                </span>
                <span>Données hébergées en France</span>
            </div>
        </header>

        <main class="n2p-main">
            <section class="n2p-hero">
                <span class="n2p-kicker n2p-anim">@yield('auth_kicker', 'ERP Industriel · MES')</span>
                <h1 class="n2p-h1 n2p-anim-2">
                    @hasSection('auth_title')
                        @yield('auth_title')
                    @else
                        Pilotez votre société<br>
                        <span class="l2">du devis à la facture.</span>
                    @endif
                </h1>
                <p class="n2p-lead n2p-anim-3">
                    @yield('auth_lead', 'Production, stocks, commercial et comptabilité : un seul outil, connecté en temps réel. Conçu pour la tôlerie, la chaudronnerie, la métallerie et l\'usinage.')
                </p>

                <div class="n2p-kpis n2p-anim-3">
                    <div>
                        <div class="n2p-kpi-n">4<span class="sky"> modules</span></div>
                        <div class="n2p-kpi-l">Interconnectés nativement</div>
                    </div>
                    <div>
                        <div class="n2p-kpi-n">4<span class="amber"> métiers</span></div>
                        <div class="n2p-kpi-l">Tôlerie · Chaudronnerie · Métallerie · Usinage</div>
                    </div>
                    <div>
                        <div class="n2p-kpi-n">100<span class="green">%</span></div>
                        <div class="n2p-kpi-l">Traçabilité ISO 9001</div>
                    </div>
                </div>

                <div class="n2p-badges">
                    <span class="n2p-badge"><span class="dot"></span>Support francophone</span>
                    <span class="n2p-badge">Hébergement France</span>
                </div>
            </section>

            <section class="n2p-card-wrap">
                <div class="n2p-card n2p-anim-2">
                    <div class="n2p-card-head">
                        <span class="n2p-card-eyebrow">@yield('auth_card_eyebrow', 'Accès sécurisé')</span>
                        <h2 class="n2p-card-title">@yield('auth_header')</h2>
                        @hasSection('auth_card_sub')
                            <p class="n2p-card-sub">@yield('auth_card_sub')</p>
                        @endif
                    </div>

                    @yield('auth_body')

                    @hasSection('auth_footer')
                        <div class="n2p-card-footer">
                            @yield('auth_footer')
                        </div>
                    @endif
                </div>
            </section>
        </main>

        <footer class="n2p-footer">
            <div>© {{ date('Y') }} {{ $brandName }}</div>
            <div class="n2p-footer-links">
                <a href="{{ url('/') }}">Accueil</a>
                <a href="https://nest2prod.com" target="_blank" rel="noopener">Site Nest2Prod</a>
            </div>
        </footer>
    </div>
@stop
