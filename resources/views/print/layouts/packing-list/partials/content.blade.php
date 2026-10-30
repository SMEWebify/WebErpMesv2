{{-- Lignes du BL d'un colis ou du bloc « hors colis » (liste de colisage). --}}
<table class="content" style="width: 100%;">
    <thead>
        <tr>
            <th align="left">{{ __('general_content.order_trans_key') }}</th>
            <th align="left">{{ __('general_content.reference_trans_key') }}</th>
            <th align="left">{{ __('general_content.description_trans_key') }}</th>
            <th align="center">{{ __('packing_list.qty_in_package') }}</th>
            <th align="center">{{ __('general_content.unit_trans_key') }}</th>
        </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row['order_code'] }}</td>
            <td>{{ $row['code'] }}@if(!empty($row['ind']))<br><span class="muted">{{ __('general_content.plan_index_short_trans_key') }} {{ $row['ind'] }}</span>@endif</td>
            <td>{{ $row['label'] }}@if(!empty($row['equipment_tags']))<br><span class="muted">{{ __('general_content.equipment_tag_trans_key') }} : {{ $row['equipment_tags'] }}</span>@endif</td>
            <td align="center">{{ format_qty($row['qty']) }}</td>
            <td align="center">{{ $row['unit'] }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">{{ $empty }}</td></tr>
    @endforelse
    </tbody>
</table>
