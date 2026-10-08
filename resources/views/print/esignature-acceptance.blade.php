<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('esignature.acceptance_title') }} - {{ $Quote->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #222; margin: 40px 50px; }
        h1 { font-size: 18pt; margin: 0 0 6px; }
        .muted { color: #666; font-size: 9pt; }
        .summary { width: 100%; border-collapse: collapse; margin: 28px 0; }
        .summary td { border: 1px solid #ccc; padding: 8px 10px; }
        .summary td.label { width: 38%; background: #f4f6f9; font-weight: bold; }
        .statement { line-height: 1.6; margin: 24px 0 36px; }
        .sign { width: 100%; border-collapse: collapse; }
        .sign td { border: 1px solid #999; padding: 10px; vertical-align: top; }
        .sign td.label { width: 38%; font-weight: bold; }
        .sign td.box { height: 90px; }
        /* Marqueurs invisibles : DocuSign y accroche les champs à remplir. */
        .anchor { color: #ffffff; font-size: 6pt; }
    </style>
</head>
<body>
    <h1>{{ __('esignature.acceptance_title') }}</h1>
    <div class="muted">{{ $Factory->name ?? config('app.name') }}</div>

    <table class="summary">
        <tr>
            <td class="label">{{ __('general_content.quote_trans_key') }}</td>
            <td>{{ $Quote->code }}@if($Quote->label && $Quote->label !== $Quote->code) - {{ $Quote->label }}@endif</td>
        </tr>
        <tr>
            <td class="label">{{ __('esignature.acceptance_customer') }}</td>
            <td>{{ $Quote->companie['label'] ?? '' }}</td>
        </tr>
        @if($Quote->customer_reference)
        <tr>
            <td class="label">{{ __('esignature.acceptance_customer_reference') }}</td>
            <td>{{ $Quote->customer_reference }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">{{ __('esignature.acceptance_total') }}</td>
            <td><strong>{{ $formattedTotal }}</strong></td>
        </tr>
    </table>

    <p class="statement">
        {{ __('esignature.acceptance_statement', ['code' => $Quote->code, 'total' => $formattedTotal]) }}
    </p>

    <table class="sign">
        <tr>
            <td class="label">{{ __('esignature.acceptance_signer') }}</td>
            <td><span class="anchor">{{ $anchorName }}</span></td>
        </tr>
        <tr>
            <td class="label">{{ __('esignature.acceptance_date') }}</td>
            <td><span class="anchor">{{ $anchorDate }}</span></td>
        </tr>
        <tr>
            <td class="label">{{ __('esignature.acceptance_signature') }}</td>
            <td class="box"><span class="anchor">{{ $anchorSign }}</span></td>
        </tr>
    </table>
</body>
</html>
