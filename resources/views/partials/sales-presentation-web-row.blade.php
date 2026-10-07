{{--
    Rangée de présentation sur une page web montrée au client (lien public,
    portail), produite par App\Services\Documents\SalesPrintLayout::webRows().
    Description en 1re colonne, quantité en 2e, montant en 3e.

    $row       rangée typée (section, subtotal, text, package)
    $columns   nombre de colonnes du tableau
    $currency  devise affichée
    $thousands séparateur de milliers, celui des lignes de la page
--}}
@php($thousands = $thousands ?? ' ')
@if($row['type'] === 'section')
    <tr class="table-secondary">
        <td colspan="{{ $columns }}" class="fw-bold font-weight-bold text-uppercase">{{ $row['label'] }}</td>
    </tr>
@elseif($row['type'] === 'subtotal')
    <tr>
        <td colspan="2" class="text-end text-right fw-bold font-weight-bold">{{ $row['label'] }}</td>
        <td class="text-end text-right fw-bold font-weight-bold">{{ number_format((float) $row['amount'], 2, '.', $thousands) }} {{ $currency }}</td>
        <td colspan="{{ $columns - 3 }}"></td>
    </tr>
@elseif($row['type'] === 'text')
    <tr>
        <td colspan="{{ $columns }}" class="fst-italic font-italic">{!! nl2br(e($row['label'])) !!}</td>
    </tr>
@elseif($row['type'] === 'package')
    <tr>
        <td><div class="fw-medium font-weight-bold">{{ $row['label'] }}</div></td>
        <td>{{ $row['show_qty'] ? '1 ' . ($row['line']->Unit['label'] ?? '') : '' }}</td>
        <td class="text-end text-right">{{ number_format((float) $row['amount'], 2, '.', $thousands) }} {{ $currency }}</td>
        <td colspan="{{ $columns - 3 }}"></td>
    </tr>
@endif
