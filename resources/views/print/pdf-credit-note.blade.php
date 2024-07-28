
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $typeDocumentName}} - #{{ $Document->code }}</title>
    <style type="text/css">
        @page {
            margin: 0px;
        }
        body {
            margin: 0px;
        }
        * {
            font-family: Verdana, Arial, sans-serif;
        }
        a {
            color: #fff;
            text-decoration: none;
        }
        table {
            font-size: smaller;
        }
        tfoot tr td {
            font-weight: bold;
            font-size: smaller;
        }
        .invoice table {
            margin: 15px;
        }
        .invoice h3 {
            margin-left: 15px;
        }
        .information {
            background-color: {{ $Factory->pdf_header_font_color }};
            color: #FFF;
        }
        .information .logo {
            margin: 5px;
        }
        .information table {
            padding: 10px;
        }
    </style>
</head>
<body>

<div class="information">
    <table width="100%">
        <tr>
            <td align="left" style="width: 40%;">
                <h3>{{ $Factory->name }}</h3>
                <pre>
                {{ $Factory->address }}
                {{ $Factory->zipcode }} {{ $Factory->city }}
                {{ __('general_content.phone_trans_key') }} : {{ $Factory->phone_number }}
                {{ __('general_content.email_trans_key') }} : {{ $Factory->mail }}
                <br /><br />
        {{ __('general_content.date_trans_key') }} : {{ date('Y-m-d') }}
                </pre>
            </td>
            <td align="center">
                @if($Factory->picture)
                <img src="data:image/png;base64,{{ $image }}" alt="Logo" width="64" class="logo"/>
                @endif
            </td>
            <td align="right" style="width: 40%;">

                <h3>{{ $Document->companie['label'] }}</h3>
                <pre>
                    {{ $Document->contact['civility'] }} {{ $Document->contact['first_name'] }} {{ $Document->contact['name'] }}
                    {{ $Document->adresse['adress'] }}
                    {{ $Document->adresse['zipcode'] }} {{ $Document->adresse['city'] }}
                    {{ $Document->adresse['country'] }}
                    {{ __('general_content.phone_trans_key') }} : {{ $Document->contact['number'] }}
                    {{ __('general_content.email_trans_key') }} : {{ $Document->contact['mail'] }}
                </pre>
            </td>
        </tr>
    </table>
</div>
<br/>
<div class="invoice">
    <div align="center"><h3>{{ $typeDocumentName}} - #{{ $Document->code }}</h3></div>
    <hr style="color: #6c757d">
    <table width="100%">
        <thead>
            <tr>
                <th align="left">{{ __('general_content.order_trans_key') }}</th>
                <th align="left">{{ __('general_content.description_trans_key') }}</th>
                <th align="left">{{ __('general_content.qty_trans_key') }}</th>
                <th align="left">{{ __('general_content.unit_trans_key') }}</th>
                <th align="left">{{ __('general_content.price_trans_key') }}</th>
                <th align="left">{{ __('general_content.discount_trans_key') }}</th>
                <th align="left">{{ __('general_content.vat_trans_key') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($Document->Lines as $DocumentLine)
            <tr>
                <td>{{ $DocumentLine->OrderLine->order['code'] }}</td>
                <td>
                    {{ $DocumentLine->OrderLine['label'] }}<br>
                    <span style="color: #6c757d">{{ $DocumentLine->OrderLine['code'] }}</span>
                </td>
                <td>{{ $DocumentLine->qty }}</td>
                <td>{{ $DocumentLine->OrderLine->Unit['label'] }}</td>
                <td>{{ $DocumentLine->OrderLine->selling_price }}  {{ $Factory->curency }}</td>
                <td>{{ $DocumentLine->OrderLine->discount }} %</td>
                <td>{{ $DocumentLine->OrderLine->VAT['rate'] }} %</td>
                <td>-</td>
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
                        <th align="right" style="width:50%">Subtotal:</th>
                        <td align="right" style="width:30%">{{ $subPrice }} {{ $Factory->curency }} </td>
                    </tr>
                    @forelse($vatPrice as $key => $value)
                    <tr>
                        <td align="right" style="width:50%">Tax <?= $vatPrice[$key][0] ?> %</td>
                        <td align="right" style="width:30%"><?= $vatPrice[$key][1] ?> {{ $Factory->curency }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td align="right" style="width:50%">No Tax</td>
                        <td align="right" style="width:30%"> </td>
                    </tr>
                    @endforelse
                    <tr>
                        <th align="right" style="width:50%">Total:</th>
                        <td align="right" style="width:30%">{{ $totalPrices }} {{ $Factory->curency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>


<div class="information" style="position: absolute; bottom: 0;">
    <table width="100%">
        <tr>
            <td align="left" style="width: 50%;">
                &copy; {{ date('Y') }} - {{ $Factory->name }}
            </td>
            <td align="right" style="width: 50%;">
                {{ $Factory->mail }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>