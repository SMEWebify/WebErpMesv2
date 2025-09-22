<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportSalesOrderRequest;
use App\Services\Exports\SalesOrderExportService;
use Illuminate\Http\JsonResponse;

class ExportSalesOrderController extends Controller
{
    public function __invoke(ExportSalesOrderRequest $request, SalesOrderExportService $service): JsonResponse
    {
        $data = $service->get($request->options());

        return response()->json([
            'data' => $data,
            'meta' => [
                'count' => count($data),
            ],
        ]);
    }
}
