<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'ordre' => $this->ordre,
            'quote_lines_id' => $this->quote_lines_id,
            'order_lines_id' => $this->order_lines_id,
            'products_id' => $this->products_id,
            'sub_assembly_id' => $this->sub_assembly_id,
            'methods_services_id' => $this->methods_services_id,
            'setting_time' => $this->seting_time,
            'unit_time'=> $this->unit_time, 
            'remaining_time'=> $this->remaining_time, 
            'status_id'=> $this->status_id, 
            'type'=> $this->type,
            'delay'=> $this->delay,
            'qty'=> $this->qty,
            'qty_init'=> $this->qty_init,
            'qty_aviable'=> $this->qty_aviable,
            'unit_cost'=> $this->unit_cost,
            'unit_price'=> $this->unit_price,
            'methods_units_id'=> $this->methods_units_id,
            'x_size'=> $this->x_size, 
            'y_size'=> $this->y_size, 
            'z_size'=> $this->z_size, 
            'x_oversize'=> $this->x_oversize,
            'y_oversize'=> $this->y_oversize,
            'z_oversize'=> $this->z_oversize,
            'diameter'=> $this->diameter,
            'diameter_oversize'=> $this->diameter_oversize,
            'to_schedule'=> $this->to_schedule,
            'end_date'=> $this->end_date,
            'not_recalculate'=> $this->not_recalculate,
            'material'=> $this->material, 
            'thickness'=> $this->thickness, 
            'weight' => $this->weight, 
        ];
    }
}
