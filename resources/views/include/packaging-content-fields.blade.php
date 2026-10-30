{{--
    Contenu d'un colis : quantité de chaque ligne du BL rangée dedans (liste de colisage).
    $Delivery   BL
    $Packaging  colis modifié, null à la création
    Enregistré par App\Services\Delivery\PackagingContent (refus si une ligne est colisée
    au-delà de sa quantité livrée).
--}}
@php
    $inThisPackage = $Packaging ? $Packaging->lines->pluck('qty', 'delivery_line_id') : collect();
    $packedByLine  = $Delivery->packaging
        ->reject(fn ($other) => $Packaging && $other->id === $Packaging->id)
        ->flatMap->lines
        ->groupBy('delivery_line_id')
        ->map->sum('qty');
@endphp
@if($Delivery->DeliveryLines->isNotEmpty())
<div class="mb-3">
    <label class="form-label">{{ __('packing_list.content') }}</label>
    <div class="form-text mb-1">{{ __('packing_list.content_help') }}</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <tbody>
            @foreach($Delivery->DeliveryLines as $line)
                @php $elsewhere = (float) ($packedByLine[$line->id] ?? 0); @endphp
                <tr>
                    <td>
                        <strong>{{ $line->OrderLine?->code }}</strong> {{ $line->OrderLine?->label }}
                        <div class="small text-body-secondary">
                            {{ __('general_content.delivered_qty_trans_key') }} : {{ format_qty($line->qty) }}
                            @if($elsewhere > 0) · {{ __('packing_list.already_packed', ['qty' => format_qty($elsewhere)]) }}@endif
                        </div>
                    </td>
                    <td style="width: 8rem;">
                        <input type="number" class="form-control form-control-sm @error('content.' . $line->id) is-invalid @enderror"
                               name="content[{{ $line->id }}]" step="any" min="0"
                               max="{{ max(0, (float) $line->qty - $elsewhere) }}"
                               value="{{ isset($inThisPackage[$line->id]) ? format_qty($inThisPackage[$line->id]) : '' }}"
                               aria-label="{{ __('packing_list.qty_in_package') }} {{ $line->OrderLine?->code }}">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
