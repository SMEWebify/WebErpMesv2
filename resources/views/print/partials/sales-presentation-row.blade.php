{{--
    Rangée de présentation d'un document commercial (section, sous-total, texte),
    produite par App\Services\Documents\SalesPrintLayout.

    $row          rangée typée
    $columns      nombre de colonnes du tableau des lignes
    $amountColumn position (1-based) de la colonne qui reçoit un montant
--}}
@php
    $currency = app('Factory')->curency ?? 'EUR';
    $money = fn ($value) => $normalizeCurrency(\Illuminate\Support\Number::currency((float) $value, $currency, config('app.locale')));
@endphp
@if($row['type'] === 'section')
    <tr class="line-section">
        <td colspan="{{ $columns }}" style="padding: 8px 6px 4px 6px; border-bottom: 1px solid #777; font-weight: bold; font-size: 1.05em; text-transform: uppercase;">
            {{ $row['label'] }}
        </td>
    </tr>
@elseif($row['type'] === 'subtotal')
    <tr class="line-subtotal">
        <td colspan="{{ $amountColumn - 1 }}" align="right" style="font-weight: bold; background-color: #f4f4f4;">{{ $row['label'] }}</td>
        <td align="right" style="font-weight: bold; background-color: #f4f4f4;">{{ $money($row['amount']) }}</td>
        @if($columns > $amountColumn)
        <td colspan="{{ $columns - $amountColumn }}" style="background-color: #f4f4f4;"></td>
        @endif
    </tr>
@elseif($row['type'] === 'text')
    <tr class="line-text">
        <td colspan="{{ $columns }}" style="font-style: italic; color: #333;">{!! nl2br(e($row['label'])) !!}</td>
    </tr>
@endif
