<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('derogations.request_title') }} - {{ $Derogation->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #222; margin: 36px 46px; }
        h1 { font-size: 17pt; margin: 0 0 6px; }
        h2 { font-size: 11pt; margin: 22px 0 6px; }
        .muted { color: #666; font-size: 9pt; }
        .summary { width: 100%; border-collapse: collapse; margin: 20px 0 6px; }
        .summary td { border: 1px solid #ccc; padding: 7px 9px; vertical-align: top; }
        .summary td.label { width: 34%; background: #f4f6f9; font-weight: bold; }
        .text { border: 1px solid #ccc; padding: 8px 10px; white-space: pre-line; line-height: 1.45; }
        .statement { line-height: 1.6; margin: 24px 0 18px; }
        .sign { width: 100%; border-collapse: collapse; }
        .sign td { border: 1px solid #999; padding: 9px; vertical-align: top; }
        .sign td.label { width: 34%; font-weight: bold; }
        .sign td.box { height: 80px; }
        /* Marqueurs invisibles : DocuSign y accroche les champs à remplir. */
        .anchor { color: #ffffff; font-size: 6pt; }
    </style>
</head>
<body>
    <h1>{{ __('derogations.request_title') }}</h1>
    <div class="muted">{{ $Factory->name ?? config('app.name') }} — {{ now()->format('d/m/Y') }}</div>

    <table class="summary">
        <tr>
            <td class="label">{{ __('derogations.request_reference') }}</td>
            <td>{{ $Derogation->code }}@if($Derogation->label && $Derogation->label !== $Derogation->code) - {{ $Derogation->label }}@endif</td>
        </tr>
        <tr>
            <td class="label">{{ __('derogations.customer') }}</td>
            <td>{{ $Derogation->companie?->label }}</td>
        </tr>
        @if($Derogation->orderLine)
        <tr>
            <td class="label">{{ __('derogations.order_line') }}</td>
            <td>
                {{ $Derogation->orderLine->order?->code }}@if($Derogation->orderLine->order?->customer_reference) ({{ $Derogation->orderLine->order->customer_reference }})@endif
                — {{ $Derogation->orderLine->code }} {{ $Derogation->orderLine->label }}
                — {{ __('general_content.qty_trans_key') }} {{ $Derogation->orderLine->qty }}
            </td>
        </tr>
        @endif
        @if($Derogation->QualityNonConformity)
        <tr>
            <td class="label">{{ __('derogations.non_conformity') }}</td>
            <td>{{ $Derogation->QualityNonConformity->code }}@if($Derogation->QualityNonConformity->qty) — {{ __('general_content.qty_trans_key') }} {{ $Derogation->QualityNonConformity->qty }}@endif</td>
        </tr>
        @endif
    </table>

    <h2>{{ __('general_content.problem_description_trans_key') }}</h2>
    <div class="text">{{ $Derogation->pb_descp }}</div>

    <h2>{{ __('general_content.proposal_description_trans_key') }}</h2>
    <div class="text">{{ $Derogation->proposal }}</div>

    <p class="statement">
        {{ __('derogations.request_statement', ['code' => $Derogation->code, 'factory' => $Factory->name ?? config('app.name')]) }}
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
