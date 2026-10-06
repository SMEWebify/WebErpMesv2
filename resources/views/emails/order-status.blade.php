<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->code }} — {{ $statusLabel }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:48px 20px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:32px 48px;border-radius:16px 16px 0 0;text-align:center;">
                        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{{ $factory?->name ?? config('mail.from.name') }}</h1>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#ffffff;padding:40px 48px;">

                        @if($order->contact)
                            <p style="margin:0 0 20px;color:#1e293b;font-size:15px;">
                                {{ __('general_content.order_status_email_greeting_trans_key', ['name' => trim(($order->contact->first_name ?? '') . ' ' . ($order->contact->name ?? ''))]) }}
                            </p>
                        @endif

                        <p style="margin:0 0 28px;color:#475569;font-size:15px;line-height:1.7;">{{ $intro }}</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;background-color:#f8fafc;border-left:3px solid #2563eb;border-radius:0 8px 8px 0;">
                            <tr>
                                <td style="padding:16px 20px;font-size:14px;color:#1e293b;line-height:1.9;">
                                    <strong>{{ __('general_content.order_trans_key') }}</strong> : {{ $order->code }}<br>
                                    @if($order->customer_reference)
                                        <strong>{{ __('general_content.customer_reference_trans_key') }}</strong> : {{ $order->customer_reference }}<br>
                                    @endif
                                    <strong>{{ __('general_content.order_status_email_step_trans_key') }}</strong> : {{ $statusLabel }}
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;color:#64748b;font-size:14px;line-height:1.7;">
                            {{ __('general_content.order_status_email_outro_trans_key') }}
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#f8fafc;padding:20px 48px;border-radius:0 0 16px 16px;border-top:1px solid #e2e8f0;text-align:center;">
                        <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.8;">
                            {{ __('general_content.order_status_email_footer_trans_key') }}
                            @if($factory)
                                <br>{{ $factory->name }}@if($factory->phone_number) · {{ $factory->phone_number }}@endif @if($factory->mail) · {{ $factory->mail }}@endif
                            @endif
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
