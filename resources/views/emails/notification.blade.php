<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }} — {{ config('branding.app_name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:48px 20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:36px 48px;border-radius:16px 16px 0 0;text-align:center;">
                        <p style="margin:0 0 8px;font-size:36px;line-height:1;">{{ $icon }}</p>
                        <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">{{ config('branding.app_name') }}</h1>
                        <p style="margin:6px 0 0;color:#64748b;font-size:13px;letter-spacing:2px;text-transform:uppercase;">ERP · MES · Industrie</p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#ffffff;padding:48px 48px 40px;">

                        <h2 style="margin:0 0 6px;color:#1e293b;font-size:22px;font-weight:700;">
                            Bonjour {{ $notifiable->name }},
                        </h2>
                        <p style="margin:0 0 28px;color:#64748b;font-size:15px;line-height:1.7;">
                            {{ $line }}
                        </p>

                        {{-- Entity block --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                            <tr>
                                <td style="padding:16px 20px;background-color:#f8fafc;border-left:4px solid {{ $accentColor }};border-radius:0 8px 8px 0;">
                                    <table width="100%" cellpadding="0" cellspacing="0"><tr>
                                        <td width="32" style="vertical-align:middle;font-size:22px;">{{ $icon }}</td>
                                        <td style="padding-left:12px;">
                                            <strong style="color:#1e293b;font-size:15px;display:block;">{{ $entityLabel }}</strong>
                                            <span style="color:#64748b;font-size:13px;">{{ $code }}</span>
                                        </td>
                                    </tr></table>
                                </td>
                            </tr>
                        </table>

                        {{-- CTA --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center">
                                    <a href="{{ $actionUrl }}"
                                       style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:0.3px;">
                                        {{ $actionText }} →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:28px 0 0;color:#94a3b8;font-size:12px;text-align:center;line-height:1.8;">
                            Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur&nbsp;:<br>
                            <span style="color:#2563eb;word-break:break-all;font-size:12px;">{{ $actionUrl }}</span>
                        </p>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#f8fafc;padding:24px 48px;border-radius:0 0 16px 16px;border-top:1px solid #e2e8f0;text-align:center;">
                        <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.8;">
                            Cet email est envoyé automatiquement — merci de ne pas y répondre.<br>
                            &copy; {{ date('Y') }} {{ config('branding.app_name') }} — Tous droits réservés.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
