{{-- Badge opérateur de l'écran de scan (docs/pointage-scan.md) : format carte
     de crédit, à imprimer puis plastifier. Codes en SVG : nets à toute échelle. --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('scan_station.badge.title') }} — {{ $user->name }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; margin: 0; padding: 15mm; }
        .badge-card {
            width: 85.6mm; height: 54mm; box-sizing: border-box;
            border: 1px dashed #888; border-radius: 3mm; padding: 3mm;
            display: grid; grid-template-columns: 26mm 1fr; grid-template-rows: 1fr auto; gap: 2mm;
        }
        .badge-qr svg { width: 26mm; height: 26mm; }
        .badge-name { font-size: 14pt; font-weight: bold; align-self: center; }
        .badge-title { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-barcode { grid-column: 1 / span 2; text-align: center; }
        .badge-barcode svg { width: 100%; height: 11mm; }
        .badge-code { font-family: monospace; font-size: 8pt; }
        .no-print { margin-top: 8mm; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="badge-card">
        <div class="badge-qr">{!! $qrSvg !!}</div>
        <div>
            <div class="badge-title">{{ __('scan_station.badge.title') }}</div>
            <div class="badge-name">{{ $user->name }}</div>
        </div>
        <div class="badge-barcode">
            {!! $barcodeSvg !!}
            <div class="badge-code">{{ $code }}</div>
        </div>
    </div>
    <div class="no-print">
        <button type="button" onclick="window.print()">{{ __('scan_station.badge.print') }}</button>
    </div>
</body>
</html>
