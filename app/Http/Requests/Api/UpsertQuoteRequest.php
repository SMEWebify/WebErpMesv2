<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpsertQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Quote header
            'code'                               => 'required|string|max:255',
            'label'                              => 'required|string|max:255',
            'companies_id'                       => 'required|exists:companies,id',
            'companies_contacts_id'              => 'required|exists:companies_contacts,id',
            'companies_addresses_id'             => 'required|exists:companies_addresses,id',
            'accounting_payment_conditions_id'   => 'required|exists:accounting_payment_conditions,id',
            'accounting_payment_methods_id'      => 'required|exists:accounting_payment_methods,id',
            'accounting_deliveries_id'           => 'required|exists:accounting_deliveries,id',
            'customer_reference'                 => 'nullable|string|max:255',
            'validity_date'                      => 'nullable|date',
            'statu'                              => 'nullable|integer',
            'comment'                            => 'nullable|string',
            'opportunities_id'                   => 'nullable|exists:opportunities,id',

            // Lines
            'lines'                              => 'nullable|array',
            'lines.*.id'                         => 'nullable|integer|exists:quote_lines,id',
            'lines.*.ordre'                      => 'required_with:lines|integer',
            'lines.*.code'                       => 'nullable|string|max:255',
            'lines.*.product_id'                 => 'nullable|exists:products,id',
            'lines.*.label'                      => 'required_with:lines|string|max:255',
            'lines.*.qty'                        => 'required_with:lines|numeric|min:0',
            'lines.*.methods_units_id'           => 'nullable|exists:methods_units,id',
            'lines.*.selling_price'              => 'nullable|numeric|min:0',
            'lines.*.discount'                   => 'nullable|numeric|min:0|max:100',
            'lines.*.accounting_vats_id'         => 'nullable|exists:accounting_vats,id',
            'lines.*.delivery_date'              => 'nullable|date',
            'lines.*.statu'                      => 'nullable|integer',
            'lines.*.use_calculated_price'       => 'nullable|boolean',

            // Line detail
            'lines.*.detail'                     => 'nullable|array',
            'lines.*.detail.x_size'              => 'nullable|numeric',
            'lines.*.detail.y_size'              => 'nullable|numeric',
            'lines.*.detail.z_size'              => 'nullable|numeric',
            'lines.*.detail.x_oversize'          => 'nullable|numeric',
            'lines.*.detail.y_oversize'          => 'nullable|numeric',
            'lines.*.detail.z_oversize'          => 'nullable|numeric',
            'lines.*.detail.diameter'            => 'nullable|numeric',
            'lines.*.detail.diameter_oversize'   => 'nullable|numeric',
            'lines.*.detail.material'            => 'nullable|string|max:255',
            'lines.*.detail.thickness'           => 'nullable|numeric',
            'lines.*.detail.finishing'           => 'nullable|string|max:255',
            'lines.*.detail.weight'              => 'nullable|numeric',
            'lines.*.detail.bend_count'          => 'nullable|integer',
            'lines.*.detail.material_loss_rate'  => 'nullable|numeric',
            'lines.*.detail.internal_comment'    => 'nullable|string',
            'lines.*.detail.external_comment'    => 'nullable|string',
            'lines.*.detail.custom_requirements' => 'nullable|array',

            // Tasks
            'lines.*.tasks'                      => 'nullable|array',
            'lines.*.tasks.*.id'                 => 'nullable|integer|exists:tasks,id',
            'lines.*.tasks.*.code'               => 'nullable|string|max:255',
            'lines.*.tasks.*.label'              => 'required_with:lines.*.tasks|string|max:255',
            'lines.*.tasks.*.ordre'              => 'nullable|integer',
            'lines.*.tasks.*.type'               => 'nullable|integer',
            'lines.*.tasks.*.qty'                => 'nullable|numeric|min:0',
            'lines.*.tasks.*.qty_init'           => 'nullable|numeric|min:0',
            'lines.*.tasks.*.qty_aviable'        => 'nullable|integer|min:0',
            'lines.*.tasks.*.methods_services_id'=> 'nullable|exists:methods_services,id',
            'lines.*.tasks.*.methods_units_id'   => 'nullable|exists:methods_units,id',
            'lines.*.tasks.*.methods_tools_id'   => 'nullable|exists:methods_tools,id',
            'lines.*.tasks.*.products_id'        => 'nullable|exists:products,id',
            'lines.*.tasks.*.seting_time'        => 'nullable|numeric|min:0',
            'lines.*.tasks.*.unit_time'          => 'nullable|numeric|min:0',
            'lines.*.tasks.*.remaining_time'     => 'nullable|numeric|min:0',
            'lines.*.tasks.*.unit_cost'          => 'nullable|numeric|min:0',
            'lines.*.tasks.*.unit_price'         => 'nullable|numeric|min:0',
            'lines.*.tasks.*.status_id'          => 'nullable|exists:statuses,id',
            'lines.*.tasks.*.priority'           => 'nullable|integer|in:1,2,3',
            'lines.*.tasks.*.delay'              => 'nullable|date',
            'lines.*.tasks.*.due_date'           => 'nullable|date',
            'lines.*.tasks.*.material'           => 'nullable|string|max:255',
            'lines.*.tasks.*.thickness'          => 'nullable|numeric',
            'lines.*.tasks.*.weight'             => 'nullable|numeric',
            'lines.*.tasks.*.x_size'             => 'nullable|numeric',
            'lines.*.tasks.*.y_size'             => 'nullable|numeric',
            'lines.*.tasks.*.z_size'             => 'nullable|numeric',
            'lines.*.tasks.*.x_oversize'         => 'nullable|numeric',
            'lines.*.tasks.*.y_oversize'         => 'nullable|numeric',
            'lines.*.tasks.*.z_oversize'         => 'nullable|numeric',
            'lines.*.tasks.*.diameter'           => 'nullable|numeric',
            'lines.*.tasks.*.diameter_oversize'  => 'nullable|numeric',
            'lines.*.tasks.*.to_schedule'        => 'nullable|boolean',
            'lines.*.tasks.*.not_recalculate'    => 'nullable|boolean',
        ];
    }
}
