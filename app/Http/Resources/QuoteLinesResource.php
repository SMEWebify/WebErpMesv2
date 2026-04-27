<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteLinesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'quotes_id'             => $this->quotes_id,
            'ordre'                 => $this->ordre,
            'code'                  => $this->code,
            'product_id'            => $this->product_id,
            'label'                 => $this->label,
            'qty'                   => $this->qty,
            'methods_units_id'      => $this->methods_units_id,
            'selling_price'         => $this->selling_price,
            'discount'              => $this->discount,
            'accounting_vats_id'    => $this->accounting_vats_id,
            'delivery_date'         => $this->delivery_date,
            'statu'                 => $this->statu,
            'use_calculated_price'  => $this->use_calculated_price,
            'detail'                => $this->whenLoaded('QuoteLineDetails'),
            'tasks'                 => TaskResource::collection($this->whenLoaded('Task')),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
