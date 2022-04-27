<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
    Route::get('/', 'App\Http\Controllers\Companies\CompaniesController@index')->middleware(['auth'])->name('companies');
    Route::post('/contacts/create/{id}', 'App\Http\Controllers\Companies\ContactsController@store')->middleware(['auth'])->name('contacts.store');
    Route::post('/contacts/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@update')->middleware(['auth'])->name('contacts.update');
    Route::get('/contacts/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@edit')->middleware(['auth'])->name('contacts.edit');
    Route::post('/addresses/create/{id}', 'App\Http\Controllers\Companies\AddressesController@store')->middleware(['auth'])->name('addresses.store');
    Route::post('/addresses/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@update')->middleware(['auth'])->name('addresses.update');
    Route::get('/addresses/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@edit')->middleware(['auth'])->name('addresses.edit');
    Route::post('/edit/{id}', 'App\Http\Controllers\Companies\CompaniesController@update')->middleware(['auth'])->name('companies.update');
    Route::get('/{id}', 'App\Http\Controllers\Companies\CompaniesController@show')->middleware(['auth'])->name('companies.show');
});

Route::group(['prefix' => 'quotes'], function () {
    Route::get('/', 'App\Http\Controllers\Workflow\QuotesController@index')->middleware(['auth'])->name('quotes'); 
    Route::get('/lines', 'App\Http\Controllers\Workflow\QuoteLinesController@index')->middleware(['auth'])->name('quotes-lines'); 
    Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\QuotesController@update')->middleware(['auth'])->name('quotes.update');
    Route::get('/{id}', 'App\Http\Controllers\Workflow\QuotesController@show')->middleware(['auth'])->name('quotes.show');
});

Route::group(['prefix' => 'orders'], function () {
    Route::get('/', 'App\Http\Controllers\Workflow\OrdersController@index')->middleware(['auth'])->name('orders'); 
    Route::get('/lines', 'App\Http\Controllers\Workflow\OrderLinesController@index')->middleware(['auth'])->name('orders-lines'); 
    Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\OrdersController@update')->middleware(['auth'])->name('orders.update');
    Route::get('/{id}', 'App\Http\Controllers\Workflow\OrdersController@show')->middleware(['auth'])->name('orders.show');
});

Route::group(['prefix' => 'deliverys'], function () {
    Route::get('/', 'App\Http\Controllers\Workflow\DeliverysController@index')->middleware(['auth'])->name('deliverys'); 
    Route::get('/request', 'App\Http\Controllers\Workflow\DeliverysController@request')->middleware(['auth'])->name('deliverys-request'); 
    Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\DeliverysController@update')->middleware(['auth'])->name('deliverys.update');
    Route::get('/{id}', 'App\Http\Controllers\Workflow\DeliverysController@show')->middleware(['auth'])->name('deliverys.show');
});

Route::group(['prefix' => 'invoices'], function () {
    Route::get('/', 'App\Http\Controllers\Workflow\InvoicesController@index')->middleware(['auth'])->name('invoices'); 
    Route::get('/request', 'App\Http\Controllers\Workflow\InvoicesController@request')->middleware(['auth'])->name('invoices-request'); 
    Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\InvoicesController@update')->middleware(['auth'])->name('invoices.update');
    Route::get('/{id}', 'App\Http\Controllers\Workflow\InvoicesController@show')->middleware(['auth'])->name('invoices.show');
});

Route::group(['prefix' => 'purchases'], function () {
    
    Route::get('/request', 'App\Http\Controllers\Purchases\PurchasesController@request')->middleware(['auth'])->name('purchases.request'); 
    Route::get('/quotation', 'App\Http\Controllers\Purchases\PurchasesController@quotation')->middleware(['auth'])->name('purchases.quotation'); 
    Route::get('/', 'App\Http\Controllers\Purchases\PurchasesController@purchase')->middleware(['auth'])->name('purchases'); 
    Route::get('/waiting/receipt', 'App\Http\Controllers\Purchases\PurchasesController@waintingReceipt')->middleware(['auth'])->name('purchases.wainting.receipt'); 
    Route::get('/receipt', 'App\Http\Controllers\Purchases\PurchasesController@receipt')->middleware(['auth'])->name('purchases.receipt'); 
    Route::get('/invoice', 'App\Http\Controllers\Purchases\PurchasesController@invoice')->middleware(['auth'])->name('purchases.invoice'); 

    //only for quote request to purchase order
    Route::post('/Purchase/Order/Create/{id}', 'App\Http\Controllers\Purchases\PurchasesController@storePurchaseOrder')->middleware(['auth'])->name('purchases.orders.store');
    
    Route::post('/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchase')->middleware(['auth'])->name('purchase.update');
    Route::post('/quotation/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchaseQuotation')->middleware(['auth'])->name('quotation.update');
    Route::post('/receipt/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchaseReceipt')->middleware(['auth'])->name('receipt.update');
    
    Route::get('/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showPurchase')->middleware(['auth'])->name('purchase.show');
    Route::get('/quotation/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showQuotation')->middleware(['auth'])->name('purchase.quotation.show');
    Route::get('/receipt/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showReceipt')->middleware(['auth'])->name('purchase.receipt.show');
});

Route::group(['prefix' => 'print'], function () {
    Route::get('/quote/{Document}', 'App\Http\Controllers\PrintController@printQuote')->middleware(['auth'])->name('print.quote');
    Route::get('/order/{Document}', 'App\Http\Controllers\PrintController@printOrder')->middleware(['auth'])->name('print.order');
    Route::get('/order/confirm/{Document}', 'App\Http\Controllers\PrintController@printOrderConfirm')->middleware(['auth'])->name('print.orders.confirm');
    Route::get('/order/manufacturing/{Document}', 'App\Http\Controllers\PrintController@printOrderManufacturingInstruction')->middleware(['auth'])->name('print.manufacturing.instruction');
    Route::get('/delivery/{Document}', 'App\Http\Controllers\PrintController@printDelivery')->middleware(['auth'])->name('print.delivery');
    Route::get('/invoice/{Document}', 'App\Http\Controllers\PrintController@printInvoince')->middleware(['auth'])->name('print.invoice');
    Route::get('/purchase/quotation/{Document}', 'App\Http\Controllers\PrintController@printPurchaseQuotation')->middleware(['auth'])->name('print.purchase.quotation');
    Route::get('/purchase/{Document}', 'App\Http\Controllers\PrintController@printPurchase')->middleware(['auth'])->name('print.purchase');
    Route::get('/receipt/{Document}', 'App\Http\Controllers\PrintController@printReceipt')->middleware(['auth'])->name('print.receipt');
});

Route::group(['prefix' => 'pdf'], function () {
    Route::get('/quote/{Document}', 'App\Http\Controllers\PrintController@getQuotePdf')->middleware(['auth'])->name('pdf.quote');
    Route::get('/order/{Document}', 'App\Http\Controllers\PrintController@getOrderPdf')->middleware(['auth'])->name('pdf.order');
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
    Route::post('/edit/{id}', 'App\Http\Controllers\Products\ProductsController@update')->middleware(['auth'])->name('products.update');
    Route::get('/duplicate/{id}', 'App\Http\Controllers\Products\ProductsController@duplicate')->middleware(['auth'])->name('products.duplicate');
    Route::post('/image', 'App\Http\Controllers\Products\ProductsController@StoreImage')->middleware(['auth'])->name('products.update.image');
    
    //stock route
    Route::get('/Stock', 'App\Http\Controllers\Products\StockController@index')->middleware(['auth'])->name('products.stock'); 
    Route::post('/Stock/create', 'App\Http\Controllers\Products\StockController@store')->middleware(['auth'])->name('products.stock.store');
    Route::get('/Stock/{id}', 'App\Http\Controllers\Products\StockController@show')->middleware(['auth'])->name('products.stocks.show');
    Route::post('/Stock/Location/create', 'App\Http\Controllers\Products\StockLocationController@store')->middleware(['auth'])->name('products.stocklocation.store');
    Route::get('/Stock/Location/{id}', 'App\Http\Controllers\Products\StockLocationController@show')->middleware(['auth'])->name('products.stocklocation.show');
    Route::post('/Stock/Location/product/create', 'App\Http\Controllers\Products\StockLocationProductsController@store')->middleware(['auth'])->name('products.stockline.store');

    Route::get('/{id}', 'App\Http\Controllers\Products\ProductsController@show')->middleware(['auth'])->name('products.show');
});

Route::group(['prefix' => 'task'], function () {
    Route::put('/sync', 'App\Http\Controllers\Planning\TaskController@sync')->name('task.sync');
    Route::get('/{id_type}/{id_page}/show/{id_line}', 'App\Http\Controllers\Planning\TaskController@manage')->middleware(['auth'])->name('task.manage');
    Route::get('/{id_type}/{id_page}/delete/{id_task}', 'App\Http\Controllers\Planning\TaskController@delete')->middleware(['auth'])->name('task.delete');
    Route::post('/create/{id}', 'App\Http\Controllers\Planning\TaskController@store')->middleware(['auth'])->name('task.store');
    Route::post('/update/{id}', 'App\Http\Controllers\Planning\TaskController@update')->middleware(['auth'])->name('task.update');
});

Route::group(['prefix' => 'production'], function () {
    Route::get('/Task', 'App\Http\Controllers\Planning\TaskController@index')->middleware(['auth'])->name('production.task');
    Route::get('/kanban', 'App\Http\Controllers\Planning\TaskController@kanban')->middleware(['auth'])->name('production.kanban');
});

Route::group(['prefix' => 'admin'], function () {
    Route::post('/factory/update', 'App\Http\Controllers\Admin\FactoryController@update')->middleware(['auth'])->name('admin.factory.update');
    Route::get('/factory', 'App\Http\Controllers\Admin\FactoryController@index')->middleware(['auth'])->name('admin.factory');
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


Route::group(['prefix' => 'users'], function () {
    Route::get('/', 'App\Http\Controllers\UsersController@List')->middleware(['auth'])->name('users');
    Route::get('/Profile', 'App\Http\Controllers\UsersController@profile')->middleware(['auth'])->name('user.profile');
    Route::get('/Profile/Update', 'App\Http\Controllers\UsersController@update')->middleware(['auth'])->name('user.profile.update');

});

Route::match(
    ['get', 'post'],
    '/navbar/search',
    'App\Http\Controllers\SearchController@showNavbarSearchResults'
);

require __DIR__.'/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

