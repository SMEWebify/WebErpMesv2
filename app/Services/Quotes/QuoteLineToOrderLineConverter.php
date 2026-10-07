<?php

namespace App\Services\Quotes;

use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Services\Planning\BillOfMaterialsCopier;
use Carbon\Carbon;

/**
 * Transforme une ligne de devis en ligne de commande avec tout son contenu
 * technique : détail, gamme et nomenclature complètes, fichiers GED.
 *
 * Partagé par la conversion directe du devis et par la proforma, pour que
 * la commande produite soit la même quel que soit le chemin.
 */
class QuoteLineToOrderLineConverter
{
    public function __construct(private readonly BillOfMaterialsCopier $bom) {}

    /**
     * @param  int  $defaultDeliveryDays  délai retenu pour le délai interne quand la ligne n'a pas de date de livraison
     */
    public function convert(QuoteLines $quoteLine, int $orderId, int $defaultDeliveryDays): OrderLines
    {
        $factory = app('Factory');

        $deliveryDate  = $quoteLine->delivery_date ?? now()->addDays($defaultDeliveryDays)->format('Y-m-d');
        $internalDelay = Carbon::parse($deliveryDate)
            ->subDays((int) ($factory->add_delivery_delay_order ?? 0))
            ->format('Y-m-d');

        $orderLine = OrderLines::create([
            'orders_id'               => $orderId,
            'quote_lines_id'          => $quoteLine->id,
            'ordre'                   => $quoteLine->ordre,
            'code'                    => $quoteLine->code,
            'product_id'              => $quoteLine->product_id,
            'label'                   => $quoteLine->label,
            'qty'                     => $quoteLine->qty,
            // Explicites : le passage en decimal a retiré le default(0) de ces colonnes.
            'delivered_qty'           => 0,
            'delivered_remaining_qty' => $quoteLine->qty,
            'invoiced_qty'            => 0,
            'invoiced_remaining_qty'  => $quoteLine->qty,
            'methods_units_id'        => $quoteLine->methods_units_id,
            'selling_price'           => $quoteLine->selling_price,
            'discount'                => $quoteLine->discount,
            'accounting_vats_id'      => $quoteLine->accounting_vats_id,
            'internal_delay'          => $internalDelay,
            'delivery_date'           => $quoteLine->delivery_date,
        ]);

        $detail = $quoteLine->QuoteLineDetails;
        if ($detail) {
            OrderLineDetails::create([
                'order_lines_id'      => $orderLine->id,
                'x_size'              => $detail->x_size,
                'y_size'              => $detail->y_size,
                'z_size'              => $detail->z_size,
                'x_oversize'          => $detail->x_oversize,
                'y_oversize'          => $detail->y_oversize,
                'z_oversize'          => $detail->z_oversize,
                'diameter'            => $detail->diameter,
                'diameter_oversize'   => $detail->diameter_oversize,
                'material'            => $detail->material,
                'thickness'           => $detail->thickness,
                'finishing'           => $detail->finishing,
                'weight'              => $detail->weight,
                'bend_count'          => $detail->bend_count,
                'material_loss_rate'  => $detail->material_loss_rate,
                'cad_file'            => $detail->cad_file,
                'cam_file'            => $detail->cam_file,
                'cad_file_path'       => $detail->cad_file_path,
                'cam_file_path'       => $detail->cam_file_path,
                'picture'             => $detail->picture,
                'internal_comment'    => $detail->internal_comment,
                'external_comment'    => $detail->external_comment,
                'custom_requirements' => $detail->custom_requirements,
            ]);
        }

        $this->bom->copy('quote_lines_id', $quoteLine->id, 'order_lines_id', $orderLine->id, '6');

        if ($quoteLine->Task->isNotEmpty()) {
            $orderLine->tasks_status = 2;
            $orderLine->save();
        }

        $pivots = $quoteLine->files->mapWithKeys(fn ($file) => [
            $file->id => [
                'role'       => $file->pivot->role,
                'is_primary' => (bool) $file->pivot->is_primary,
            ],
        ])->all();

        if (!empty($pivots)) {
            $orderLine->files()->attach($pivots);
        }

        return $orderLine;
    }
}
