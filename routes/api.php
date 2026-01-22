<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\EnergyConsumptionController;
use App\Http\Controllers\Api\ExportSalesOrderController;
use App\Http\Controllers\Api\Collaboration\WhiteboardController as ApiWhiteboardController;
use App\Http\Controllers\Api\Collaboration\WhiteboardSnapshotController;
use App\Http\Controllers\Api\Collaboration\WhiteboardFileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->group(function () {

    Route::apiResource('companies', CompanyController::class);

    Route::apiResource('quote', QuoteController::class);
    Route::apiResource('order', OrderController::class);
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('energy-consumptions', EnergyConsumptionController::class)->only(['index','store']);

    Route::get('/exports/sales-orders', ExportSalesOrderController::class);

    Route::prefix('collaboration/whiteboards')->name('api.collaboration.whiteboards.')->group(function () {
        Route::get('/', [ApiWhiteboardController::class, 'index'])->name('index');
        Route::post('/', [ApiWhiteboardController::class, 'store'])->name('store');
        Route::get('/{whiteboard}', [ApiWhiteboardController::class, 'show'])->name('show');
        Route::put('/{whiteboard}', [ApiWhiteboardController::class, 'update'])->name('update');

        Route::get('/{whiteboard}/snapshots', [WhiteboardSnapshotController::class, 'index'])->name('snapshots.index');
        Route::post('/{whiteboard}/snapshots', [WhiteboardSnapshotController::class, 'store'])->name('snapshots.store');

        Route::get('/{whiteboard}/files', [WhiteboardFileController::class, 'index'])->name('files.index');
        Route::post('/{whiteboard}/files', [WhiteboardFileController::class, 'store'])->name('files.store');
    });

    // inspection...
    Route::get('/inspection-projects/{id}', 'App\Http\Controllers\Inspection\InspectionProjectController@show');
    Route::get('/inspection-sessions/{id}', 'App\Http\Controllers\Inspection\InspectionMeasureSessionController@show');
    Route::post('/inspection-measures', 'App\Http\Controllers\Inspection\InspectionMeasureController@store');
    Route::put('/inspection-measures/{id}', 'App\Http\Controllers\Inspection\InspectionMeasureController@update');
    Route::post('/inspection-nonconformities', 'App\Http\Controllers\Inspection\InspectionNonconformityController@store');
});
