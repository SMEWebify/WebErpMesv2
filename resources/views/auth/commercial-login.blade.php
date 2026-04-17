<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Nest2Prod ERP</title>
    <link rel="stylesheet" href="vendor/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="vendor/fontawesome-free/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        /* ── Top bar ── */
        .n2p-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 40px;
            background: transparent;
        }
        .n2p-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
        }
        .n2p-logo-badge {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 2px 7px;
            letter-spacing: 0.05em;
        }
        .n2p-topbar-link {
            font-size: 13px;
            color: #374151;
            text-decoration: none;
        }
        .n2p-topbar-link:hover { color: #111827; }

        /* ── Main content ── */
        .n2p-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            gap: 64px;
        }

        /* ── Left column ── */
        .n2p-left {
            flex: 1;
            max-width: 520px;
        }
        .n2p-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 13px;
            color: #374151;
            margin-bottom: 28px;
        }
        .n2p-pill-dot {
            width: 7px;
            height: 7px;
            background: #22c55e;
            border-radius: 50%;
        }
        .n2p-heading {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.15;
            color: #111827;
            margin-bottom: 16px;
        }
        .n2p-heading .green { color: #22c55e; }
        .n2p-sub {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* ── Feature cards ── */
        .n2p-cards {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
        }
        .n2p-card {
            flex: 1;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }
        .n2p-card-icon {
            width: 38px;
            height: 38px;
            background: #f0f9ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: #0ea5e9;
            font-size: 15px;
        }
        .n2p-card-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .n2p-card-desc {
            font-size: 12px;
            color: #9ca3af;
            line-height: 1.5;
        }

        .n2p-cta {
            font-size: 14px;
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
        }
        .n2p-cta:hover { color: #16a34a; text-decoration: none; }

        /* ── Right column / form card ── */
        .n2p-right {
            width: 380px;
            flex-shrink: 0;
        }
        .n2p-form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 36px 32px;
        }
        .n2p-form-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin-bottom: 4px;
        }
        .n2p-form-sub {
            font-size: 14px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 28px;
        }

        /* ── Form fields ── */
        .n2p-field {
            margin-bottom: 16px;
        }
        .n2p-field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        .n2p-input-wrap {
            position: relative;
        }
        .n2p-input-wrap input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 40px 10px 14px;
            font-size: 14px;
            color: #111827;
            background: #fff;
            outline: none;
            transition: border-color .15s;
        }
        .n2p-input-wrap input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }
        .n2p-input-wrap input.is-invalid {
            border-color: #ef4444;
        }
        .n2p-input-icon {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            cursor: default;
        }
        .n2p-input-icon.clickable { cursor: pointer; }
        .invalid-feedback { font-size: 12px; color: #ef4444; margin-top: 4px; display: block; }

        /* ── Remember / forgot row ── */
        .n2p-row-remember {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .n2p-remember {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #374151;
        }
        .n2p-remember input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: #3b82f6;
            cursor: pointer;
        }
        .n2p-forgot {
            font-size: 13px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .n2p-forgot:hover { color: #2563eb; text-decoration: none; }

        /* ── Submit button ── */
        .n2p-btn {
            width: 100%;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            margin-bottom: 16px;
        }
        .n2p-btn:hover { background: #2563eb; }

        .n2p-register {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .n2p-register a {
            color: #3b82f6;
            font-weight: 500;
            text-decoration: none;
        }
        .n2p-register a:hover { color: #2563eb; }

        /* ── Mode selector ── */
        .n2p-mode-wrap {
            margin-bottom: 16px;
        }
        .n2p-mode-wrap select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            color: #111827;
            background: #fff;
            outline: none;
        }
        .n2p-mode-wrap select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }

        /* ── Footer ── */
        .n2p-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 40px;
            font-size: 12px;
            color: #9ca3af;
        }
        .n2p-footer a {
            color: #9ca3af;
            text-decoration: none;
            margin-left: 16px;
        }
        .n2p-footer a:hover { color: #6b7280; }

        @media (max-width: 900px) {
            .n2p-main { flex-direction: column; gap: 32px; padding: 24px 20px; }
            .n2p-left { max-width: 100%; }
            .n2p-right { width: 100%; max-width: 420px; }
            .n2p-heading { font-size: 30px; }
        }
        @media (max-width: 600px) {
            .n2p-topbar { padding: 14px 20px; }
            .n2p-footer { padding: 14px 20px; flex-direction: column; gap: 6px; text-align: center; }
            .n2p-cards { flex-direction: column; }
        }
    </style>
</head>
<body>

    {{-- Top bar --}}
    <nav class="n2p-topbar">
        <a href="#" class="n2p-logo">
            Nest<span style="color:#22c55e">2</span>Prod
            <span class="n2p-logo-badge">ERP</span>
        </a>
        <a href="https://nest2prod.com" target="_blank" rel="noopener" class="n2p-topbar-link">
            nest2prod.com&nbsp;<i class="fas fa-external-link-alt" style="font-size:11px"></i>
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
                <span class="green">pour</span> <span style="color:#3b82f6">l'industrie</span>
            </h1>

            <p class="n2p-sub">
                Centralisez vos commandes, stocks et plannings au sein d'une
                plateforme unifiée, propulsée par l'intelligence artificielle.
            </p>

            <div class="n2p-cards">
                <div class="n2p-card">
                    <div class="n2p-card-icon"><i class="fas fa-sliders-h"></i></div>
                    <div class="n2p-card-title">Gestion ERP</div>
                    <div class="n2p-card-desc">Commandes, stocks et planification centralisés</div>
                </div>
                <div class="n2p-card">
                    <div class="n2p-card-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="n2p-card-title">Suivi temps réel</div>
                    <div class="n2p-card-desc">Tableau de bord de production en direct</div>
                </div>
                <div class="n2p-card">
                    <div class="n2p-card-icon"><i class="fas fa-upload"></i></div>
                    <div class="n2p-card-title">Export données</div>
                    <div class="n2p-card-desc">Exportez vers vos outils métiers existants</div>
                </div>
            </div>

            <a href="https://nest2prod.com" target="_blank" rel="noopener" class="n2p-cta">
                Découvrir la suite Nest2Prod &rarr;
            </a>
        </div>

        {{-- Right: login card --}}
        <div class="n2p-right">
            <div class="n2p-form-card">
                <div class="n2p-form-title">Nest<span style="color:#22c55e">2</span>Prod <span style="color:#3b82f6">ERP</span></div>
                <div class="n2p-form-sub">Connectez-vous à votre espace</div>

                @if(session('message'))
                    <div class="alert alert-danger" style="font-size:13px;padding:10px 14px;border-radius:8px;">
                        {{ session('message') }}
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST">
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
                            <span class="n2p-input-icon clickable" onclick="togglePassword()">
                                <i class="fas fa-eye" id="n2p-eye"></i>
                            </span>
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Mode selector --}}
                    @if(config('auth.login_mode_selector_enabled'))
                        <div class="n2p-mode-wrap">
                            <select name="modeView">
                                <option value="desktop">Desktop</option>
                                <option value="workshop">Workshop (Beta)</option>
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="modeView" value="desktop">
                    @endif

                    {{-- Remember / forgot --}}
                    <div class="n2p-row-remember">
                        <label class="n2p-remember">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Se souvenir de moi
                        </label>
                        <a href="{{ url('forgot/password') }}" class="n2p-forgot">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="n2p-btn">Se connecter</button>
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
        function togglePassword() {
            var input = document.getElementById('n2p-password');
            var icon  = document.getElementById('n2p-eye');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
