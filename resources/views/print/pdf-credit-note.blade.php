<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>{{ $typeDocumentName}} - #{{ $Document->code }}</title>
        <style type="text/css">
            @page {
                margin: 350px 25px 50px 25px;
            }
            body {
                margin: 0px; 
                border: 1px solid {{ $Factory->pdf_header_font_color }};
            }
            * {
                font-family: Verdana, Arial, sans-serif;
            }
            a {
                color: #000;
                text-decoration: none;
            }
            table {
                font-size: smaller;
                border-collapse:collapse;
            }
            tfoot tr td {
                font-weight: bold;
                font-size: smaller;
            }
            table th
            {
                padding: 10px;
                margin-top:30px;
            }
            header { position: fixed; top: -350px; left: 0px; right: 0px; ; height: 350px; }
            footer { position: fixed; bottom: -50px; left: 0px; right: 0px; background-color: {{ $Factory->pdf_header_font_color }}; height: 50px; }
            footer .pagenum:before {
                content: counter(page);
            }
        </style>
        @include('print.partials.custom-styles')
    </head>
    <body>
        <header>
            <table width="100%; border: none; ">
                <tr>
                    <td align="left" style="width: 50%; background-color: {{ $Factory->pdf_header_font_color }}">
                    </td>
                    <td colspan="2" align="center" style="width: 400%; background-color: {{ $Factory->pdf_header_font_color }}">
                        <h2>{{ $typeDocumentName }}</h2>
                        <h3>{{ $Document->code }}</h3>
                    </td>
                </tr>
                <tr>
                    <td align="left" style="width: 40%;">
                        <h3>{{ $Factory->name }}</h3>
                        <pre>
{{ $Factory->address }}
{{ $Factory->zipcode }} {{ $Factory->city }}
{{ __('general_content.phone_trans_key') }} : {{ $Factory->phone_number }}
{{ __('general_content.email_trans_key') }} : {{ $Factory->mail }}
<br />
<br />
{{ __('general_content.date_trans_key') }} : {{ date('Y-m-d') }}
                        </pre>
                    </td>
                    <td align="left" style="width: 50%;">
                        @if($Document->type == 1 || empty($Document->type))
                            <h3>{{ $Document->companie['label'] }} </h3>
                            <pre>
{{ $Document->contact['civility'] }} {{ $Document->contact['first_name'] }} {{ $Document->contact['name'] }}
{{ $Document->adresse['adress'] }}
{{ $Document->adresse['zipcode'] }} {{ $Document->adresse['city'] }} {{ $Document->adresse['province'] ?? '' }}
{{ $Document->adresse['country'] }}
{{ __('general_content.phone_trans_key') }} : {{ $Document->contact['number'] }}
{{ __('general_content.email_trans_key') }} : {{ $Document->contact['mail'] }}
<br />
{{ __('general_content.identifier_trans_key') }}: {{ $Document->customer_reference }}
                            </pre>
                        @else
<h1>{{ __('general_content.internal_order_trans_key') }}</h1>
                        @endif
                    </td>
                </tr>
            </table>
        </header>

        <footer>
            <div>
                <table width="100%">
                    <tr>
                        <td align="left" style="width: 40%;">
                            &copy; {{ date('Y') }} - {{ $Factory->name }}
                        </td>
                        <td align="right" style="width: 40%;">
                            <div class="pagenum-container">Page <span class="pagenum"></span></div>
                        </td>
                    </tr>
                </table>
            </div>
        </footer>

        <main>
            <table style="width: 100%; " >
                <thead >
                    <tr style="background-color: grey;">
                        <th align="center">{{ __('general_content.order_trans_key') }}</th>
                        <th align="center">{{ __('general_content.description_trans_key') }}</th>
                        <th align="center">{{ __('general_content.qty_trans_key') }}</th>
                        <th align="center">{{ __('general_content.unit_trans_key') }}</th>
                        <th align="center">{{ __('general_content.price_trans_key') }}</th>
                        <th align="center">{{ __('general_content.discount_trans_key') }}</th>
                        <th align="center">{{ __('general_content.vat_trans_key') }}</th>
                        <th align="center">{{ __('general_content.delivery_date_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($Document->Lines as $DocumentLine)
                    <tr>
                        <td align="center">{{ $DocumentLine->OrderLine->order['code'] }}</td>
                        <td>
                            {{ $DocumentLine->OrderLine['label'] }}<br>
                            <span style="color: #6c757d">{{ $DocumentLine->OrderLine['code'] }}</span>
                        </td>
                        <td>{{ $DocumentLine->qty }}</td>
                        <td>{{ $DocumentLine->OrderLine->Unit['label'] }}</td>
                        <td>{{ $normalizeCurrency($DocumentLine->OrderLine->formatted_selling_price) }}</td>
                        <td align="center">{{ $DocumentLine->OrderLine->discount }} %</td>
                        <td>{{ $DocumentLine->OrderLine->VAT['rate'] }} %</td>
                        @if($DocumentLine->OrderLine->delivery_date )
                        <td align="center">{{ $DocumentLine->OrderLine->delivery_date }}</td>
                        @else
                        <td align="center">-</td>
                        @endif
                    </tr>
                    @empty
                        <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
            </table>
            <hr style="color: #6c757d">
            <table width="100%">
                <tr>
                    <td align="left" style="width: 50%;">
                        @if($Document->type == 1)
                        <p class="lead"><strong>{{ __('general_content.payment_methods_trans_key') }}:</strong> {{ $Document->payment_method['label'] }}</p>
                        <p class="lead"><strong>{{ __('general_content.payment_conditions_trans_key') }}:</strong> {{ $Document->payment_condition['label'] }}</p>
                        @endif
                        @if($Document->comment)
                        <p class="lead"><strong>{{ __('general_content.comment_trans_key') }} :</strong></p>
                        <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                            {{  $Document->comment }}
                        </p>
                        @endif
                    </td>
                    <td align="right" style="width: 50%;">
                        <table width="80%">
                            <tr>
                                <th align="right" style="width:50%">{{ __('general_content.sub_total_trans_key') }}:</th> 
                                <td align="right" style="width:30%">{{ number_format( $subPrice, 2, '.', ',') }} {{ $Factory->curency }} </td>
                            </tr>
                            @forelse($vatPrice as $key => $value)
                            <tr>
                                <td align="right" style="width:50%">{{ __('general_content.tax_trans_key') }} <?= $vatPrice[$key][0] ?> %</td> 
                                <td align="right" style="width:30%"><?=  number_format( $vatPrice[$key][1], 2, '.', ',') ?> {{ $Factory->curency }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td align="right" style="width:50%">{{ __('general_content.no_tax_trans_key') }}</td> 
                                <td align="right" style="width:30%"> </td>
                            </tr>
                            @endforelse
                            <tr  style=" background-color: {{ $Factory->pdf_header_font_color }}">
                                <th align="right" style="width:50%">{{ __('general_content.total_trans_key') }} :</th> 
                                <td align="right" style="width:30%">{{ number_format( $totalPrices, 2, '.', ',') }} {{ $Factory->curency }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </main>
    </body>
</html>
