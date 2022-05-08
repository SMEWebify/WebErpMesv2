
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
            background-color: #60A7A6;
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
            <td align="left" style="width: 40%;">
                <h3>{{ $Factory->name }}</h3>
                <pre>
                {{ $Factory->address }}
                {{ $Factory->zipcode }} {{ $Factory->city }}
                Phone : {{ $Factory->phone_number }}
                Mail : {{ $Factory->mail }}
                <br /><br />
        Date: {{ date('Y-m-d') }}
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
                    Phone : {{ $Document->contact['number'] }}
                    Mail : {{ $Document->contact['mail'] }}
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
                <th align="left">Order</th>
                <th align="left">Description</th>
                <th align="left">Qty</th>
                <th align="left">Unit</th>
                <th align="left">Selling price</th>
                <th align="left">Discount</th>
                <th align="left">VAT</th>
                <th align="left">Delivery date</th>
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
                <td>{{ $DocumentLine->selling_price }}  {{ $Factory->curency }}</td>
                <td>{{ $DocumentLine->OrderLine->discount }} %</td>
                <td>{{ $DocumentLine->OrderLine->VAT['rate'] }} %</td>
                @if($DocumentLine->OrderLine->delivery_date )
                <td>{{ $DocumentLine->OrderLine->delivery_date }}</td>
                @else
                <td>No date</td>
                @endif
            </tr>
            @empty
                <x-EmptyDataLine col="7" text="No line in this delivery found ..."  />
            @endforelse
        </tbody>
    </table>
    <hr style="color: #6c757d">
    <table width="100%">
        <tr>
            <td align="left" style="width: 50%;">
                @if($Document->comment)
                <p class="lead"><strong>Comment :</strong></p>
                <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                    {{  $Document->comment }}
                </p>
                @endif
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