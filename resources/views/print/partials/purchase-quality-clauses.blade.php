{{-- Clauses qualité d'une commande d'achat, ajoutées en fin de document par
     App\Services\Purchases\PurchaseQualityClauses::appendToHtml() : n'existe dans le
     HTML que si la commande porte au moins une clause. --}}
<div style="margin-top: 20px; padding: 0 10px;">
    <h3 style="margin: 10px 0 6px 0;">{{ __('purchase_quality.pdf_title') }}</h3>
    @if($lines->isNotEmpty())
    <table style="width: 100%; margin-bottom: 10px;">
        <thead>
            <tr>
                <th align="left" style="padding: 4px;">{{ __('purchase_quality.pdf_line') }}</th>
                <th align="left" style="padding: 4px;">{{ __('purchase_quality.certificate_type') }}</th>
                <th align="left" style="padding: 4px;">{{ __('purchase_quality.construction_code') }}</th>
                <th align="left" style="padding: 4px;">{{ __('purchase_quality.nuclear_level') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            <tr>
                <td style="padding: 4px;">{{ $line->ordre }}. {{ $line->code }} {{ $line->label }}</td>
                <td style="padding: 4px;">{{ $line->certificate_type ?? '-' }}</td>
                <td style="padding: 4px;">{{ $line->construction_code ?? '-' }}</td>
                <td style="padding: 4px;">{{ $line->nuclear_level ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @foreach($clauses as $clause)
    <div style="page-break-inside: avoid; margin-bottom: 8px; font-size: smaller;">
        <strong>{{ $clause->code }}</strong><br />
        {!! nl2br(e($clause->text())) !!}
    </div>
    @endforeach
</div>
