<?php

namespace App\Services\Inspection;

use App\Models\Admin\Factory;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionProject;
use App\Models\Planning\Task;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Rapport premier article d'un dossier de contrôle, en trois formulaires sur
 * le modèle AS9102 :
 * 1. identification de la pièce (article, indice, plan, commande, signatures) ;
 * 2. matière, achats et sous-traitance, certificats de la GED ;
 * 3. résultat de chaque cote bullée.
 *
 * Tout est déduit de l'ERP ; seuls les champs sans source (n° de série, code
 * fournisseur, modifications…) se saisissent, dans `report_fields`.
 * Le format est neutre par défaut ; `config('inspection.report_format')` = as9102
 * reprend les intitulés et numéros de cases de la norme.
 */
class FaiReport
{
    /** Champs du formulaire 1 saisis à la main (le reste vient de l'ERP). */
    public const EDITABLE = [
        'serial_number', 'drawing_number', 'drawing_revision', 'additional_changes',
        'process_reference', 'supplier_code', 'po_number', 'detail_assembly', 'partial_reason',
    ];

    public function __construct(
        private readonly TaskInspection $inspection,
        private readonly FileStorageService $files,
    ) {
    }

    public function build(InspectionProject $dossier): array
    {
        $dossier->loadMissing(['Plan.product', 'Plan.file', 'Plan.points', 'Company', 'Order', 'OrderLine', 'VerifiedBy', 'ApprovedBy', 'ReportFile']);
        $plan = $dossier->Plan;
        $product = $plan?->product;
        $fields = $dossier->report_fields ?? [];
        $latest = $this->inspection->latestMeasures($dossier);

        $characteristics = $this->characteristics($dossier, $latest);
        $hasNc = collect($characteristics)->contains(fn ($c) => $c['result'] === 'nok');
        $drawingName = $plan?->file?->original_file_name ? pathinfo($plan->file->original_file_name, PATHINFO_FILENAME) : null;

        return [
            'format' => config('inspection.report_format', 'neutral'),
            'form1'  => [
                'part_number'        => $product?->code,
                'part_name'          => $product?->label,
                'serial_number'      => $fields['serial_number'] ?? null,
                'fair_identifier'    => $dossier->code,
                'part_revision'      => $dossier->revision,
                'drawing_number'     => $fields['drawing_number'] ?? $drawingName,
                'drawing_revision'   => $fields['drawing_revision'] ?? $dossier->revision,
                'additional_changes' => $fields['additional_changes'] ?? null,
                'process_reference'  => $fields['process_reference'] ?? $dossier->Order?->code,
                'organization'       => Factory::query()->value('name'),
                'supplier_code'      => $fields['supplier_code'] ?? null,
                'po_number'          => $fields['po_number'] ?? $dossier->Order?->customer_reference,
                'customer'           => $dossier->Company?->label,
                'detail_assembly'    => $fields['detail_assembly'] ?? 'detail',
                'fai_type'           => $dossier->fai_type ?? 'full',
                'partial_reason'     => $fields['partial_reason'] ?? null,
                'is_fai'             => $dossier->is_fai,
                'has_nc'             => $hasNc,
                'verified_by'        => $dossier->VerifiedBy?->name,
                'verified_at'        => $dossier->verified_at?->toIso8601String(),
                'approved_by'        => $dossier->ApprovedBy?->name,
                'approved_at'        => $dossier->approved_at?->toIso8601String(),
                'customer_approval'  => $dossier->customer_approval,
                'customer_approved_at' => $dossier->customer_approved_at?->toDateString(),
                'comment'            => $dossier->report_comment,
                'quantity'           => (int) $dossier->quantity_planned,
            ],
            'form2'           => $this->materials($dossier),
            'form3'           => $characteristics,
            'closed'          => $dossier->closed_at !== null,
            'report_file_url' => $dossier->ReportFile?->view_url,
        ];
    }

    /**
     * Génère le PDF, le range dans la GED de la commande (sinon de l'article) et
     * le relie au dossier. Un nouveau rapport remplace le précédent sur le dossier ;
     * l'ancien PDF reste dans la GED.
     */
    public function generate(InspectionProject $dossier): \App\Models\File
    {
        $data = $this->build($dossier);
        $pdf = Pdf::loadView('print/inspection-fai', [
            'report'  => $data,
            'dossier' => $dossier,
            'factory' => Factory::first(),
            'draft'   => $dossier->approved_at === null,
        ])->setPaper('a4', 'landscape');

        $file = $this->files->storeContents(
            (string) $pdf->output(),
            'FAI-' . $dossier->code . '.pdf',
            'application/pdf',
            ['comment' => __('inspection.report_title'), 'hashtags' => ['controle-' . $dossier->id]],
        );

        $owner = $dossier->Order ?? $dossier->Plan?->product;
        if ($owner) {
            $this->files->attach($file, $owner, FileRole::CONTROL);
        }

        $dossier->forceFill(['report_file_id' => $file->id])->save();

        return $file;
    }

    /**
     * Formulaire 3 : une ligne par cote, résultat de chaque pièce mesurée.
     */
    private function characteristics(InspectionProject $dossier, Collection $latest): array
    {
        return $dossier->Plan->points->map(function (InspectionControlPoint $point) use ($latest) {
            $measures = $latest->get($point->id, collect())->sortKeys();
            $nok = $measures->contains(fn ($m) => $m->result === 'nok');

            return [
                'number'      => $point->number,
                'location'    => trim(($point->grid_zone ?? '') . (($point->plan_page ?? 1) > 1 ? ' p.' . $point->plan_page : '')),
                'designator'  => collect([
                    $point->is_critical ? __('inspection.critical_short') : null,
                    $point->count > 1 ? $point->count . 'X' : null,
                ])->filter()->implode(' · '),
                'requirement' => $point->requirementText(),
                'requirement_print' => $point->requirementText(printable: true),
                'results'     => $measures->map(fn ($m) => [
                    'part'   => $m->part_index,
                    'value'  => $m->measured_value,
                    'result' => $m->result,
                ])->values()->all(),
                'result'      => $measures->isEmpty() ? null : ($nok ? 'nok' : 'ok'),
                'tools'       => $measures->pluck('device_code')->filter()->unique()->values()->implode(', ')
                    ?: ($point->instrument_type ?? ''),
                'nc'          => $measures->pluck('nc_code')->filter()->unique()->values()->implode(', '),
                'comments'    => $measures->pluck('comment')->filter()->unique()->values()->implode(' / '),
            ];
        })->values()->all();
    }

    /**
     * Formulaire 2 : matière de l'article, achats et sous-traitance des tâches de
     * la ligne, certificats rangés dans la GED (commande et article).
     */
    private function materials(InspectionProject $dossier): array
    {
        $product = $dossier->Plan?->product;
        $rows = [];

        if ($product && filled($product->material)) {
            $rows[] = [
                'kind'      => 'material',
                'label'     => trim($product->material . ($product->thickness ? ' — ' . rtrim(rtrim((string) $product->thickness, '0'), '.') . ' mm' : '')),
                'supplier'  => null,
                'reference' => null,
            ];
        }

        if ($dossier->order_lines_id) {
            $tasks = Task::with(['purchaseLines.purchase.companie', 'service'])
                ->where('order_lines_id', $dossier->order_lines_id)
                ->get();

            foreach ($tasks as $task) {
                foreach ($task->purchaseLines as $line) {
                    $rows[] = [
                        'kind'      => $task->service?->type === 1 ? 'purchase' : 'process',
                        'label'     => $line->label ?: $task->label,
                        'supplier'  => $line->purchase?->companie?->label,
                        'reference' => $line->purchase?->code,
                    ];
                }
            }
        }

        $owners = array_filter([$dossier->Order, $product]);
        foreach ($owners as $owner) {
            foreach ($owner->files()->wherePivot('role', FileRole::CERTIFICATE)->get() as $file) {
                $rows[] = [
                    'kind'      => 'certificate',
                    'label'     => $file->original_file_name ?: $file->name,
                    'supplier'  => null,
                    'reference' => $file->comment,
                ];
            }
        }

        return $rows;
    }
}
