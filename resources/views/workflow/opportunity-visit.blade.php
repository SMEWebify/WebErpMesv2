{{--
    Visite sur site — page autonome pensée pour le téléphone : pas de barre
    latérale AdminLTE, qui mange la moitié d'un écran de 375 px.
--}}
@php
    $address = $Opportunity->adresse
        ? trim(implode(' ', array_filter([$Opportunity->adresse->adress, $Opportunity->adresse->zipcode, $Opportunity->adresse->city])))
        : null;

    $visitProps = [
        'opportunity' => [
            'id' => $Opportunity->id,
            'label' => $Opportunity->label,
            'company' => $Opportunity->companie->label ?? null,
            'contact' => $Opportunity->contact
                ? trim($Opportunity->contact->first_name . ' ' . $Opportunity->contact->name)
                    . ($Opportunity->contact->mobile ? ' — ' . $Opportunity->contact->mobile : '')
                : null,
            'address' => $address ?: null,
            'url' => route('opportunities.show', $Opportunity->id),
        ],
        'visit' => $visit,
        'endpoints' => [
            'store' => route('opportunities.visits.store', $Opportunity->id),
            'filesList' => route('files.json.list'),
            'filesStore' => route('files.json.store'),
        ],
        'transcriptionAvailable' => $transcriptionAvailable,
        'imageAccept' => $imageAccept,
        'locale' => app()->getLocale(),
        'trans' => __('visits'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#343a40">
    <title>{{ __('visits.page_title') }} — {{ $Opportunity->label }}</title>

    @php($faviconDir = config('branding.favicon_dir', 'favicons'))
    <link rel="shortcut icon" href="{{ asset($faviconDir.'/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        body { background: #f1f5f9; }
        /* Cibles tactiles : 44 px minimum (recommandation Apple / WCAG 2.5.5). */
        .btn, .form-control, .form-select { min-height: 44px; }
        .btn-sm { min-height: 36px; }
        .min-w-0 { min-width: 0; }
    </style>
</head>
<body>
    <div data-react="opportunity-visit"
         data-props='@json($visitProps, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'></div>
</body>
</html>
