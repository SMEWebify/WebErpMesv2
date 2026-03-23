<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès en attente — {{ \App\Models\Admin\Factory::value('name') ?? config('branding.app_name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            color: #334155;
        }

        /* Topbar */
        .topbar {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-brand { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; }
        .topbar-brand span { font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
        .topbar-brand small { color: #64748b; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; display: block; }
        .topbar-user { display: flex; align-items: center; gap: 12px; }
        .topbar-user .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: #2563eb; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px;
        }
        .topbar-user .name { color: #cbd5e1; font-size: 14px; }
        .logout-btn {
            background: rgba(255,255,255,.08); color: #94a3b8;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 6px; padding: 6px 14px;
            font-size: 13px; cursor: pointer; text-decoration: none;
            transition: background .15s;
        }
        .logout-btn:hover { background: rgba(255,255,255,.14); color: #e2e8f0; }

        /* Main */
        .main { max-width: 900px; margin: 0 auto; padding: 48px 24px; }

        /* Hero */
        .hero {
            background: #fff;
            border-radius: 16px;
            padding: 48px 48px 40px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            margin-bottom: 32px;
        }
        .hero-icon {
            width: 80px; height: 80px; border-radius: 50%;
            background: #fef9c3; border: 2px solid #fde68a;
            display: flex; align-items: center; justify-content: center;
            font-size: 38px; margin: 0 auto 24px;
        }
        .hero h1 { font-size: 26px; font-weight: 700; color: #1e293b; margin-bottom: 12px; }
        .hero p { color: #64748b; font-size: 15px; line-height: 1.8; max-width: 520px; margin: 0 auto 28px; }

        .badge-pending {
            display: inline-flex; align-items: center; gap: 8px;
            background: #fef9c3; color: #854d0e;
            border: 1px solid #fde68a;
            border-radius: 999px; padding: 8px 20px;
            font-size: 13px; font-weight: 600;
        }
        .badge-pending i { font-size: 12px; }

        /* Section title */
        .section-title {
            font-size: 13px; font-weight: 700; color: #94a3b8;
            letter-spacing: 1.5px; text-transform: uppercase;
            margin-bottom: 16px;
        }

        /* Modules grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .module-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 22px;
            box-shadow: 0 1px 6px rgba(0,0,0,.05);
            border-left: 4px solid var(--color);
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .module-icon {
            width: 42px; height: 42px; border-radius: 10px;
            background: var(--bg);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .module-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .module-desc { font-size: 12px; color: #64748b; line-height: 1.6; }

        /* Footer card */
        .info-card {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .info-card i { color: #2563eb; font-size: 18px; margin-top: 2px; }
        .info-card p { color: #1d4ed8; font-size: 13px; line-height: 1.7; }
        .info-card p strong { color: #1e40af; }
    </style>
</head>
<body>

    {{-- Topbar --}}
    <div class="topbar">
        <a href="#" class="topbar-brand">
            <span>⚙️ {{ \App\Models\Admin\Factory::value('name') ?? config('branding.app_name') }}</span>
        </a>
        <div class="topbar-user">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <span class="name">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fa fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </div>

    <div class="main">

        {{-- Hero --}}
        <div class="hero">
            <div class="hero-icon">⏳</div>
            <h1>Votre compte est en cours d'activation</h1>
            <p>
                Votre adresse email est vérifiée. Un administrateur va vous attribuer vos droits d'accès très prochainement.<br>
                Vous recevrez une notification dès que votre compte sera activé.
            </p>
            <div class="badge-pending">
                <i class="fa fa-clock"></i>
                En attente d'attribution de rôle
            </div>
        </div>

        {{-- Modules --}}
        <p class="section-title">Ce qui vous attend sur la plateforme</p>

        <div class="grid">

            <div class="module-card" style="--color:#3b82f6;--bg:#eff6ff;">
                <div class="module-icon">👥</div>
                <div>
                    <div class="module-title">CRM</div>
                    <div class="module-desc">Gérez vos clients, prospects et contacts. Suivez vos opportunités commerciales.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#8b5cf6;--bg:#f5f3ff;">
                <div class="module-icon">📋</div>
                <div>
                    <div class="module-title">Devis</div>
                    <div class="module-desc">Créez et suivez vos devis. Calculez vos marges et transformez en commandes.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#10b981;--bg:#f0fdf4;">
                <div class="module-icon">📦</div>
                <div>
                    <div class="module-title">Commandes</div>
                    <div class="module-desc">Pilotez vos commandes clients de la réception à la livraison.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#f59e0b;--bg:#fffbeb;">
                <div class="module-icon">🏭</div>
                <div>
                    <div class="module-title">Production & MES</div>
                    <div class="module-desc">Planifiez et suivez vos ordres de fabrication. Gérez les temps opérateurs.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#06b6d4;--bg:#ecfeff;">
                <div class="module-icon">🚚</div>
                <div>
                    <div class="module-title">Livraisons</div>
                    <div class="module-desc">Créez vos bons de livraison et suivez vos expéditions en temps réel.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#ef4444;--bg:#fef2f2;">
                <div class="module-icon">🧾</div>
                <div>
                    <div class="module-title">Facturation</div>
                    <div class="module-desc">Émettez vos factures, gérez les acomptes et exportez votre FEC comptable.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#f97316;--bg:#fff7ed;">
                <div class="module-icon">🛒</div>
                <div>
                    <div class="module-title">Achats</div>
                    <div class="module-desc">Gérez vos fournisseurs, vos demandes d'achat et vos réceptions de matières.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#6366f1;--bg:#eef2ff;">
                <div class="module-icon">📊</div>
                <div>
                    <div class="module-title">Stocks</div>
                    <div class="module-desc">Suivez vos stocks en temps réel. Gérez vos emplacements et vos mouvements.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#14b8a6;--bg:#f0fdfa;">
                <div class="module-icon">✅</div>
                <div>
                    <div class="module-title">Qualité</div>
                    <div class="module-desc">Traitez vos non-conformités, dérogations et actions correctives qualité.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#ec4899;--bg:#fdf2f8;">
                <div class="module-icon">🤖</div>
                <div>
                    <div class="module-title">Pré-commandes IA</div>
                    <div class="module-desc">Anticipez vos besoins clients grâce à l'analyse prédictive par intelligence artificielle.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#64748b;--bg:#f8fafc;">
                <div class="module-icon">📅</div>
                <div>
                    <div class="module-title">Planification</div>
                    <div class="module-desc">Planifiez vos tâches, vos ressources et vos jalons de production.</div>
                </div>
            </div>

            <div class="module-card" style="--color:#0ea5e9;--bg:#f0f9ff;">
                <div class="module-icon">⏱</div>
                <div>
                    <div class="module-title">Temps & RH</div>
                    <div class="module-desc">Gérez les absences, les contrats et les pointages de vos collaborateurs.</div>
                </div>
            </div>

        </div>

        {{-- Info --}}
        <div class="info-card">
            <i class="fa fa-circle-info"></i>
            <p>
                <strong>Que faire maintenant ?</strong><br>
                Contactez votre administrateur système pour qu'il vous attribue un rôle. Si vous êtes l'administrateur, connectez-vous avec votre compte admin et accédez à <strong>Administration → Utilisateurs</strong> pour attribuer des rôles.
            </p>
        </div>

    </div>

</body>
</html>
