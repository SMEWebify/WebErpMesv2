<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmez votre adresse email - {{ config('branding.app_name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:48px 20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:36px 48px;border-radius:16px 16px 0 0;text-align:center;">
                        <p style="margin:0 0 8px;font-size:36px;line-height:1;">⚙️</p>
                        <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">{{ config('branding.app_name') }}</h1>
                        <p style="margin:6px 0 0;color:#64748b;font-size:13px;letter-spacing:2px;text-transform:uppercase;">ERP · MES · Industrie</p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#ffffff;padding:48px 48px 40px;">

                        {{-- Icon --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                            <tr>
                                <td align="center">
                                    <div style="display:inline-block;background-color:#eff6ff;border-radius:50%;width:72px;height:72px;line-height:72px;text-align:center;font-size:36px;">
                                        ✉️
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <h2 style="margin:0 0 8px;color:#1e293b;font-size:24px;font-weight:700;text-align:center;">
                            Confirmez votre adresse email
                        </h2>
                        <p style="margin:0 0 28px;color:#64748b;font-size:15px;line-height:1.7;text-align:center;">
                            Bonjour {{ $user->name }},<br>
                            Pour activer votre compte, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.
                        </p>

                        {{-- CTA --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $url }}"
                                       style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:15px 40px;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:0.3px;">
                                        Confirmer mon adresse email →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        {{-- Expiry notice --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                            <tr>
                                <td style="padding:14px 18px;background-color:#fefce8;border:1px solid #fde68a;border-radius:8px;">
                                    <table width="100%" cellpadding="0" cellspacing="0"><tr>
                                        <td width="24" style="vertical-align:top;padding-top:1px;font-size:16px;">⏱</td>
                                        <td style="padding-left:10px;color:#78350f;font-size:13px;line-height:1.6;">
                                            Ce lien est valable <strong>60 minutes</strong>. Passé ce délai, vous devrez en demander un nouveau depuis la page de connexion.
                                        </td>
                                    </tr></table>
                                </td>
                            </tr>
                        </table>

                        <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 24px;">

                        {{-- Fallback link --}}
                        <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.8;text-align:center;">
                            Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur&nbsp;:<br>
                            <span style="color:#2563eb;word-break:break-all;font-size:12px;">{{ $url }}</span>
                        </p>

                        <p style="margin:24px 0 0;color:#94a3b8;font-size:12px;text-align:center;">
                            Si vous n'avez pas créé de compte sur {{ config('branding.app_name') }}, ignorez cet email.
                        </p>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#f8fafc;padding:24px 48px;border-radius:0 0 16px 16px;border-top:1px solid #e2e8f0;text-align:center;">
                        <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.8;">
                            Cet email est envoyé automatiquement - merci de ne pas y répondre.<br>
                            &copy; {{ date('Y') }} {{ config('branding.app_name') }} - Tous droits réservés.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
