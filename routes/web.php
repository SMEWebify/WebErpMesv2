<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/dashboard', 'App\Http\Controllers\HomeController@index')->middleware(['auth'])->name('dashboard');

Route::group(['prefix' => 'companies'], function () {
    //companie route
    Route::get('/', 'App\Http\Controllers\Companies\CompaniesController@index')->middleware(['auth'])->name('companies'); 
    Route::post('/create', 'App\Http\Controllers\Companies\CompaniesController@store')->middleware(['auth'])->name('companies.store');
    Route::get('/create', 'App\Http\Controllers\Companies\CompaniesController@create')->middleware(['auth'])->name('companies.create');
    //contact route
    Route::post('/contacts/create/{id}', 'App\Http\Controllers\Companies\ContactsController@store')->middleware(['auth'])->name('contacts.store');
    Route::get('/contacts/create/{id}', 'App\Http\Controllers\Companies\ContactsController@create')->middleware(['auth'])->name('contacts.create');
    Route::post('/contacts/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@update')->middleware(['auth'])->name('contacts.update');
    Route::get('/contacts/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@edit')->middleware(['auth'])->name('contacts.edit');
    //adresses route
    Route::post('/addresses/create/{id}', 'App\Http\Controllers\Companies\AddressesController@store')->middleware(['auth'])->name('addresses.store');
    Route::get('/addresses/create/{id}', 'App\Http\Controllers\Companies\AddressesController@create')->middleware(['auth'])->name('addresses.create');
    Route::post('/addresses/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@update')->middleware(['auth'])->name('addresses.update');
    Route::get('/addresses/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@edit')->middleware(['auth'])->name('addresses.edit');

    Route::get('/{id}', 'App\Http\Controllers\Companies\CompaniesController@show')->middleware(['auth'])->name('companies.show');
    
});

Route::group(['prefix' => 'accouting'], function () {
    Route::get('/', 'App\Http\Controllers\Accounting\AccountingController@index')->middleware(['auth'])->name('accounting');

    Route::post('/Allocation/create', 'App\Http\Controllers\Accounting\AllocationController@store')->middleware(['auth'])->name('accouting.allocation.create');
    Route::post('/Delivery/create', 'App\Http\Controllers\Accounting\DeliveryController@store')->middleware(['auth'])->name('accouting.delivery.create');
    Route::post('/PaymentCondition/create', 'App\Http\Controllers\Accounting\PaymentConditionsController@store')->middleware(['auth'])->name('accouting.paymentCondition.create');
    Route::post('/PaymentMethod/create', 'App\Http\Controllers\Accounting\PaymentMethodController@store')->middleware(['auth'])->name('accouting.paymentMethod.create');
    Route::post('/VAT/create', 'App\Http\Controllers\Accounting\VatController@store')->middleware(['auth'])->name('accouting.vat.create');
});

Route::group(['prefix' => 'times'], function () {
    Route::get('/', 'App\Http\Controllers\Times\TimesController@index')->middleware(['auth'])->name('times');

    Route::post('/Absence/create', 'App\Http\Controllers\Times\AbsenceController@store')->middleware(['auth'])->name('times.absence.create');
    Route::post('/BanckHoliday/create', 'App\Http\Controllers\Times\BanckHolidayController@store')->middleware(['auth'])->name('times.banckholiday.create');
    Route::post('/ImproductTime/create', 'App\Http\Controllers\Times\ImproductTimeController@store')->middleware(['auth'])->name('times.improducttime.create');
    Route::post('/MachineEvent/create', 'App\Http\Controllers\Times\MachineEventController@store')->middleware(['auth'])->name('times.machineevent.create');
});

Route::group(['prefix' => 'products'], function () {
    Route::get('/', 'App\Http\Controllers\Products\ProductsController@index')->middleware(['auth'])->name('products'); 

    Route::post('/create', 'App\Http\Controllers\Products\ProductsController@store')->middleware(['auth'])->name('products.store');
    Route::get('/create', 'App\Http\Controllers\Products\ProductsController@create')->middleware(['auth'])->name('products.create');

    //stock route
    Route::get('/Stock', 'App\Http\Controllers\Products\StockController@index')->middleware(['auth'])->name('products.stock'); 
    Route::post('/Stock/create', 'App\Http\Controllers\Products\StockController@store')->middleware(['auth'])->name('products.stock.store');
    Route::get('/Stock/{id}', 'App\Http\Controllers\Products\StockController@show')->middleware(['auth'])->name('products.stocks.show');

    Route::post('/Stock/Location/create', 'App\Http\Controllers\Products\StockLocationController@store')->middleware(['auth'])->name('products.stocklocation.store');
    Route::get('/Stock/Location/{id}', 'App\Http\Controllers\Products\StockLocationController@show')->middleware(['auth'])->name('products.stocklocation.show');

    Route::post('/Stock/Location/product/create', 'App\Http\Controllers\Products\StockLocationProductsController@store')->middleware(['auth'])->name('products.stockline.store');

    

    Route::get('/{id}', 'App\Http\Controllers\Products\ProductsController@show')->middleware(['auth'])->name('products.show');
    

});

Route::group(['prefix' => 'quality'], function () {
    Route::get('/', 'App\Http\Controllers\Quality\QualityController@index')->middleware(['auth'])->name('quality');
    Route::post('/Failling/create', 'App\Http\Controllers\Quality\QualityFailureController@store')->middleware(['auth'])->name('quality.failling.create');
    Route::post('/Cause/create', 'App\Http\Controllers\Quality\QualityCauseController@store')->middleware(['auth'])->name('quality.cause.create');
    Route::post('/Correction/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->middleware(['auth'])->name('quality.correction.create');
    Route::post('/Device/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->middleware(['auth'])->name('quality.device.create');
    Route::post('/Action/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->middleware(['auth'])->name('quality.action.create');
    Route::post('/Device/create', 'App\Http\Controllers\Quality\QualityControlDeviceController@store')->middleware(['auth'])->name('quality.device.create');
    Route::post('/NonConformitie/create', 'App\Http\Controllers\Quality\QualityNonConformityController@store')->middleware(['auth'])->name('quality.nonConformitie.create');
    Route::post('/Derogation/create', 'App\Http\Controllers\Quality\QualityDerogationController@store')->middleware(['auth'])->name('quality.derogation.create');
    Route::post('/Action/create', 'App\Http\Controllers\Quality\QualityActionController@store')->middleware(['auth'])->name('quality.action.create');
    
});

Route::group(['prefix' => 'methods'], function () {
    Route::get('/', 'App\Http\Controllers\Methods\MethodsController@index')->middleware(['auth'])->name('methods');

    Route::post('/methods/Unit/create', 'App\Http\Controllers\Methods\UnitsController@store')->middleware(['auth'])->name('methods.unit.create');
    Route::post('/methods/Family/create', 'App\Http\Controllers\Methods\FamiliesController@store')->middleware(['auth'])->name('methods.family.create');
    Route::post('/methods/Service/create', 'App\Http\Controllers\Methods\ServicesController@store')->middleware(['auth'])->name('methods.service.create');
    Route::post('/methods/Section/create', 'App\Http\Controllers\Methods\SectionsController@store')->middleware(['auth'])->name('methods.section.create');
    Route::post('/methods/Ressources/create', 'App\Http\Controllers\Methods\RessourcesController@store')->middleware(['auth'])->name('methods.ressource.create');
    Route::post('/methods/Location/create', 'App\Http\Controllers\Methods\LocationsController@store')->middleware(['auth'])->name('methods.location.create');
    Route::post('/methods/Tool/create', 'App\Http\Controllers\Methods\ToolsController@store')->middleware(['auth'])->name('methods.tool.create');
});




Route::get('/users', 'App\Http\Controllers\UsersController@List')->middleware(['auth'])->name('users');

require __DIR__.'/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

