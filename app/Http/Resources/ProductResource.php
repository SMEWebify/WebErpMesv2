<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'code'                => $this->code,
            'label'               => $this->label,
            'ind'                 => $this->ind,
            'methods_services_id' => $this->methods_services_id,
            'methods_families_id' => $this->methods_families_id,
            'methods_units_id'    => $this->methods_units_id,
            'purchased'           => $this->purchased,
            'purchased_price'     => $this->purchased_price,
            'sold'                => $this->sold,
            'selling_price'       => $this->selling_price,
            'material'            => $this->material,
            'finishing'           => $this->finishing,
            'thickness'           => $this->thickness,
            'weight'              => $this->weight,
            'bend_count'          => $this->bend_count,
            'x_size'              => $this->x_size,
            'y_size'              => $this->y_size,
            'z_size'              => $this->z_size,
            'x_oversize'          => $this->x_oversize,
            'y_oversize'          => $this->y_oversize,
            'z_oversize'          => $this->z_oversize,
            'diameter'            => $this->diameter,
            'diameter_oversize'   => $this->diameter_oversize,
            'section_size'        => $this->section_size,
            'qty_eco_min'         => $this->qty_eco_min,
            'qty_eco_max'         => $this->qty_eco_max,
            'tracability_type'    => $this->tracability_type,
            'comment'             => $this->comment,
            'picture'             => $this->picture,
            'drawing_file'        => $this->drawing_file,
            'stl_file'            => $this->stl_file,
            'svg_file'            => $this->svg_file,
            'cad_file_path'       => $this->cad_file_path,
            'cam_file_path'       => $this->cam_file_path,
            'csv_file_name'       => $this->csv_file_name,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
