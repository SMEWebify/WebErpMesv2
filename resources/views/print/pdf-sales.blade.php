
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
                <!--<img src="/path/to/logo.png" alt="Logo" width="64" class="logo"/>-->
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
                    <br /><br />
            Identifier: {{ $Document->customer_reference }}
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
                <td>{{ $DocumentLine->label }}<br>
                <span style="color: #6c757d">{{ $DocumentLine->code }}</span>
                </td>
                <td>{{ $DocumentLine->qty }}</td>
                <td>{{ $DocumentLine->Unit['label'] }}</td>
                <td>{{ $DocumentLine->selling_price }}  {{ $Factory->curency }}</td>
                <td>{{ $DocumentLine->discount }} %</td>
                <td>{{ $DocumentLine->VAT['rate'] }} %</td>
                @if($DocumentLine->delivery_date )
                <td>{{ $DocumentLine->delivery_date }}</td>
                @else
                <td>No date</td>
                @endif
                
            </tr>
            @empty
                <x-EmptyDataLine col="7" text="No line ..."  />
            @endforelse
        </tbody>
        </table>
        
        <hr style="color: #6c757d">
        <table width="100%">
        <tr>
            <td align="left" style="width: 50%;">
                <p class="lead"><strong>Payment Methods:</strong> {{ $Document->payment_condition['label'] }}</p>
                <p class="lead"><strong>Payment Conditions:</strong> {{ $Document->payment_method['label'] }}</p>
                @if($Document->comment)
                <p class="lead"><strong>Comment :</strong></p>
                <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                    {{  $Document->comment }}
                </p>
                @endif
            </div>
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