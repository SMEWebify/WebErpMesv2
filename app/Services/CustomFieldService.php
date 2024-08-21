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
            ->get();
    }
}
