<?php

namespace App\Http\Controllers\HumanResources;

use App\Exports\PayrollVariablesExport;
use App\Http\Controllers\Controller;
use App\Services\HumanResources\PayrollExportService;
use App\Services\SelectDataService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Monthly payroll variable elements: preview on screen, then download.
 */
class PayrollExportController extends Controller
{
    public function __construct(
        private readonly PayrollExportService $payroll,
        private readonly SelectDataService $selectData,
    ) {
    }

    public function index(Request $request)
    {
        $month = $request->input('month');
        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;

        [$periodStart, $periodEnd] = $this->payroll->periodFor($month);

        return view('admin/human-resources-payroll-export', [
            'Rows' => $this->payroll->rows($month, $userId),
            'Warnings' => $this->payroll->warnings($month, $userId),
            'PeriodStart' => $periodStart,
            'PeriodEnd' => $periodEnd,
            'userSelect' => $this->selectData->getUsers(),
            'filters' => [
                'month' => $periodStart->format('Y-m'),
                'user_id' => $userId,
            ],
        ]);
    }

    public function export(Request $request, string $ext)
    {
        abort_unless(in_array($ext, ['csv', 'xlsx'], true), Response::HTTP_NOT_FOUND);

        $month = $request->input('month');
        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;

        return Excel::download(
            new PayrollVariablesExport($this->payroll->rows($month, $userId)),
            $this->payroll->fileName($month, $ext)
        );
    }
}
