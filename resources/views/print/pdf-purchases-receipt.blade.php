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
                        <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.description_trans_key') }}</th>
                        <th>{{ __('general_content.supplier_ref_trans_key') }}</th>
                        <th>{{ __('general_content.qty_purchase_trans_key') }}</th>
                        <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($Document->Lines as $DocumentLine)
                    <tr>
                        <td>
                            {{ $DocumentLine->purchaseLines->purchase->code }}
                        </td>
                        <td>
                            @if($DocumentLine->purchaseLines->tasks->OrderLines ?? null)
                            {{ $DocumentLine->purchaseLines->tasks->OrderLines->order->code }}
                            @endif
                        </td>
                        <td>
                            @if($DocumentLine->purchaseLines->tasks_id ?? null)
                            #{{ $DocumentLine->purchaseLines->tasks->id }} {{ $DocumentLine->purchaseLines->tasks->code }} {{ $DocumentLine->purchaseLines->tasks->label }}
                            @else
                                {{ $DocumentLine->purchaseLines->label }}
                            @endif
                        </td>
                        <td>{{ $DocumentLine->purchaseLines->supplier_ref }}</td>
                        <td>{{ $DocumentLine->purchaseLines->qty  }}</td>
                        <td>{{ $DocumentLine->receipt_qty }}</td>
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