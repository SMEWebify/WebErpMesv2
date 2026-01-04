<?php

namespace App\Services;

use App\Models\Admin\CustomField;

class CustomFieldService
{
    /**
     * Get custom fields with their values for a specific entity type and ID.
     *
     * @param string $relatedType
     * @param int $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomFieldsWithValues(string $relatedType, int $entityId)
    {
        return CustomField::where('custom_fields.related_type', '=', $relatedType)
            ->leftJoin('custom_field_values as cfv', function($join) use ($entityId, $relatedType) {
                $join->on('custom_fields.id', '=', 'cfv.custom_field_id')
                        ->where('cfv.entity_type', '=', $relatedType)
                        ->where('cfv.entity_id', '=', $entityId);
            })
            ->select('custom_fields.*', 'cfv.value as field_value')
            ->orderBy('custom_fields.category')
            ->orderBy('custom_fields.name')
            ->get();
    }

    /**
     * Get product custom fields with values scoped to a quote line.
     *
     * This returns all custom fields defined for products, including any value
     * previously saved for the provided quote line (entity_type = quote_line)
     * and the product's own value as a fallback.
     */
    public function getProductCustomFieldsForQuoteLine(?int $productId, int $quoteLineId)
    {
        if (!$productId) {
            return collect();
        }

        return CustomField::where('custom_fields.related_type', '=', 'product')
            ->leftJoin('custom_field_values as line_values', function ($join) use ($quoteLineId) {
                $join->on('custom_fields.id', '=', 'line_values.custom_field_id')
                    ->where('line_values.entity_type', '=', 'quote_line')
                    ->where('line_values.entity_id', '=', $quoteLineId);
            })
            ->leftJoin('custom_field_values as product_values', function ($join) use ($productId) {
                $join->on('custom_fields.id', '=', 'product_values.custom_field_id')
                    ->where('product_values.entity_type', '=', 'product')
                    ->where('product_values.entity_id', '=', $productId);
            })
            ->select(
                'custom_fields.*',
                'line_values.value as line_value',
                'product_values.value as product_value'
            )
            ->orderBy('custom_fields.category')
            ->orderBy('custom_fields.name')
            ->get();
    }
}
