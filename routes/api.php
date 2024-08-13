<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\CompanyController;

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

//https://github.com/SMEWebify/WebErpMesv2/issues/507
// old route => Route::get('/gantt/data', 'App\Http\Controllers\Planning\GanttController@get');
// new route :
Route::get('/gantt/order/{order_lines_id}', 'App\Http\Controllers\Planning\GanttController@getTasksByOrderLine');

Route::apiResource('company',CompanyController::class);
Route::apiResource('quote',QuoteController::class);
Route::apiResource('order',OrderController::class);
Route::apiResource('tasks', TaskController::class);