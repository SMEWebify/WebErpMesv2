{{--
    Modèle « technique » du devis, de la commande et de l'AR : aucun prix, aucun
    total, aucune TVA. Désignation, quantités, caractéristiques, exigences, délai.

    Modèle « prices": false » (layout.json) : la vue ne reçoit ni calculateur, ni
    montant, ni ligne Eloquent ($Document->Lines est vide), seulement
    $layoutRows (App\Services\Documents\SalesLayoutRows::rows), sans prix.
    Pas de lien public non plus : la page publique montre les prix.
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <title>{{ $typeDocumentName }} - #{{ $Document->code }}</title>
        @include('print.partials.layout-sales-styles')
        @include('print.partials.custom-styles')
    </head>
    <body>
        @include('print.partials.layout-sales-frame', ['subtitle' => __('pdf_layouts.technical_subtitle')])

        <main>
            <table style="width: 100%;">
                <thead>
                    <tr style="background-color: grey;">
                        <th align="center">{{ __('pdf_layouts.designation') }}</th>
                        <th align="center">{{ __('general_content.characteristics_trans_key') }}</th>
                        <th align="center">{{ __('general_content.qty_trans_key') }}</th>
                        <th align="center">{{ __('general_content.unit_trans_key') }}</th>
                        <th align="center">{{ __('general_content.delivery_date_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($layoutRows as $row)
                    @if(in_array($row['type'], ['article', 'package'], true))
                    <tr class="line-main">
                        <td style="width: 35%;">
                            <strong>{{ $row['label'] }}</strong><br>
                            <span class="muted">{{ $row['code'] }}@if(!empty($row['ind'])) · {{ __('general_content.plan_index_short_trans_key') }} {{ $row['ind'] }}@endif</span>
                            @if(!empty($row['equipment_tags']))<br><span class="muted">{{ __('general_content.equipment_tag_trans_key') }} : {{ $row['equipment_tags'] }}</span>@endif
                            @if(!empty($row['long_description']))<div class="facts">{!! nl2br(e($row['long_description'])) !!}</div>@endif
                        </td>
                        <td>@include('print.partials.layout-line-facts', ['row' => $row, 'description' => false])</td>
                        <td align="center">{{ $row['qty'] }}</td>
                        <td align="center">{{ $row['unit'] }}</td>
                        <td align="center">{{ $row['delivery_date'] ?: '-' }}</td>
                    </tr>
                    @elseif($row['type'] === 'section')
                    <tr class="line-section"><td colspan="5">{{ $row['label'] }}</td></tr>
                    @elseif($row['type'] === 'text')
                    <tr class="line-text"><td colspan="5">{!! nl2br(e($row['label'])) !!}</td></tr>
                    @endif
                @empty
                    <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}" />
                @endforelse
                </tbody>
            </table>

            @if($Document->comment)
            <hr style="color: #6c757d">
            <p><strong>{{ __('general_content.comment_trans_key') }} :</strong></p>
            <p class="muted">{!! nl2br(e($Document->comment)) !!}</p>
            @endif

            @foreach($estimates as $block)
            <div class="estimate">
                <h4>{{ $block['title'] }}</h4>
                <div>{!! nl2br(e($block['text'])) !!}</div>
            </div>
            @endforeach
        </main>
    </body>
</html>
