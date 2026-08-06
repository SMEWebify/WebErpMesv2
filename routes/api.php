<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\EnergyConsumptionController;
use App\Http\Controllers\Api\ExportSalesOrderController;
use App\Http\Controllers\Api\Integrations\QontoIntegrationController;
use App\Http\Controllers\Files\FileApiController;
use App\Http\Controllers\Integrations\QontoWebhookController;

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

Route::prefix('integrations/qonto')->name('api.integrations.qonto.')->withoutMiddleware('auth:api')->group(function () {
    Route::get('/callback', [QontoIntegrationController::class, 'callback'])->name('callback');
    // Webhook Qonto → sans auth, signature HMAC vérifiée dans le contrôleur
    Route::post('/webhook/invoice', [QontoWebhookController::class, 'handle'])->name('webhook.invoice');
});

// Routes externes — authentification par token Bearer (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('clients',            [CompanyController::class, 'clients'])->name('api.clients.index');
    Route::post('quote',             [QuoteController::class,   'store'])->name('api.quote.store');
    Route::put('quote/{quote}',      [QuoteController::class,   'update'])->name('api.quote.update');
    Route::post('product',           [ProductController::class, 'store'])->name('api.product.store');
    Route::put('product/{product}',  [ProductController::class, 'update'])->name('api.product.update');

    // GED — attache un fichier (DXF, SVG, PDF, STL...) à n'importe quelle entité déclarée
    // dans FileableRegistry, y compris quote-line et order-line. Multipart, pas de base64.
    // Le fichier reçu suit exactement le même flux que l'upload React (FileStorageService).
    Route::prefix('files/json')->group(function () {
        Route::get('/list',      [FileApiController::class, 'index'])->name('api.files.list');
        Route::post('/store',    [FileApiController::class, 'store'])->name('api.files.store');
        Route::patch('/{file}',  [FileApiController::class, 'update'])->name('api.files.update');
        Route::delete('/{file}', [FileApiController::class, 'destroy'])->name('api.files.destroy');
    });
});

Route::middleware('auth:api')->group(function () {

    Route::apiResource('companies', CompanyController::class)->names('api.companies');

    Route::apiResource('quote', QuoteController::class)->only(['index', 'show']);
    Route::apiResource('order', OrderController::class);
    Route::apiResource('product', ProductController::class)->only(['index', 'show']);
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('energy-consumptions', EnergyConsumptionController::class)
        ->only(['index','store'])
        ->names('api.energy-consumptions');

    Route::get('/exports/sales-orders', ExportSalesOrderController::class);






    Route::prefix('integrations/qonto')->name('api.integrations.qonto.')->group(function () {
        Route::get('/status', [QontoIntegrationController::class, 'status'])->name('status');
        Route::get('/connect', [QontoIntegrationController::class, 'connect'])->name('connect');
        Route::post('/clients/sync', [QontoIntegrationController::class, 'sync'])->name('clients.sync');
        Route::post('/clients/reconcile', [QontoIntegrationController::class, 'reconcile'])->name('clients.reconcile');
        Route::post('/clients/{wemClientId}/push', [QontoIntegrationController::class, 'push'])->name('clients.push');
        Route::post('/clients/{reviewId}/resolve', [QontoIntegrationController::class, 'resolve'])->name('clients.resolve');
        Route::post('/settings', [QontoIntegrationController::class, 'settings'])->name('settings');
        Route::post('/disconnect', [QontoIntegrationController::class, 'disconnect'])->name('disconnect');
        Route::post('/invoices/{invoiceId}/submit', [QontoIntegrationController::class, 'submitInvoice'])->name('invoices.submit');
        Route::post('/invoices/{invoiceId}/poll', [QontoIntegrationController::class, 'pollInvoice'])->name('invoices.poll');
    });

    // inspection...
    Route::get('/inspection-projects/{id}', 'App\Http\Controllers\Inspection\InspectionProjectController@show');
    Route::get('/inspection-sessions/{id}', 'App\Http\Controllers\Inspection\InspectionMeasureSessionController@show');
    Route::post('/inspection-measures', 'App\Http\Controllers\Inspection\InspectionMeasureController@store');
    Route::put('/inspection-measures/{id}', 'App\Http\Controllers\Inspection\InspectionMeasureController@update');
    Route::post('/inspection-nonconformities', 'App\Http\Controllers\Inspection\InspectionNonconformityController@store');
});
