<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Nest2Prod ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green: #22c55e;
            --green-dark: #16a34a;
            --blue: #3b82f6;
            --blue-dark: #2563eb;
            --blue-glow: rgba(59, 130, 246, 0.3);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
            overflow-x: hidden;
            position: relative;
        }

        /* ── Animated background shapes ── */
        .bg-shapes {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0;
            animation: shape-fade-in 1.2s ease forwards, shape-drift ease-in-out infinite alternate;
        }
        .shape-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,0.12), transparent 70%);
            top: -200px; right: -150px;
            animation-delay: 0s, 0.5s;
            animation-duration: 1.2s, 18s;
        }
        .shape-2 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(34,197,94,0.1), transparent 70%);
            bottom: -150px; left: -100px;
            animation-delay: 0.3s, 0.8s;
            animation-duration: 1.2s, 22s;
        }
        .shape-3 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(124,58,237,0.08), transparent 70%);
            top: 40%; left: 30%;
            animation-delay: 0.6s, 1s;
            animation-duration: 1.2s, 15s;
        }
        @keyframes shape-fade-in {
            to { opacity: 1; }
        }
        @keyframes shape-drift {
            from { transform: translate(0, 0); }
            to   { transform: translate(30px, 20px); }
        }

        /* ── Floating dots grid ── */
        .dot-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image: radial-gradient(circle, rgba(0,0,0,0.06) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* ── Top bar ── */
        .n2p-topbar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 48px;
            animation: slide-down 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes slide-down {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .n2p-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-decoration: none;
            letter-spacing: -0.02em;
        }
        .n2p-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--green), #0ea5e9);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 16px;
            box-shadow: 0 4px 14px rgba(34,197,94,0.35);
            animation: logo-pulse 3s ease-in-out infinite;
        }
        @keyframes logo-pulse {
            0%, 100% { box-shadow: 0 4px 14px rgba(34,197,94,0.35); }
            50%       { box-shadow: 0 4px 24px rgba(14,165,233,0.45); }
        }
        .n2p-logo-badge {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 2px 8px;
            letter-spacing: 0.06em;
            background: #fff;
        }
        .n2p-topbar-link {
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            display: flex; align-items: center; gap: 5px;
            transition: color 0.2s;
        }
        .n2p-topbar-link:hover { color: #0f172a; }

        /* ── Main ── */
        .n2p-main {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 48px 48px;
            gap: 72px;
        }

        /* ── Left column ── */
        .n2p-left {
            flex: 1;
            max-width: 540px;
            animation: slide-left 0.7s 0.1s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes slide-left {
            from { opacity: 0; transform: translateX(-24px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .n2p-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 12.5px;
            color: #475569;
            margin-bottom: 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            animation: fade-in 0.5s 0.35s ease both;
        }
        .n2p-pill-dot {
            width: 7px; height: 7px;
            background: var(--green);
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
            animation: dot-ping 2s ease-in-out infinite;
        }
        @keyframes dot-ping {
            0%, 100% { box-shadow: 0 0 0 3px rgba(34,197,94,0.2); }
            50%       { box-shadow: 0 0 0 6px rgba(34,197,94,0.08); }
        }

        .n2p-heading {
            font-size: 44px;
            font-weight: 800;
            line-height: 1.13;
            color: #0f172a;
            margin-bottom: 18px;
            letter-spacing: -0.03em;
            animation: fade-in 0.5s 0.4s ease both;
        }
        .n2p-heading .green { color: var(--green); }
        .n2p-heading .blue  { color: var(--blue); }

        .n2p-sub {
            font-size: 15px;
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 36px;
            animation: fade-in 0.5s 0.48s ease both;
        }

        /* ── Feature cards ── */
        .n2p-cards {
            display: flex;
            gap: 14px;
            margin-bottom: 32px;
        }
        .n2p-card {
            flex: 1;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
            animation: card-in 0.5s ease both;
        }
        .n2p-card:nth-child(1) { animation-delay: 0.52s; }
        .n2p-card:nth-child(2) { animation-delay: 0.60s; }
        .n2p-card:nth-child(3) { animation-delay: 0.68s; }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .n2p-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.09);
            border-color: #cbd5e1;
        }
        .n2p-card-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            font-size: 16px;
        }
        .n2p-card-icon.blue  { background: #eff6ff; color: var(--blue); }
        .n2p-card-icon.green { background: #f0fdf4; color: var(--green); }
        .n2p-card-icon.purple { background: #faf5ff; color: #7c3aed; }
        .n2p-card-title {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 5px;
        }
        .n2p-card-desc {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .n2p-cta {
            font-size: 13.5px;
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s, color 0.2s;
            animation: fade-in 0.5s 0.75s ease both;
        }
        .n2p-cta:hover { color: var(--green-dark); gap: 10px; }

        /* ── Right column ── */
        .n2p-right {
            width: 400px;
            flex-shrink: 0;
            animation: slide-right 0.7s 0.15s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes slide-right {
            from { opacity: 0; transform: translateX(24px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .n2p-form-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08), 0 1px 0 rgba(255,255,255,0.8) inset;
            padding: 40px 36px 32px;
            position: relative;
            overflow: hidden;
        }
        .n2p-form-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--green), var(--blue), #7c3aed);
            background-size: 200% 100%;
            animation: gradient-slide 3s linear infinite;
        }
        @keyframes gradient-slide {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .n2p-form-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .n2p-form-logo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .n2p-form-sub {
            font-size: 13.5px;
            color: #94a3b8;
        }

        /* ── Alert ── */
        .n2p-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 20px;
            color: #ef4444;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25%       { transform: translateX(-5px); }
            75%       { transform: translateX(5px); }
        }

        /* ── Fields ── */
        .n2p-field {
            margin-bottom: 16px;
            animation: fade-in 0.4s ease both;
        }
        .n2p-field:nth-child(1) { animation-delay: 0.35s; }
        .n2p-field:nth-child(2) { animation-delay: 0.42s; }
        .n2p-field:nth-child(3) { animation-delay: 0.49s; }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .n2p-field label {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }
        .n2p-input-wrap { position: relative; }
        .n2p-input-wrap input,
        .n2p-input-wrap select {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 40px 11px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #fafafa;
            outline: none;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        .n2p-input-wrap input::placeholder { color: #cbd5e1; }
        .n2p-input-wrap input:focus,
        .n2p-input-wrap select:focus {
            border-color: var(--blue);
            background: #fff;
            box-shadow: 0 0 0 3px var(--blue-glow);
        }
        .n2p-input-wrap input.is-invalid { border-color: #ef4444; }
        .n2p-input-icon {
            position: absolute;
            right: 13px; top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 14px;
            transition: color 0.2s;
            pointer-events: none;
        }
        .n2p-input-icon.clickable { pointer-events: auto; cursor: pointer; }
        .n2p-input-icon.clickable:hover { color: var(--blue); }
        .n2p-input-wrap:focus-within .n2p-input-icon:not(.clickable) { color: var(--blue); }
        .invalid-feedback { font-size: 12px; color: #ef4444; margin-top: 4px; display: block; }

        /* ── Remember / forgot ── */
        .n2p-row-remember {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            animation: fade-in 0.4s 0.55s ease both;
        }
        .n2p-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            user-select: none;
        }
        .n2p-remember input[type="checkbox"] { display: none; }
        .custom-check {
            width: 17px; height: 17px;
            border: 1.5px solid #cbd5e1;
            border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s, border-color 0.2s;
            background: #fafafa;
        }
        .n2p-remember input:checked ~ .custom-check {
            background: var(--blue);
            border-color: var(--blue);
        }
        .n2p-remember input:checked ~ .custom-check::after {
            content: '';
            display: block;
            width: 4px; height: 8px;
            border: 2px solid #fff;
            border-top: none; border-left: none;
            transform: rotate(45deg) translate(-1px, -1px);
        }
        .n2p-forgot {
            font-size: 12.5px;
            color: var(--blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .n2p-forgot:hover { color: var(--blue-dark); }

        /* ── Submit ── */
        .n2p-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--blue), #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: 14.5px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 18px var(--blue-glow);
            animation: fade-in 0.4s 0.6s ease both;
        }
        .n2p-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .n2p-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px var(--blue-glow); }
        .n2p-btn:hover::before { opacity: 1; }
        .n2p-btn:active { transform: translateY(0); }
        .n2p-btn .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            transform: scale(0);
            animation: ripple 0.55s linear;
            pointer-events: none;
        }
        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }

        .n2p-register {
            text-align: center;
            font-size: 12.5px;
            color: #94a3b8;
            animation: fade-in 0.4s 0.65s ease both;
        }
        .n2p-register a {
            color: var(--blue);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }
        .n2p-register a:hover { color: var(--blue-dark); }

        /* ── Mode selector ── */
        .n2p-mode-wrap { margin-bottom: 16px; }

        /* ── Footer ── */
        .n2p-footer {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 48px;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(8px);
            animation: fade-in 0.5s 0.8s ease both;
        }
        .n2p-footer a { color: #94a3b8; text-decoration: none; margin-left: 16px; transition: color 0.2s; }
        .n2p-footer a:hover { color: #475569; }

        @media (max-width: 960px) {
            .n2p-main { flex-direction: column; gap: 36px; padding: 24px 24px 40px; }
            .n2p-left { max-width: 100%; }
            .n2p-right { width: 100%; max-width: 440px; align-self: center; }
            .n2p-heading { font-size: 32px; }
            .n2p-topbar { padding: 16px 24px; }
        }
        @media (max-width: 560px) {
            .n2p-cards { flex-direction: column; }
            .n2p-footer { flex-direction: column; gap: 6px; text-align: center; }
            .n2p-form-card { padding: 32px 22px 24px; }
        }
    </style>
</head>
<body>

    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="dot-grid"></div>

    {{-- Top bar --}}
    <nav class="n2p-topbar">
        <a href="#" class="n2p-logo">
            <div class="n2p-logo-icon"><i class="fas fa-industry"></i></div>
            Nest<span style="color:var(--green)">2</span>Prod
            <span class="n2p-logo-badge">ERP</span>
        </a>
        <a href="https://nest2prod.com" target="_blank" rel="noopener" class="n2p-topbar-link">
            nest2prod.com <i class="fas fa-arrow-up-right-from-square" style="font-size:10px"></i>
        </a>
    </nav>

    {{-- Main --}}
    <main class="n2p-main">

        {{-- Left: marketing --}}
        <div class="n2p-left">
            <div class="n2p-pill">
                <span class="n2p-pill-dot"></span>
                Module de la suite Nest2Prod
            </div>

            <h1 class="n2p-heading">
                Gestion <span class="green">intelligente</span><br>
                <span class="green">pour</span> <span class="blue">l'industrie</span>
            </h1>

            <p class="n2p-sub">
                Centralisez vos commandes, stocks et plannings au sein d'une
                plateforme unifiée, propulsée par l'intelligence artificielle.
            </p>

            <div class="n2p-cards">
                <div class="n2p-card">
                    <div class="n2p-card-icon blue"><i class="fas fa-sliders"></i></div>
                    <div class="n2p-card-title">Gestion ERP</div>
                    <div class="n2p-card-desc">Commandes, stocks et planification centralisés</div>
                </div>
                <div class="n2p-card">
                    <div class="n2p-card-icon green"><i class="fas fa-chart-line"></i></div>
                    <div class="n2p-card-title">Suivi temps réel</div>
                    <div class="n2p-card-desc">Tableau de bord de production en direct</div>
                </div>
                <div class="n2p-card">
                    <div class="n2p-card-icon purple"><i class="fas fa-file-export"></i></div>
                    <div class="n2p-card-title">Export données</div>
                    <div class="n2p-card-desc">Exportez vers vos outils métiers existants</div>
                </div>
            </div>

            <a href="https://nest2prod.com" target="_blank" rel="noopener" class="n2p-cta">
                Découvrir la suite Nest2Prod <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        {{-- Right: login card --}}
        <div class="n2p-right">
            <div class="n2p-form-card">
                <div class="n2p-form-header">
                    <div class="n2p-form-logo">
                        Nest<span style="color:var(--green)">2</span>Prod <span style="color:var(--blue)">ERP</span>
                    </div>
                    <div class="n2p-form-sub">Connectez-vous à votre espace</div>
                </div>

                @if(session('message'))
                    <div class="n2p-alert">
                        <i class="fas fa-circle-exclamation"></i>
                        {{ session('message') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="n2p-alert">
                        <i class="fas fa-circle-exclamation"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" id="loginForm">
                    @csrf

                    {{-- Email / Username --}}
                    <div class="n2p-field">
                        <label for="n2p-email">
                            @if(env('AUTH_DRIVER') === 'ldap') Nom d'utilisateur @else Email @endif
                        </label>
                        <div class="n2p-input-wrap">
                            @if(env('AUTH_DRIVER') === 'ldap')
                                <input type="text" id="n2p-email" name="username"
                                    value="{{ old('username') }}"
                                    placeholder="nom.prenom"
                                    class="{{ $errors->has('username') ? 'is-invalid' : '' }}"
                                    autofocus>
                                <span class="n2p-input-icon"><i class="fas fa-user"></i></span>
                                @error('username')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            @else
                                <input type="email" id="n2p-email" name="email"
                                    value="{{ old('email') }}"
                                    placeholder="vous@entreprise.com"
                                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                    autofocus>
                                <span class="n2p-input-icon"><i class="fas fa-envelope"></i></span>
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="n2p-field">
                        <label for="n2p-password">Mot de passe</label>
                        <div class="n2p-input-wrap">
                            <input type="password" id="n2p-password" name="password"
                                placeholder="••••••••"
                                class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                            <span class="n2p-input-icon clickable" id="pwToggle">
                                <i class="fas fa-eye" id="n2p-eye"></i>
                            </span>
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Mode selector --}}
                    @if(config('auth.login_mode_selector_enabled'))
                        <div class="n2p-field n2p-mode-wrap">
                            <label>Mode d'affichage</label>
                            <div class="n2p-input-wrap">
                                <select name="modeView">
                                    <option value="desktop">Desktop</option>
                                    <option value="workshop">Workshop (Beta)</option>
                                </select>
                                <span class="n2p-input-icon"><i class="fas fa-display"></i></span>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="modeView" value="desktop">
                    @endif

                    {{-- Remember / forgot --}}
                    <div class="n2p-row-remember">
                        <label class="n2p-remember">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span class="custom-check"></span>
                            Se souvenir de moi
                        </label>
                        <a href="{{ url('forgot/password') }}" class="n2p-forgot">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="n2p-btn" id="submitBtn">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Se connecter
                    </button>
                </form>

                <div class="n2p-register">
                    Pas encore de compte ?
                    <a href="https://nest2prod.com/contact" target="_blank" rel="noopener">Contactez Nest2Prod</a>
                </div>
            </div>
        </div>

    </main>

    {{-- Footer --}}
    <footer class="n2p-footer">
        <span>&copy; {{ date('Y') }} Nest2Prod &mdash; Tous droits réservés</span>
        <span>
            <a href="https://nest2prod.com" target="_blank" rel="noopener">nest2prod.com</a>
            <a href="https://nest2prod.com/contact" target="_blank" rel="noopener">Contact</a>
        </span>
    </footer>

    <script>
        // Password toggle
        document.getElementById('pwToggle').addEventListener('click', function () {
            const input = document.getElementById('n2p-password');
            const icon  = document.getElementById('n2p-eye');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        // Ripple on submit
        document.getElementById('submitBtn').addEventListener('click', function (e) {
            const btn = this;
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const rect = btn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
            btn.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        });

        // Loading state
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion…';
            btn.disabled = true;
        });
    </script>
</body>
</html>
