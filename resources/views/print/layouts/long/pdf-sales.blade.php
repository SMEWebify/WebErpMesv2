{{--
    Modèle « long » du devis, de la commande et de l'AR : le modèle court, plus
    pour chaque ligne la description longue, le plan principal en vignette et
    les caractéristiques / exigences, puis les blocs de l'estimation de projet
    cochés pour le PDF (devis).

    Variables : celles de print/pdf-sales, plus $layout, $layoutRows
    (App\Services\Documents\SalesLayoutRows::rows) et $estimates.
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
        @include('print.partials.layout-sales-frame')

        <main>
            <table style="width: 100%;">
                <thead>
                    <tr style="background-color: grey;">
                        <th align="center">{{ __('general_content.description_trans_key') }}</th>
                        <th align="center">{{ __('general_content.qty_trans_key') }}</th>
                        <th align="center">{{ __('general_content.unit_trans_key') }}</th>
                        <th align="center">{{ __('general_content.price_trans_key') }}</th>
                        <th align="center">{{ __('general_content.discount_trans_key') }}</th>
                        <th align="center">{{ __('pdf_layouts.line_amount') }}</th>
                        <th align="center">{{ __('general_content.delivery_date_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($layoutRows as $row)
                    @if(in_array($row['type'], ['article', 'package'], true))
                    <tr class="line-main">
                        <td>
                            <strong>{{ $row['label'] }}</strong><br>
                            <span class="muted">{{ $row['code'] }}@if(!empty($row['ind'])) · {{ __('general_content.plan_index_short_trans_key') }} {{ $row['ind'] }}@endif</span>
                            @if(!empty($row['equipment_tags']))<br><span class="muted">{{ __('general_content.equipment_tag_trans_key') }} : {{ $row['equipment_tags'] }}</span>@endif
                        </td>
                        <td align="center">{{ $row['qty'] }}</td>
                        <td align="center">{{ $row['unit'] }}</td>
                        <td align="right">{{ $row['unit_price'] }}</td>
                        <td align="center">{{ $row['discount'] !== null ? $row['discount'] . ' %' : '-' }}</td>
                        <td align="right">{{ $row['amount'] }}</td>
                        <td align="center">{{ $row['delivery_date'] ?: '-' }}</td>
                    </tr>
                    @if($row['plan'] || $row['long_description'] || $row['characteristics'] || $row['requirements'])
                    <tr class="line-detail">
                        <td colspan="7">
                            <table width="100%">
                                <tr>
                                    @if($row['plan'])
                                    <td style="width: 140px;"><img class="plan-thumb" src="{{ $row['plan'] }}" alt=""></td>
                                    @endif
                                    <td>@include('print.partials.layout-line-facts', ['row' => $row])</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                    @elseif($row['type'] === 'section')
                    <tr class="line-section"><td colspan="7">{{ $row['label'] }}</td></tr>
                    @elseif($row['type'] === 'subtotal')
                    <tr class="line-subtotal">
                        <td colspan="5" align="right">{{ $row['label'] }}</td>
                        <td align="right">{{ $row['amount'] }}</td>
                        <td></td>
                    </tr>
                    @elseif($row['type'] === 'text')
                    <tr class="line-text"><td colspan="7">{!! nl2br(e($row['label'])) !!}</td></tr>
                    @endif
                @empty
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}" />
                @endforelse
                </tbody>
            </table>
            <hr style="color: #6c757d">
            <table width="100%">
                <tr>
                    <td align="left" style="width: 50%; vertical-align: top;">
                        @if($Document->type == 1)
                        <p><strong>{{ __('general_content.payment_methods_trans_key') }}:</strong> {{ optional($Document->payment_method)['label'] }}</p>
                        <p><strong>{{ __('general_content.payment_conditions_trans_key') }}:</strong> {{ optional($Document->payment_condition)['label'] }}</p>
                        @endif
                        @if($Document->comment)
                        <p><strong>{{ __('general_content.comment_trans_key') }} :</strong></p>
                        <p class="muted">{!! nl2br(e($Document->comment)) !!}</p>
                        @endif
                        <p>
                            <a href="{{ Request::root() }}/guest/quote/{{ $Document->uuid }}"> {{ __('general_content.public_link_trans_key') }}</a>
                        </p>
                    </td>
                    <td align="right" style="width: 50%; vertical-align: top;">
                        <table width="80%">
                            <tr>
                                <th align="right" style="width:50%">{{ __('general_content.sub_total_trans_key') }}:</th>
                                <td align="right" style="width:30%">{{ $normalizeCurrency($formattedSubPrice) }}</td>
                            </tr>
                            @forelse($vatPrice as $key => $value)
                            <tr>
                                <td align="right" style="width:50%">{{ __('general_content.tax_trans_key') }} {{ $vatPrice[$key][0] }} %</td>
                                <td align="right" style="width:30%">{{ number_format($vatPrice[$key][1], 2, '.', ',') }} {{ $Factory->curency }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td align="right" style="width:50%">{{ __('general_content.no_tax_trans_key') }}</td>
                                <td align="right" style="width:30%"></td>
                            </tr>
                            @endforelse
                            <tr style="background-color: {{ $Factory->pdf_header_font_color }}">
                                <th align="right" style="width:50%">{{ __('general_content.total_trans_key') }} :</th>
                                <td align="right" style="width:30%">{{ $normalizeCurrency($formattedTotalPrice) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            @foreach($estimates as $block)
            <div class="estimate">
                {{-- Les coûts de l'estimation ne sont pas comptés dans le total du devis : on le dit. --}}
                <h4>{{ $block['title'] }}@if($block['cost']) — {{ $block['cost'] }} <span class="muted">({{ __('pdf_layouts.estimate_cost_not_included') }})</span>@endif</h4>
                <div>{!! nl2br(e($block['text'])) !!}</div>
            </div>
            @endforeach
        </main>
    </body>
</html>
