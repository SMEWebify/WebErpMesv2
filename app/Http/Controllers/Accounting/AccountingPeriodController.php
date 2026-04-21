<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AccountingPeriodService;

class AccountingPeriodController extends Controller
{
    public function __construct(private AccountingPeriodService $periodService) {}

    public function listJson(): \Illuminate\Http\JsonResponse
    {
        $periods = $this->periodService->all()->map(fn ($p) => [
            'year'           => $p->year,
            'month'          => $p->month,
            'label'          => $p->getLabel(),
            'locked_by_name' => $p->lockedBy?->name,
            'locked_at'      => $p->locked_at?->format('d/m/Y H:i'),
        ]);

        return response()->json($periods);
    }

    public function lock(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'year'  => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $period = $this->periodService->lock($validated['year'], $validated['month']);

        return response()->json([
            'ok'    => true,
            'label' => $period->getLabel(),
        ]);
    }

    public function unlock(int $year, int $month): \Illuminate\Http\JsonResponse
    {
        $this->periodService->unlock($year, $month);

        return response()->json(['ok' => true]);
    }
}
