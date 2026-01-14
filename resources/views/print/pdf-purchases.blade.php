
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
                        @if($image)
                            <img src="data:image/png;base64,{{ $image }}" alt="Logo" style="height: 120px;"/>
                        @endif
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
                <thead>
                    <tr>
                        <th align="left">{{ __('general_content.order_trans_key') }}</th>
                        <th align="left">{{ __('general_content.description_trans_key') }}</th>
                        <th align="left">{{ __('general_content.supplier_ref_trans_key') }}</th>
                        <th align="left">{{ __('general_content.qty_trans_key') }}</th>
                        <th align="left">{{ __('general_content.price_trans_key') }}</th>
                        <th align="left">{{ __('general_content.discount_trans_key') }}</th>
                        <th align="left">{{ __('general_content.vat_trans_key') }}</th> 
                    </tr>
                </thead>
                <tbody>
                    @forelse($Document->Lines as $DocumentLine)
                    <tr>
                        <td>
                            @if($DocumentLine->tasks->OrderLines ?? null)
                            {{ $DocumentLine->tasks->OrderLines->order->code }}
                            @endif
                        </td>
                        <td>
                            @if($DocumentLine->tasks_id ?? null)
                            #{{ $DocumentLine->tasks->id }} {{ $DocumentLine->code }} {{ $DocumentLine->label }}
                            @else
                                {{ $DocumentLine->label }}
                            @endif
                        </td>
                        <td>{{ $DocumentLine->supplier_ref }}</td>
                        <td>{{ $DocumentLine->qty }}</td>
                        <td>{{ $normalizeCurrency($DocumentLine->formatted_selling_price) }}</td>
                        <td>{{ $DocumentLine->discount }} %</td>
                        <td> 
                            @if($DocumentLine->accounting_vats_id)
                            {{ $DocumentLine->VAT['rate'] }} %
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
            </table>
            <hr style="color: #6c757d">
            <table width="100%">
                <tr>
                    <td align="left" style="width: 50%;">
                        @if($Document->comment)
                        <p class="lead"><strong>{{ __('general_content.comment_trans_key') }} :</strong></p>
                        <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                            {{  $Document->comment }}
                        </p>
                        @endif
                    </td>
                </tr>
            </table>
        </main>
    </body>
</html>
