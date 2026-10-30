{{--
    Liste de colisage du BL (modèle « packing-list » du menu d'impression).
    Le BL lui-même (print/pdf-delivery) n'est pas touché.

    $packingList (App\Services\Documents\PackingList::build) :
      packages  colis : n°, code, type, dimensions, poids net / brut, lignes du BL rangées dedans
      unpacked  quantités du BL rangées dans aucun colis (« hors colis »)
      totals    nombre de colis, poids net et brut
    Aucun prix imprimé.
--}}
@php
    $weight = fn ($value) => \Illuminate\Support\Number::format((float) $value, maxPrecision: 2, locale: app()->getLocale()) . ' ' . __('packing_list.weight_unit');
    $size   = fn ($value) => \Illuminate\Support\Number::format((float) $value, maxPrecision: 2, locale: app()->getLocale());
    $count  = $packingList['totals']['count'];
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <title>{{ __('packing_list.title') }} - #{{ $Document->code }}</title>
        @include('print.partials.layout-sales-styles')
        <style type="text/css">
            .summary td { padding: 6px; border: 1px solid #ccc; }
            .package { margin-top: 14px; page-break-inside: avoid; }
            .package-head td { padding: 6px; background-color: #eeeeee; border-top: 1px solid #777; }
            .content th { padding: 4px; border-bottom: 1px solid #777; }
            .content td { padding: 4px; border-top: 1px solid #ccc; vertical-align: top; }
        </style>
        @include('print.partials.custom-styles')
    </head>
    <body>
        @include('print.partials.layout-sales-frame', [
            'typeDocumentName' => __('packing_list.title'),
            'subtitle'         => __('packing_list.delivery_note') . ' ' . $Document->code
                                  . ($Document->tracking_number ? ' · ' . __('packing_list.tracking_number') . ' : ' . $Document->tracking_number : ''),
        ])

        <main>
            <table class="summary" style="width: 100%;">
                <tr>
                    <td><strong>{{ __('packing_list.packages_count') }}</strong> : {{ $count }}</td>
                    <td><strong>{{ __('packing_list.total_net_weight') }}</strong> : {{ $weight($packingList['totals']['net_weight']) }}</td>
                    <td><strong>{{ __('packing_list.total_gross_weight') }}</strong> : {{ $weight($packingList['totals']['gross_weight']) }}</td>
                </tr>
            </table>

            @if($count === 0)
            <p class="muted">{{ __('packing_list.no_package') }}</p>
            @endif

            @foreach($packingList['packages'] as $package)
            <div class="package">
                <table style="width: 100%;">
                    <tr class="package-head">
                        <td style="width: 35%;">
                            <strong>{{ __('packing_list.package_of', ['number' => $package['number'], 'count' => $count]) }}</strong>
                            — {{ $package['code'] }}@if($package['type']) · {{ $package['type'] }}@endif
                        </td>
                        <td>{{ __('packing_list.dimensions') }} : {{ $size($package['length']) }} × {{ $size($package['width']) }} × {{ $size($package['height']) }}</td>
                        <td>{{ __('packing_list.net_weight') }} : {{ $weight($package['net_weight']) }}</td>
                        <td>{{ __('packing_list.gross_weight') }} : {{ $weight($package['gross_weight']) }}</td>
                    </tr>
                </table>
                @if($package['comment'])
                <div class="muted">{!! nl2br(e($package['comment'])) !!}</div>
                @endif
                @include('print.layouts.packing-list.partials.content', ['rows' => $package['lines'], 'empty' => __('packing_list.empty_package')])
            </div>
            @endforeach

            @if(!empty($packingList['unpacked']))
            <div class="package">
                <table style="width: 100%;">
                    <tr class="package-head">
                        <td><strong>{{ __('packing_list.unpacked') }}</strong> — <span class="muted">{{ __('packing_list.unpacked_help') }}</span></td>
                    </tr>
                </table>
                @include('print.layouts.packing-list.partials.content', ['rows' => $packingList['unpacked'], 'empty' => ''])
            </div>
            @endif

            @if($Document->comment)
            <hr style="color: #6c757d">
            <p><strong>{{ __('general_content.comment_trans_key') }} :</strong></p>
            <p class="muted">{!! nl2br(e($Document->comment)) !!}</p>
            @endif
        </main>
    </body>
</html>
