{{--
    En-tête et pied communs aux modèles d'impression des documents commerciaux
    (print/layouts/*/pdf-sales). Repris de print/pdf-sales, qui reste inchangé :
    son rendu est figé par SalesPdfLegacyRenderingTest.

    $subtitle  texte facultatif sous le numéro (ex. « Document technique »)
--}}
<header>
    <table width="100%; border: none; ">
        <tr>
            <td align="left" style="width: 50%; background-color: {{ $Factory->pdf_header_font_color }}">
                @if($image)
                    <img src="data:image/png;base64,{{ $image }}" alt="Logo" style="{{ \App\Support\PdfLogo::style($image, 300, 95) }}"/>
                @endif
            </td>
            <td colspan="2" align="center" style="width: 400%; background-color: {{ $Factory->pdf_header_font_color }}">
                <h2>{{ $typeDocumentName }}</h2>
                <h3>{{ $Document->code }}</h3>
                @if(!empty($subtitle))<div>{{ $subtitle }}</div>@endif
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
                    <h3>{{ optional($Document->companie)['label'] }} </h3>
                    <pre>
{{ optional($Document->contact)['civility'] }} {{ optional($Document->contact)['first_name'] }} {{ optional($Document->contact)['name'] }}
{{ optional($Document->adresse)['adress'] }}
{{ optional($Document->adresse)['zipcode'] }} {{ optional($Document->adresse)['city'] }} {{ optional($Document->adresse)['province'] ?? '' }}
{{ optional($Document->adresse)['country'] }}
{{ __('general_content.phone_trans_key') }} : {{ optional($Document->contact)['number'] }}
{{ __('general_content.email_trans_key') }} : {{ optional($Document->contact)['mail'] }}
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
