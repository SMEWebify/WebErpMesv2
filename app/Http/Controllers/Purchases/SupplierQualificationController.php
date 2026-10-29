<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Services\Purchases\SupplierQualification;
use App\Services\SelectDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Liste des fournisseurs qualifiés (CSV), d'après la même règle que le contrôle des
 * commandes d'achat (SupplierQualification). ?scope=all : tous les fournisseurs, avec
 * leur état. Disponible que l'option de blocage soit active ou non.
 */
class SupplierQualificationController extends Controller
{
    public function export(Request $request, SelectDataService $selectData, SupplierQualification $qualification)
    {
        $all = $request->query('scope') === 'all';
        $suppliers = $selectData->getSupplier();
        $states = $qualification->states($suppliers->pluck('id'));

        $rows = $suppliers
            ->map(fn ($c) => ['company' => $c, 'state' => $states->get($c->id)])
            ->filter(fn ($row) => $all || $row['state']['status'] === SupplierQualification::QUALIFIED)
            ->values();

        $date = fn ($value, string $format) => $value ? Carbon::parse($value)->format($format) : '';
        $filename = ($all ? 'suppliers-qualification-' : 'qualified-suppliers-') . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows, $qualification, $date) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // Excel : accents lus en UTF-8

            $columns = __('supplier_qualification.export_columns');
            fputcsv($handle, array_values($columns), ';', escape: '\\');

            foreach ($rows as $row) {
                $state = $row['state'];
                fputcsv($handle, [
                    $row['company']->code,
                    $row['company']->label ?? $row['company']->last_name,
                    __('supplier_qualification.status.' . $state['status']),
                    $qualification->evaluationStatusLabel($state['evaluation_status']),
                    $date($state['evaluated_at'], 'd/m/Y'),
                    $date($state['approved_at'], 'd/m/Y'),
                    $date($state['next_review_at'], 'd/m/Y'),
                ], ';', escape: '\\');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
