
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
                @if($Factory->picture)
                <img src="data:image/png;base64,{{ $image }}" alt="Logo" width="200" class="logo"/>
                @endif
            </td>
            <td align="center" style="width: 40%;">
                <div align="center">
                    <h1>{{ $typeDocumentName}}</h1> <br/>
                    <h2>{{ $Document->code }}</h2>
                </div>
            </td>
        </tr>
    </table>
</div>
<hr style="color: #6c757d">
<hr style="color: #6c757d">
<div class="invoice">
    <table width="90%" style="border-collapse: collapse; border-radius: 5px">
        <tr>
            <td align="center" style="width: 30%; border-block-color: rgb(52, 71, 124); border: inset; background-color:MediumSeaGreen; ">
                <h1>{{__('general_content.order_trans_key') }}</h1>
            </td>
            <td align="center" style="width: 30%; border-block-color: rgb(52, 71, 124); border: inset; background-color:MediumSeaGreen; ">
                <h2>{{__('general_content.task_trans_key') }}</h2>
            </td>
            <td align="center" style="width: 30%; border-block-color: rgb(52, 71, 124); border: inset; background-color:MediumSeaGreen; ">
                <h3>{{__('general_content.qty_trans_key') }}</h3>
            </td>
        </tr>
        <tr>
            <td align="center" style="width: 30%; border-block-color: rgb(52, 71, 124); border: inset; border-radius: 5px">
                @if($Document->order_lines_id)
                    <h2>{{ $Document->orderLine->order['code'] }}</h2>
                @else
                    <h2>N/A</h2>
                @endif
            </td>
            <td align="center" style="width: 30%; border-block-color: rgb(52, 71, 124); border: inset; border-radius: 5px">
                @if($Document->task_id)
                    <h2>#{{ $Document->task_id }}</h2>
                @else
                    <h2>N/A</h2>
                @endif
            </td>
            <td align="center" style="width: 30%; border-block-color: rgb(52, 71, 124); border: inset; border-radius: 5px">
                <h3>x{{ $Document->qty }}</h3>
            </td>
        </tr>
    </table>
    <hr style="color: #6c757d">
    <table width="45%" style="border-block-color: rgb(139, 131, 131); border: dashed; background-color:LightGray; border-radius: 5px">
        <tr>
            <td>{{ __('general_content.user_trans_key') }}</td>
            <td>{{ $Document->UserManagement['name'] }}</td>
        </tr>
        <tr>
            <td>{{ __('general_content.type_trans_key') }}</td>
            <td>
                @if($Document->type  == 1) {{ __('general_content.internal_trans_key') }}@endif
                @if($Document->type  == 2) {{ __('general_content.external_trans_key') }}@endif
            </td>
        </tr>
        <tr>
            <td>{{ __('general_content.customer_trans_key') }}</td>
            <td>
                @if($Document->companie_id)
                    {{ $Document->companie->label }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td>{{__('general_content.service_trans_key') }}</td>
            <td>
                @if($Document->service_id)
                    {{ $Document->service->label }}
                @else
                    N/A
                @endif
            </td>
        </tr>
    </table>
    <hr style="color: #6c757d">
    <table width="90%" style="border-block-color: rgb(139, 131, 131); border: dashed; border-radius: 5px">
        <tr>
            <td>{{__('general_content.failure_trans_key') }}  :</td>
            <td>
                @if($Document->failure_id)
                    {{ $Document->Failure->label }}
                @else
                    N/A
                @endif
            </td>
            <td>{{__('general_content.failure_comment_trans_key') }}  :</td>
            <td>
                {{ $Document->failure_comment }}
            </td>
        </tr>
        <tr>
            <td>{{__('general_content.cause_trans_key') }}  :</td>
            <td>
                @if($Document->causes_id)
                    {{ $Document->Cause->label }}
                @else
                    N/A
                @endif
            </td>
            <td>{{__('general_content.cause_comment_trans_key') }}  :</td>
            <td>
                {{ $Document->causes_comment }}
            </td>
        </tr>
        <tr>
            <td>{{__('general_content.correction_trans_key') }}  :</td>
            <td>
                @if($Document->correction_id)
                    {{ $Document->Correction->label }}
                @else
                    N/A
                @endif
            </td>
            <td>{{__('general_content.correction_comment_trans_key') }}  :</td>
            <td>
                {{ $Document->correction_comment }}
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