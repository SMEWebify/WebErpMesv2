<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — Nest2Prod ERP</title>
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
        .n2p-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 40px;
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
        .n2p-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .n2p-form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 36px 32px;
            width: 100%;
            max-width: 400px;
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
            margin-bottom: 24px;
        }
        .n2p-field { margin-bottom: 16px; }
        .n2p-field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        .n2p-input-wrap { position: relative; }
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
        .n2p-input-wrap input.is-invalid { border-color: #ef4444; }
        .n2p-input-icon {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }
        .invalid-feedback { font-size: 12px; color: #ef4444; margin-top: 4px; display: block; }
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
            margin-top: 4px;
        }
        .n2p-btn:hover { background: #2563eb; }
        .n2p-back {
            text-align: center;
            font-size: 13px;
        }
        .n2p-back a {
            color: #3b82f6;
            font-weight: 500;
            text-decoration: none;
        }
        .n2p-back a:hover { color: #2563eb; }
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
        @media (max-width: 600px) {
            .n2p-topbar { padding: 14px 20px; }
            .n2p-footer { padding: 14px 20px; flex-direction: column; gap: 6px; text-align: center; }
        }
    </style>
</head>
<body>

    <nav class="n2p-topbar">
        <a href="#" class="n2p-logo">
            Nest<span style="color:#22c55e">2</span>Prod
            <span class="n2p-logo-badge">ERP</span>
        </a>
        <a href="https://nest2prod.com" target="_blank" rel="noopener" class="n2p-topbar-link">
            nest2prod.com&nbsp;<i class="fas fa-external-link-alt" style="font-size:11px"></i>
        </a>
    </nav>

    <main class="n2p-main">
        <div class="n2p-form-card">
            <div class="n2p-form-title">Mot de passe oublié</div>
            <div class="n2p-form-sub">Entrez votre email pour recevoir un lien de réinitialisation</div>

            @if(session('status'))
                <div class="alert alert-success" style="font-size:13px;padding:10px 14px;border-radius:8px;">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('forgot.password.email') }}" method="POST">
                @csrf

                <div class="n2p-field">
                    <label for="n2p-email">Email</label>
                    <div class="n2p-input-wrap">
                        <input type="email" id="n2p-email" name="email"
                            value="{{ old('email') }}"
                            placeholder="vous@entreprise.com"
                            class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                            autofocus>
                        <span class="n2p-input-icon"><i class="fas fa-envelope"></i></span>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="n2p-btn">
                    <i class="fas fa-paper-plane" style="margin-right:6px;font-size:13px"></i>
                    Envoyer le lien
                </button>
            </form>

            <div class="n2p-back">
                <a href="{{ url('login') }}"><i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px"></i> Retour à la connexion</a>
            </div>
        </div>
    </main>

    <footer class="n2p-footer">
        <span>&copy; {{ date('Y') }} Nest2Prod &mdash; Tous droits réservés</span>
        <span>
            <a href="https://nest2prod.com" target="_blank" rel="noopener">nest2prod.com</a>
            <a href="https://nest2prod.com/contact" target="_blank" rel="noopener">Contact</a>
        </span>
    </footer>

</body>
</html>
