<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email - {{ \App\Models\Admin\Factory::value('name') ?? config('branding.app_name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 32px 40px;
            text-align: center;
        }
        .card-header .logo { font-size: 32px; margin-bottom: 8px; }
        .card-header h1 { color: #fff; font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
        .card-header p { color: #64748b; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; }
        .card-body { padding: 40px; }
        .icon-wrap {
            width: 72px; height: 72px; border-radius: 50%;
            background: #eff6ff;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; margin: 0 auto 24px;
        }
        h2 { color: #1e293b; font-size: 20px; font-weight: 700; text-align: center; margin-bottom: 12px; }
        .description { color: #64748b; font-size: 14px; line-height: 1.7; text-align: center; margin-bottom: 28px; }
        .btn {
            display: block; width: 100%;
            background: #2563eb; color: #fff;
            border: none; border-radius: 8px;
            padding: 13px 24px; font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; text-align: center;
            transition: background .15s;
        }
        .btn:hover { background: #1d4ed8; }
        .alert {
            padding: 12px 16px; border-radius: 8px; font-size: 13px;
            margin-bottom: 20px; text-align: center;
        }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .logout-link {
            display: block; text-align: center; margin-top: 20px;
            color: #94a3b8; font-size: 13px; text-decoration: none;
        }
        .logout-link:hover { color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="logo">⚙️</div>
            <h1>{{ \App\Models\Admin\Factory::value('name') ?? config('branding.app_name') }}</h1>
            <p>ERP · MES · Industrie</p>
        </div>
        <div class="card-body">
            <div class="icon-wrap">✉️</div>

            <h2>Vérifiez votre email</h2>
            <p class="description">
                Merci de vous être inscrit ! Avant de continuer, veuillez vérifier votre adresse email en cliquant sur le lien que nous venons de vous envoyer.<br><br>
                Si vous n'avez pas reçu l'email, cliquez sur le bouton ci-dessous pour en recevoir un nouveau.
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i>
                    Un nouveau lien de vérification a été envoyé à votre adresse email.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn">
                    <i class="fa fa-paper-plane" style="margin-right:6px;"></i>
                    Renvoyer l'email de vérification
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="#" onclick="this.closest('form').submit()" class="logout-link">
                    <i class="fa fa-sign-out-alt" style="margin-right:4px;"></i>
                    Se déconnecter
                </a>
            </form>
        </div>
    </div>
</body>
</html>
