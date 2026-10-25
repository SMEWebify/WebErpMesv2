<?php

namespace App\Services\Inspection;

use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionPlan;

/**
 * Forme JSON des plans de contrôle et de leurs cotes, partagée par la liste,
 * le bulleur et l'onglet de la fiche article.
 */
class InspectionPlanPresenter
{
    /**
     * @param  object  $file  ligne de `files` (modèle ou lecture brute)
     */
    public static function fileSummary(object $file): array
    {
        $thumbnail = $file->thumbnail_path ?? null;

        return [
            'id'                  => (int) $file->id,
            'kind'                => $file->kind,
            'name'                => $file->original_file_name ?? null,
            'view_url'            => route('files.raw', ['file' => $file->id]),
            'thumbnail_url'       => filled($thumbnail)
                ? route('files.thumbnail', ['file' => $file->id, 'v' => substr(md5($thumbnail), 0, 8)])
                : null,
            'thumbnail_store_url' => route('files.json.thumbnail', ['file' => $file->id]),
        ];
    }

    public static function plan(InspectionPlan $plan, bool $withPoints = false): array
    {
        $product = $plan->product;

        $data = [
            'id'             => $plan->id,
            'status'         => $plan->status,
            'revision'       => $plan->revision,
            'page_count'     => $plan->page_count,
            'rotation'       => (int) $plan->rotation,
            'product_id'     => $plan->products_id,
            'product_code'   => $product?->code,
            'product_label'  => $product?->label,
            'product_revision' => $product?->ind,
            'file'           => $plan->file ? self::fileSummary($plan->file) : null,
            'thumbnail_url'  => filled($plan->thumbnail_path)
                ? route('inspection.plans.thumbnail', ['plan' => $plan->id, 'v' => substr(md5($plan->thumbnail_path), 0, 8)])
                : null,
            'points_count'   => $plan->points_count ?? ($plan->relationLoaded('points') ? $plan->points->count() : null),
            'critical_count' => $plan->critical_count ?? null,
            'created_by'     => $plan->creator?->name,
            'created_at'     => $plan->created_at?->toIso8601String(),
            'approved_by'    => $plan->approver?->name,
            'approved_at'    => $plan->approved_at?->toIso8601String(),
            'bubbler_url'    => route('inspection.plans.bubbler', ['plan' => $plan->id]),
        ];

        if ($withPoints) {
            $data['points'] = $plan->points->map(fn ($p) => self::point($p))->values();
        }

        return $data;
    }

    public static function point(InspectionControlPoint $point): array
    {
        return $point->only([
            'id', 'number', 'label', 'characteristic_type', 'gdt_symbol', 'diameter_zone',
            'material_modifier', 'datums', 'count', 'nominal_value', 'tol_min', 'tol_max', 'unit',
            'frequency_type', 'frequency_value', 'plan_page', 'grid_zone', 'methods_services_id',
            'instrument_type', 'is_critical', 'is_fai',
            'box_x', 'box_y', 'box_w', 'box_h', 'bubble_x', 'bubble_y',
        ]);
    }
}
