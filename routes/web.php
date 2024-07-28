<?php

use Carbon\Carbon;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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

Route::group(['prefix' => LaravelLocalization::setLocale(),
                            'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]], function(){

    Route::get('/guest/quote/{uuid}', 'App\Http\Controllers\GuestController@ShowQuoteDocument')->name('guest.quote.show');
    Route::get('/guest/order/{uuid}', 'App\Http\Controllers\GuestController@ShowOrderDocument')->name('guest.order.show');
    Route::get('/guest/delivery/{uuid}', 'App\Http\Controllers\GuestController@ShowDeliveryDocument')->name('guest.delivery.show');
    Route::get('/guest/nonConformitie/{id}', 'App\Http\Controllers\Quality\QualityNonConformityController@createNCFromDelivery')->name('guest.nonConformitie.create');
    Route::get('/guest/', 'App\Http\Controllers\GuestController@index')->name('guest');
    //Rating
    Route::post('/order/ratings', 'App\Http\Controllers\Workflow\OrdersRatingController@store')->name('order.ratings.store');

    Route::get('/dashboard', 'App\Http\Controllers\HomeController@index')->middleware(['auth', 'check.factory'])->name('dashboard');

    Route::group(['prefix' => 'workshop', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workshop\WorkshopController@index')->middleware(['auth', 'check.factory'])->name('workshop');
        Route::get('/Task/Lines', 'App\Http\Controllers\Workshop\WorkshopController@taskLines')->middleware(['auth', 'check.factory'])->name('workshop.task.lines');
        Route::get('/Task/Statu/Id/{id}', 'App\Http\Controllers\Workshop\WorkshopController@statu')->name('workshop.task.statu.id');
        Route::get('/Task/Statu', 'App\Http\Controllers\Workshop\WorkshopController@statu')->name('workshop.task.statu');
        Route::get('/Stock/Detail/{id}', 'App\Http\Controllers\Workshop\WorkshopController@stockDetail')->name('workshop.stock.detail.id');
        Route::get('/Stock/Detail', 'App\Http\Controllers\Workshop\WorkshopController@stockDetail')->name('workshop.stock.detail');
    });
    

    Route::group(['prefix' => 'companies', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Companies\CompaniesController@index')->name('companies');

         // contacts routes
        Route::group(['prefix' => 'contacts'], function () {
            Route::post('/create/{id}', 'App\Http\Controllers\Companies\ContactsController@store')->name('contacts.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@update')->name('contacts.update');
            Route::get('/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@edit')->name('contacts.edit');
        });
    
         // addresses routes
        Route::group(['prefix' => 'addresses'], function () {
            Route::post('/create/{id}', 'App\Http\Controllers\Companies\AddressesController@store')->name('addresses.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@update')->name('addresses.update');
            Route::get('/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@edit')->name('addresses.edit');
        });
    
        Route::post('/import', 'App\Http\Controllers\Admin\ImportsExportsController@importCompanies')->name('companies.import');
        Route::post('/edit/{id}', 'App\Http\Controllers\Companies\CompaniesController@update')->name('companies.update');
        Route::get('/{id}', 'App\Http\Controllers\Companies\CompaniesController@show')->name('companies.show');

        //Rating
        Route::post('/supplier/ratings', 'App\Http\Controllers\Companies\SupplierRatingController@store')->name('companies.ratings.store');
    });

    Route::group(['prefix' => 'leads'], function () {
        //leads
        Route::get('/', 'App\Http\Controllers\Workflow\LeadsController@index')->middleware(['auth', 'check.factory'])->name('leads'); 
    });

    Route::group(['prefix' => 'opportunities', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\OpportunitiesController@index')->name('opportunities');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\OpportunitiesController@show')->name('opportunities.show');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\OpportunitiesController@update')->name('opportunities.update');
        Route::get('/store/quote/{id}', 'App\Http\Controllers\Workflow\OpportunitiesController@storeQuote')->name('opportunities.store.quote');
    
         // store routes
        Route::group(['prefix' => 'store'], function () {
            Route::post('/activity/{id}', 'App\Http\Controllers\Workflow\OpportunityActivitiesController@store')->name('opportunities.store.activity');
            Route::post('/event/{id}', 'App\Http\Controllers\Workflow\OpportunityEventsController@store')->name('opportunities.store.event');
        });
    
        // update routes
        Route::group(['prefix' => 'update'], function () {
            Route::post('/activity/{id}', 'App\Http\Controllers\Workflow\OpportunityActivitiesController@update')->name('opportunities.update.activity');
            Route::post('/event/{id}', 'App\Http\Controllers\Workflow\OpportunityEventsController@update')->name('opportunities.update.event');
        });
    });

    Route::group(['prefix' => 'quotes', 'middleware' => ['auth', 'check.factory', 'check.task.status']], function () {
        //quote
        Route::get('/', 'App\Http\Controllers\Workflow\QuotesController@index')->name('quotes'); 
        Route::get('/lines', 'App\Http\Controllers\Workflow\QuoteLinesController@index')->name('quotes-lines'); 
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\QuotesController@update')->name('quotes.update');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\QuotesController@show')->name('quotes.show');
        //quote line
        Route::post('/{idQuote}/edit-detail-lines/{id}', 'App\Http\Controllers\Workflow\QuoteLinesController@update')->name('quotes.update.detail.line');
        Route::post('/{idQuote}/edit-detail-lines/{id}/image', 'App\Http\Controllers\Workflow\QuoteLinesController@StoreImage')->name('quotes.update.detail.picture');
        Route::post('/{idQuote}/import', 'App\Http\Controllers\Workflow\QuoteLinesController@import')->name('quotes.import');
    });
    

    Route::group(['prefix' => 'orders', 'middleware' => ['auth', 'check.factory', 'check.task.status']], function () {
        //order
        Route::get('/', 'App\Http\Controllers\Workflow\OrdersController@index')->name('orders'); 
        Route::get('/lines', 'App\Http\Controllers\Workflow\OrderLinesController@index')->name('orders-lines'); 
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\OrdersController@update')->name('orders.update');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\OrdersController@show')->name('orders.show');
        //order line
        Route::post('/{idOrder}/edit-detail-lines/{id}', 'App\Http\Controllers\Workflow\OrderLinesController@update')->name('orders.update.detail.line');
        Route::post('/{idOrder}/edit-detail-lines/{id}/image', 'App\Http\Controllers\Workflow\OrderLinesController@StoreImage')->name('orders.update.detail.picture');
        Route::post('/{idOrder}/import', 'App\Http\Controllers\Workflow\OrderLinesController@import')->name('orders.import');
    
    });

    Route::group(['prefix' => 'deliverys', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\DeliverysController@index')->name('deliverys'); 
        Route::get('/request', 'App\Http\Controllers\Workflow\DeliverysController@request')->name('deliverys-request'); 
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\DeliverysController@update')->name('deliverys.update');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\DeliverysController@show')->name('deliverys.show');
    });

    Route::group(['prefix' => 'invoices', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\InvoicesController@index')->name('invoices'); 
        Route::get('/store/delevery/{id}', 'App\Http\Controllers\Workflow\InvoicesController@storeFromDelevery')->name('invoices.store.from.delivery'); 
        Route::get('/request', 'App\Http\Controllers\Workflow\InvoicesController@request')->name('invoices-request'); 
        Route::get('/export', 'App\Http\Controllers\Workflow\InvoicesController@export')->name('invoices.export');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\InvoicesController@update')->name('invoices.update');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\InvoicesController@show')->name('invoices.show');
    });

    Route::group(['prefix' => 'credit-notes', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\CreditNoteController@index')->name('credit-notes');
        Route::post('/store/credit-notes', 'App\Http\Controllers\Workflow\CreditNoteController@CreateCreditNotes')->name('credit-notes.store.from.invoice'); 
        Route::get('/{id}', 'App\Http\Controllers\Workflow\CreditNoteController@show')->name('credit.notes.show');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\CreditNoteController@update')->name('credit.notes.update');
    });

    Route::group(['prefix' => 'purchases', 'middleware' => ['auth', 'check.factory', 'check.task.status']], function () {
        
        Route::get('/request', 'App\Http\Controllers\Purchases\PurchasesController@request')->name('purchases.request'); 
        Route::get('/quotation', 'App\Http\Controllers\Purchases\PurchasesController@quotation')->name('purchases.quotation'); 
        Route::get('/', 'App\Http\Controllers\Purchases\PurchasesController@purchase')->name('purchases'); 
        
        Route::post('/', 'App\Http\Controllers\Purchases\PurchasesController@storePurchase')->name('purchases.store'); 
        Route::get('/waiting/receipt', 'App\Http\Controllers\Purchases\PurchasesController@waintingReceipt')->name('purchases.wainting.receipt'); 
        Route::get('/receipt', 'App\Http\Controllers\Purchases\PurchasesController@receipt')->name('purchases.receipt'); 
        Route::get('/waiting/invoice', 'App\Http\Controllers\Purchases\PurchasesController@waintingInvoice')->name('purchases.wainting.invoice'); 
        Route::get('/invoice', 'App\Http\Controllers\Purchases\PurchasesController@invoice')->name('purchases.invoice'); 

        //only for quote request to purchase order
        Route::post('/Purchase/Order/Create/{id}', 'App\Http\Controllers\Purchases\PurchasesController@storePurchaseOrder')->middleware(['auth'])->name('purchases.orders.store');
        
        Route::post('/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchase')->middleware(['auth'])->name('purchase.update');
        Route::post('/quotation/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchaseQuotation')->middleware(['auth'])->name('quotation.update');
        Route::post('/receipt/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchaseReceipt')->middleware(['auth'])->name('receipt.update');
        Route::post('/invoice/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchaseReceipt')->middleware(['auth'])->name('invoice.update');

        Route::get('/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showPurchase')->middleware(['auth'])->name('purchases.show');
        Route::get('/quotation/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showQuotation')->middleware(['auth'])->name('purchases.quotations.show');
        Route::get('/receipt/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showReceipt')->middleware(['auth'])->name('purchase.receipts.show');
        Route::get('/invoice/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showInvoice')->middleware(['auth'])->name('purchase.invoices.show');
    });

    Route::group(['prefix' => 'print', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/order/manufacturing/{Document}', 'App\Http\Controllers\PrintController@printOrderManufacturingInstruction')->name('print.manufacturing.instruction');
    });

    Route::group(['prefix' => 'pdf', 'middleware' => ['auth', 'check.factory']], function () {
        Route::get('/quote/{Document}', 'App\Http\Controllers\PrintController@getQuotePdf')->name('pdf.quote');
        Route::get('/order/{Document}', 'App\Http\Controllers\PrintController@getOrderPdf')->name('pdf.order');
        Route::get('/order/Confirm/{Document}', 'App\Http\Controllers\PrintController@getOrderConfirmPdf')->name('pdf.orders.confirm');
        Route::get('/delivery/{Document}', 'App\Http\Controllers\PrintController@getDeliveryPdf')->name('pdf.delivery');
        Route::get('/invoice/{Document}', 'App\Http\Controllers\PrintController@getInvoicePdf')->name('pdf.invoice');;
        Route::get('/credit-note/{Document}', 'App\Http\Controllers\PrintController@getCreditNotePdf')->name('pdf.credit.note');
        Route::get('/facture-x/{Document}', 'App\Http\Controllers\PrintController@getInvoiceFactureX')->name('pdf.facturex');
        Route::get('/purchase/quotation/{Document}', 'App\Http\Controllers\PrintController@getPurchaseQuotationPdf')->name('pdf.purchase.quotation');
        Route::get('/purchase/{Document}', 'App\Http\Controllers\PrintController@getPurchasePdf')->name('pdf.purchase');
        Route::get('/receipt/{Document}', 'App\Http\Controllers\PrintController@getReceiptPdf')->name('pdf.receipt');
        Route::get('/nc/{Document}', 'App\Http\Controllers\PrintController@getNCPdf')->name('pdf.nc');
    });

    Route::group(['prefix' => 'accounting', 'middleware' => ['auth', 'check.factory']], function () {
        //index route
        Route::get('/', 'App\Http\Controllers\Accounting\AccountingController@index')->middleware(['auth'])->name('accounting');
        
        // Routes for Allocation
        Route::prefix('allocation')->group(function () {
            Route::post('/create', 'App\Http\Controllers\Accounting\AllocationController@store')->name('accounting.allocation.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Accounting\AllocationController@update')->name('accounting.allocation.update');
        });
    
        // Routes for Delivery
        Route::prefix('delivery')->group(function () {
            Route::post('/create', 'App\Http\Controllers\Accounting\DeliveryController@store')->name('accounting.delivery.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Accounting\DeliveryController@update')->name('accounting.delivery.update');
        });
    
        // Routes for Payment Conditions
        Route::prefix('paymentCondition')->group(function () {
            Route::post('/create', 'App\Http\Controllers\Accounting\PaymentConditionsController@store')->name('accounting.paymentCondition.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Accounting\PaymentConditionsController@update')->name('accounting.paymentCondition.update');
        });
    
        // Routes for Payment Methods
        Route::prefix('paymentMethod')->group(function () {
            Route::post('/create', 'App\Http\Controllers\Accounting\PaymentMethodController@store')->name('accounting.paymentMethod.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Accounting\PaymentMethodController@update')->name('accounting.paymentMethod.update');
        });
    
        // Routes for VAT
        Route::prefix('vat')->group(function () {
            Route::post('/create', 'App\Http\Controllers\Accounting\VatController@store')->name('accounting.vat.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Accounting\VatController@update')->name('accounting.vat.update');
        }); });

        Route::group(['prefix' => 'times', 'middleware' => ['auth', 'check.factory']], function () {
            // Index route
            Route::get('/', 'App\Http\Controllers\Times\TimesController@index')->name('times');
        
            // Absence routes
            Route::group(['prefix' => 'absence'], function () {
                Route::post('/create', 'App\Http\Controllers\Times\AbsenceController@store')->name('times.absence.create');
                Route::post('/edit/{id}', 'App\Http\Controllers\Times\AbsenceController@update')->name('times.absence.update');
            });
        
            // Bank Holiday routes
            Route::group(['prefix' => 'banckholiday'], function () {
                Route::post('/create', 'App\Http\Controllers\Times\BanckHolidayController@store')->name('times.banckholiday.create');
                Route::post('/edit/{id}', 'App\Http\Controllers\Times\BanckHolidayController@update')->name('times.banckholiday.update');
            });
        
            // ImproductTime routes
            Route::group(['prefix' => 'improducttime'], function () {
                Route::post('/create', 'App\Http\Controllers\Times\ImproductTimeController@store')->name('times.improducttime.create');
                Route::post('/edit/{id}', 'App\Http\Controllers\Times\ImproductTimeController@update')->name('times.improducttime.update');
            });
        
            // MachineEvent routes
            Route::group(['prefix' => 'machineevent'], function () {
                Route::post('/create', 'App\Http\Controllers\Times\MachineEventController@store')->name('times.machineevent.create');
                Route::post('/edit/{id}', 'App\Http\Controllers\Times\MachineEventController@update')->name('times.machineevent.update');
            });
        });

    Route::group(['prefix' => 'products', 'middleware' => ['auth', 'check.factory']], function () {
        //index product route
        Route::get('/', 'App\Http\Controllers\Products\ProductsController@index')->name('products');

        //product route 
        Route::post('/create', 'App\Http\Controllers\Products\ProductsController@store')->name('products.store');
        Route::post('/supplier', 'App\Http\Controllers\Products\ProductsController@StoreSupplier')->name('products.supplier.create');
        Route::post('/supplier/price/qty/{id}', 'App\Http\Controllers\Products\ProductsController@StoreSupplierPriceQty')->name('products.supplier.qty.price.create');
        
        Route::post('/edit/{id}', 'App\Http\Controllers\Products\ProductsController@update')->name('products.update');
        Route::get('/duplicate/{id}', 'App\Http\Controllers\Products\ProductsController@duplicate')->name('products.duplicate');
        Route::post('/image', 'App\Http\Controllers\Products\ProductsController@StoreImage')->name('products.update.image');
        Route::post('/drawing', 'App\Http\Controllers\Products\ProductsController@StoreDrawing')->name('products.update.drawing');
        Route::post('/stl', 'App\Http\Controllers\Products\ProductsController@StoreStl')->name('products.update.stl');
        Route::post('/svg', 'App\Http\Controllers\Products\ProductsController@StoreSVG')->name('products.update.svg');

        // Serial numbers routes
        Route::group(['prefix' => 'serial-numbers'], function () {
            Route::get('/', 'App\Http\Controllers\Products\SerialNumbersController@index')->name('products.serialNumbers');
        });

        // Stock routes
        Route::group(['prefix' => 'Stock'], function () {
            Route::get('/', 'App\Http\Controllers\Products\StockController@index')->name('products.stock');
            Route::post('/create', 'App\Http\Controllers\Products\StockController@store')->name('products.stock.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Products\StockController@update')->name('products.stock.update');
            Route::get('/{id}', 'App\Http\Controllers\Products\StockController@show')->name('products.stock.show');

            // Stock detail
            Route::get('/detail/{id}', 'App\Http\Controllers\Products\StockController@detail')->name('products.stock.detail.show');
            Route::post('/detail/edit/{id}', 'App\Http\Controllers\Products\StockController@detailUpdate')->name('products.stock.detail.update');
        });

        // Stock Location routes
        Route::group(['prefix' => 'stock/location'], function () {
            Route::post('/create', 'App\Http\Controllers\Products\StockLocationController@store')->name('products.stocklocation.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Products\StockLocationController@update')->name('products.stocklocation.update');
            Route::get('/{id}', 'App\Http\Controllers\Products\StockLocationController@show')->name('products.stocklocation.show');
        });

        // Stock Location Products routes
        Route::group(['prefix' => 'stock/location/product'], function () {
            Route::post('/create', 'App\Http\Controllers\Products\StockLocationProductsController@store')->name('products.stockline.store');
            Route::post('/create/internal-order', 'App\Http\Controllers\Products\StockLocationProductsController@storeFromInternalOrder')->name('products.stockline.store.from.internal.order');
            Route::post('/create/purchase-order', 'App\Http\Controllers\Products\StockLocationProductsController@storeFromPurchaseOrder')->name('products.stockline.store.from.purchase.order');
            Route::post('/edit/{id}', 'App\Http\Controllers\Products\StockLocationProductsController@update')->name('products.stockline.update');
            Route::get('/{id}', 'App\Http\Controllers\Products\StockLocationProductsController@show')->name('products.stockline.show');
            Route::post('/entry', 'App\Http\Controllers\Products\StockLocationProductsController@entry')->name('products.stockline.manual.entry');
            Route::post('/entry/internal-order', 'App\Http\Controllers\Products\StockLocationProductsController@entryFromInternalOrder')->name('products.stockline.entry.from.internal.order');
            Route::post('/entry/purchase-order', 'App\Http\Controllers\Products\StockLocationProductsController@entryFromPurchaseOrder')->name('products.stockline.entry.from.purchase.order');
            Route::post('/sorting', 'App\Http\Controllers\Products\StockLocationProductsController@sorting')->name('products.stockline.sorting');
        });
        
        Route::get('/{id}', 'App\Http\Controllers\Products\ProductsController@show')->name('products.show');
    });

    Route::group(['prefix' => 'task', 'middleware' => ['auth', 'check.factory']], function () {
        Route::put('/sync', 'App\Http\Controllers\Planning\TaskController@sync')->name('task.sync');
        Route::get('/{id_type}/{id_page}/show/{id_line}', 'App\Http\Controllers\Planning\TaskController@manage')->name('task.manage');
        Route::get('/{id_type}/{id_page}/delete/{id_task}', 'App\Http\Controllers\Planning\TaskController@delete')->name('task.delete');
        Route::post('/create/{id}', 'App\Http\Controllers\Planning\TaskController@store')->name('task.store');
        Route::post('/update/{id}', 'App\Http\Controllers\Planning\TaskController@update')->name('task.update');
    });


    Route::group(['prefix' => 'production', 'middleware' => ['auth', 'check.factory', 'check.task.status']], function () {
        Route::get('/Task/Statu/Id/{id}', 'App\Http\Controllers\Planning\TaskController@statu')->name('production.task.statu.id');
        Route::get('/Task/Statu', 'App\Http\Controllers\Planning\TaskController@statu')->name('production.task.statu');
        Route::get('/Task', 'App\Http\Controllers\Planning\TaskController@index')->name('production.task');
        Route::get('/kanban', 'App\Http\Controllers\Planning\TaskController@kanban')->name('production.kanban');
        Route::get('/calendar/orders', 'App\Http\Controllers\Planning\CalendarController@calendarOders')->name('production.calendar.orders');
        Route::get('/calendar/tasks', 'App\Http\Controllers\Planning\CalendarController@calendarTasks')->name('production.calendar.tasks');
        Route::get('/gantt', 'App\Http\Controllers\Planning\GanttController@index')->name('production.gantt');
        
        Route::get('/load-planning', 'App\Http\Controllers\Planning\PlanningController@index')->name('production.load.planning');
    });

    Route::group(['prefix' => 'admin'], function () {
        
        Route::post('/factory/announcement/create', 'App\Http\Controllers\Admin\FactoryController@storeAnnouncement')->middleware(['auth'])->name('admin.factory.announcement.create');
        Route::get('/factory/announcement/delete/{id}', 'App\Http\Controllers\Admin\FactoryController@deleteAnnouncement')->middleware(['auth'])->name('admin.factory.announcement.delete');
        Route::post('/factory/update', 'App\Http\Controllers\Admin\FactoryController@update')->middleware(['auth'])->name('admin.factory.update');
        Route::get('/factory', 'App\Http\Controllers\Admin\FactoryController@index')->middleware(['auth'])->name('admin.factory');

        Route::get('/roles-permissions/', 'App\Http\Controllers\Admin\RoleController@index')->middleware(['auth'])->name('admin.roles.permissions');
        Route::post('/factory/role/store', 'App\Http\Controllers\Admin\RoleController@store')->middleware(['auth'])->name('admin.factory.role.store');
        Route::post('/factory/role/update/{id}', 'App\Http\Controllers\Admin\RoleController@update')->middleware(['auth'])->name('admin.factory.role.update');
        Route::get('/factory/role/delete/{role}', 'App\Http\Controllers\Admin\RoleController@destroy')->middleware(['auth'])->name('admin.factory.role.destroy');
        Route::post('/factory/permissions/store', 'App\Http\Controllers\Admin\PermissionController@store')->middleware(['auth'])->name('admin.factory.permissions.store');
        Route::get('/factory/permissions/delete/{permission}', 'App\Http\Controllers\Admin\PermissionController@destroy')->middleware(['auth'])->name('admin.factory.permissions.destroy');
        Route::post('/factory/role/permissions/store', 'App\Http\Controllers\Admin\RoleController@RolePemissionStore')->middleware(['auth'])->name('admin.factory.rolepermissions.store');
    
        Route::post('/factory/custom-field/store', 'App\Http\Controllers\Admin\FactoryController@storeCustomField')->middleware(['auth'])->name('admin.factory.custom.field.store');
        Route::post('/factory/custom-field-value/storeOrUpdate/{id}/{type}', 'App\Http\Controllers\Admin\FactoryController@storeOrUpdateCustomField')->middleware(['auth'])->name('admin.factory.custom.field.value.store.update');
        
        
        Route::get('/imports-exports/', 'App\Http\Controllers\Admin\ImportsExportsController@index')->middleware(['auth'])->name('admin.imports.exports');

        Route::get('/logs-view/', 'App\Http\Controllers\Admin\FactoryController@logsView')->middleware(['auth'])->name('admin.logs.view');
    });

    Route::group(['prefix' => 'human-resources', 'middleware' => ['auth', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Admin\HumanResourcesController@index')->name('human.resources');
    
        // Show User
        Route::get('/user/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@ShowUser')->name('human.resources.show.user');
    
        // Update User
        Route::post('/update/user/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@UpdateUser')->name('human.resources.update.user');

        //lock User
        Route::post('/lock/user/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@LockUser')->name('human.resources.lock.user');

        // Employment Contract
        Route::group(['prefix' => 'contract'], function () {
            // Create Employment Contract
            Route::post('/create', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserEmploymentContract')->name('human.resources.create.contract');
    
            // Update Employment Contract
            Route::post('/update', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserEmploymentContract')->name('human.resources.update.contract');
        });

        // Employment Contract
        Route::group(['prefix' => 'expense'], function () {
            // Create Expense category
            Route::post('/create/category', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserExpenseCategorie')->name('human.resources.create.expense.category');
            // Update Expense category
            Route::post('/update/category', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserExpenseCategorie')->name('human.resources.update.expense.category');
            // Create Expense Report User
            Route::post('/create/report', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserExpenseReport')->name('human.resources.create.expense.report');
            // Update Expense Report User
            Route::post('/update/report', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserExpenseReport')->name('human.resources.update.expense.report');
            // Show Expense User
            Route::get('/show/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@ShowExpenseUser')->name('human.resources.show.expense');
            // Create Expense  User
            Route::post('/create/expense/{report_id}', 'App\Http\Controllers\Admin\HumanResourcesController@storeExpenseUser')->name('human.resources.create.expense.line');
            // Update Expense  User
            Route::post('/update/expense/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@updateExpenseUser')->name('human.resources.update.expense.line');
             // Valide Expense  User
            Route::post('/valide/report', 'App\Http\Controllers\Admin\HumanResourcesController@valideExpenseUser')->name('human.resources.valide.expense.report');
        });
    });

    Route::group(['prefix' => 'quality', 'middleware' => ['auth', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Quality\QualityController@index')->name('quality');
    
        // Routes for Action
        Route::group(['prefix' => 'action'], function () {
            Route::get('/', 'App\Http\Controllers\Quality\QualityActionController@index')->name('quality.action');
            Route::post('/create', 'App\Http\Controllers\Quality\QualityActionController@store')->name('quality.action.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityActionController@update')->name('quality.action.update');
        });
    
        // Routes for Device
        Route::group(['prefix' => 'device'], function () {
            Route::post('/create', 'App\Http\Controllers\Quality\QualityControlDeviceController@store')->name('quality.device.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityControlDeviceController@update')->name('quality.device.update');
        });
    
        // Routes for NonConformitie
        Route::group(['prefix' => 'nonConformitie'], function () {
            Route::get('/', 'App\Http\Controllers\Quality\QualityNonConformityController@index')->name('quality.nonConformitie');
            Route::post('/create', 'App\Http\Controllers\Quality\QualityNonConformityController@store')->name('quality.nonConformitie.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityNonConformityController@update')->name('quality.nonConformitie.update');
        });
    
        // Routes for Derogation
        Route::group(['prefix' => 'derogation'], function () {
            Route::get('/', 'App\Http\Controllers\Quality\QualityDerogationController@index')->name('quality.derogation');
            Route::post('/create', 'App\Http\Controllers\Quality\QualityDerogationController@store')->name('quality.derogation.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityDerogationController@update')->name('quality.derogation.update');
        });

        // Routes for AMDEC
        Route::group(['prefix' => 'amdec'], function () {
            Route::get('/', 'App\Http\Controllers\Quality\QualityAmdecController@index')->name('quality.amdec');
            Route::post('/create', 'App\Http\Controllers\Quality\QualityAmdecController@store')->name('quality.amdec.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityAmdecController@update')->name('quality.amdec.update');
        });
    
        // Routes for Failure
        Route::group(['prefix' => 'failure'], function () {
            Route::post('/create', 'App\Http\Controllers\Quality\QualityFailureController@store')->name('quality.failure.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityFailureController@update')->name('quality.failure.update');
        });
    
        // Routes for Cause
        Route::group(['prefix' => 'cause'], function () {
            Route::post('/create', 'App\Http\Controllers\Quality\QualityCauseController@store')->name('quality.cause.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityCauseController@update')->name('quality.cause.update');
        });
    
        // Routes for Correction
        Route::group(['prefix' => 'correction'], function () {
            Route::post('/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->name('quality.correction.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Quality\QualityCorrectionController@update')->name('quality.correction.update');
        });
    });

    Route::group(['prefix' => 'methods', 'middleware' => ['auth', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Methods\MethodsController@index')->name('methods');
    
        // Routes for Unit
        Route::group(['prefix' => 'unit'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\UnitsController@store')->name('methods.unit.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\UnitsController@update')->name('methods.unit.update');
        });
    
        // Routes for Family
        Route::group(['prefix' => 'family'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\FamiliesController@store')->name('methods.family.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\FamiliesController@update')->name('methods.family.update');
        });
    
        // Routes for Service
        Route::group(['prefix' => 'service'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\ServicesController@store')->name('methods.service.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\ServicesController@update')->name('methods.service.update');
            Route::post('/edit/{id}/image', 'App\Http\Controllers\Methods\ServicesController@StoreImage')->name('methods.service.update.picture');
        });
    
        // Routes for Section
        Route::group(['prefix' => 'section'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\SectionsController@store')->name('methods.section.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\SectionsController@update')->name('methods.section.update');
        });
    
        // Routes for Ressources
        Route::group(['prefix' => 'ressources'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\RessourcesController@store')->name('methods.ressource.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\RessourcesController@update')->name('methods.ressource.update');
            Route::post('/edit/{id}/image', 'App\Http\Controllers\Methods\RessourcesController@StoreImage')->name('methods.ressource.update.picture');
        });
    
        // Routes for Location
        Route::group(['prefix' => 'location'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\LocationsController@store')->name('methods.location.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\LocationsController@update')->name('methods.location.update');
        });
    
        // Routes for Tool
        Route::group(['prefix' => 'tool'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\ToolsController@store')->name('methods.tool.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\ToolsController@update')->name('methods.tool.update');
            Route::post('/edit/{id}/image', 'App\Http\Controllers\Methods\ToolsController@StoreImage')->name('methods.tool.update.picture');
        });

        // Routes for Standard Nomenclature
        Route::group(['prefix' => 'standard-nomenclature'], function () {
            Route::post('/create', 'App\Http\Controllers\Methods\StandardNomenclatureController@store')->name('methods.standard.nomenclature.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\StandardNomenclatureController@update')->name('methods.standard.nomenclature.update');
        });
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/get', 'App\Http\Controllers\NotificationsController@getNotificationsData')->middleware(['auth'])->name('notifications.get');
        Route::get('/show', 'App\Http\Controllers\UsersController@profile')->middleware(['auth'])->name('notifications.show');
        Route::post('/show', 'App\Http\Controllers\UsersController@settingNotification')->middleware(['auth'])->name('notifications.setting');
    });

    Route::post('upload-file', 'App\Http\Controllers\FileUpload@fileUpload')->middleware(['auth'])->name('file.store');
    Route::post('upload-photo', 'App\Http\Controllers\FileUpload@photoUpload')->middleware(['auth'])->name('photo.store');

    Route::get('/licence', function () {return view('licence');})->middleware(['auth'])->name('licence');

    Route::get('/rgpd-policy', function () {return view('rgpd-policy');})->middleware(['auth'])->name('rgpd.policy');

    Route::get('/iframe-mode', function () {return view('iframe-mode');})->middleware(['auth'])->name('iframe.mode');

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'App\Http\Controllers\UsersController@List')->middleware(['auth'])->name('users');
        Route::get('/Profile/{id}', 'App\Http\Controllers\UsersController@profile')->middleware(['auth'])->name('user.profile');
        Route::get('/Profile/Update', 'App\Http\Controllers\UsersController@update')->middleware(['auth'])->name('user.profile.update');
    });

    Route::match(
        ['get', 'post'],
        '/navbar/search',
        'App\Http\Controllers\SearchController@showNavbarSearchResults'
        
    );

    require __DIR__.'/auth.php';

    Auth::routes();

    Route::get('/home', 'App\Http\Controllers\HomeController@index')->middleware(['auth'])->name('home');

    Livewire::setUpdateRoute(function ($handle) {
        return Route::post('/livewire/update', $handle);
    });

});