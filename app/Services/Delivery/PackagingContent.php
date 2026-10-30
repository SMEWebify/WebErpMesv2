<?php

namespace App\Services\Delivery;

use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Packaging;
use App\Models\Workflow\PackagingLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Contenu d'un colis : quantité de chaque ligne du BL rangée dedans.
 *
 * Règle : une ligne ne peut pas être colisée au-delà de sa quantité livrée,
 * tous colis du BL confondus. Une quantité vide ou nulle retire la ligne du colis.
 */
class PackagingContent
{
    private const EPSILON = 0.0005;

    /**
     * @param  array<int|string, mixed>  $quantities  id de ligne du BL => quantité
     *
     * @throws ValidationException
     */
    public function sync(Packaging $package, array $quantities): void
    {
        $quantities = collect($quantities)
            ->mapWithKeys(fn ($qty, $lineId) => [(int) $lineId => $qty === null || $qty === '' ? 0.0 : (float) $qty]);

        if ($quantities->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($package, $quantities) {
            $lines = DeliveryLines::where('deliverys_id', $package->deliverys_id)
                ->whereIn('id', $quantities->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $packedElsewhere = PackagingLine::whereIn('delivery_line_id', $lines->keys())
                ->where('packaging_id', '!=', $package->id)
                ->selectRaw('delivery_line_id, SUM(qty) as qty')
                ->groupBy('delivery_line_id')
                ->pluck('qty', 'delivery_line_id');

            $errors = [];
            foreach ($quantities as $lineId => $qty) {
                $line = $lines->get($lineId);
                if ($line === null) {
                    $errors["content.{$lineId}"] = __('packing_list.errors.foreign_line');
                    continue;
                }
                if ($qty < 0) {
                    $errors["content.{$lineId}"] = __('packing_list.errors.negative');
                    continue;
                }

                $available = (float) $line->qty - (float) ($packedElsewhere[$lineId] ?? 0);
                if ($qty - $available > self::EPSILON) {
                    $errors["content.{$lineId}"] = __('packing_list.errors.over_packed', [
                        'code'      => $line->OrderLine?->code ?? $lineId,
                        'qty'       => format_qty($qty),
                        'available' => format_qty(max(0, $available)),
                    ]);
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            foreach ($quantities as $lineId => $qty) {
                if ($qty <= self::EPSILON) {
                    PackagingLine::where('packaging_id', $package->id)->where('delivery_line_id', $lineId)->delete();
                    continue;
                }

                PackagingLine::updateOrCreate(
                    ['packaging_id' => $package->id, 'delivery_line_id' => $lineId],
                    ['qty' => $qty]
                );
            }
        });
    }
}
