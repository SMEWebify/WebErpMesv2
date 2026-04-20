<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Collaboration\WhiteboardController as CollaborationWhiteboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EnergyConsumptionController;
use App\Http\Controllers\ProductionTraceController;
use App\Http\Controllers\SpreadsheetController;
use App\Http\Controllers\SpreadsheetDataController;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
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

    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('/guest/quote/{uuid}', 'App\Http\Controllers\GuestController@ShowQuoteDocument')->name('guest.quote.show');
        Route::get('/guest/order/{uuid}', 'App\Http\Controllers\GuestController@ShowOrderDocument')->name('guest.order.show');
        Route::get('/guest/delivery/{uuid}', 'App\Http\Controllers\GuestController@ShowDeliveryDocument')->name('guest.delivery.show');
        Route::get('/guest/nonConformitie/{uuid}/{id}', 'App\Http\Controllers\Quality\QualityNonConformityController@createNCFromDelivery')->name('guest.nonConformitie.create');
        Route::get('/guest/', 'App\Http\Controllers\GuestController@index')->name('guest');
    });
    Route::get('/integrations/qonto/callback', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'callback'])->name('admin.integrations.qonto.callback');

    Route::get('/pointage', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/pointage', [AttendanceController::class, 'store'])->name('attendance.store');
    //Rating
    Route::post('/order/ratings', 'App\Http\Controllers\Workflow\OrdersRatingController@store')->name('order.ratings.store');

    Route::prefix('customer')->name('customer.')->group(function () {
        Route::middleware('guest:customer')->group(function () {
            Route::get('login', 'App\Http\Controllers\Customer\Auth\AuthenticatedSessionController@create')->name('login');
            Route::post('login', 'App\Http\Controllers\Customer\Auth\AuthenticatedSessionController@store')->name('login.store');
        });

        Route::middleware('customer')->group(function () {
            Route::post('logout', 'App\Http\Controllers\Customer\Auth\AuthenticatedSessionController@destroy')->name('logout');
            Route::get('/', 'App\Http\Controllers\Customer\PortalController@index')->name('dashboard');
            Route::get('/orders/{order}', 'App\Http\Controllers\Customer\PortalController@showOrder')->name('orders.show');
            Route::get('/deliveries/{delivery}', 'App\Http\Controllers\Customer\PortalController@showDelivery')->name('deliveries.show');
            Route::get('/invoices/{invoice}', 'App\Http\Controllers\Customer\PortalController@showInvoice')->name('invoices.show');
        });
    });


    Route::get('/pending-role', fn () => view('pending-role'))->middleware(['auth', 'verified'])->name('pending.role');

    // --- Setup wizard (installation initiale) ---
    Route::middleware(['auth'])->prefix('setup')->name('setup.')->group(function () {
        Route::get('/',                    'App\Http\Controllers\Setup\SetupController@index')->name('index');
        Route::post('/company',            'App\Http\Controllers\Setup\SetupController@saveCompany')->name('company');
        Route::post('/vat',                'App\Http\Controllers\Setup\SetupController@saveVat')->name('vat');
        Route::post('/payment-condition',  'App\Http\Controllers\Setup\SetupController@savePaymentCondition')->name('payment-condition');
        Route::post('/payment-method',     'App\Http\Controllers\Setup\SetupController@savePaymentMethod')->name('payment-method');
        Route::post('/delivery',           'App\Http\Controllers\Setup\SetupController@saveDelivery')->name('delivery');
        Route::post('/unit',               'App\Http\Controllers\Setup\SetupController@saveUnit')->name('unit');
        Route::post('/role',               'App\Http\Controllers\Setup\SetupController@saveRole')->name('role');
        Route::post('/estimated-budget',   'App\Http\Controllers\Setup\SetupController@saveEstimatedBudget')->name('estimated-budget');
    });

    Route::get('/dashboard', 'App\Http\Controllers\HomeController@index')->middleware(['auth', 'verified', 'has.role', 'check.factory'])->name('dashboard');

    // KPI JSON endpoints (session auth, consommés par les widgets React)
    Route::prefix('kpi')->name('kpi.')->middleware(['auth', 'verified', 'has.role', 'check.factory'])->group(function () {
        Route::get('/quotes/rate',    'App\Http\Controllers\Api\KpiController@quoteRate')->name('quotes.rate');
        Route::get('/orders/monthly', 'App\Http\Controllers\Api\KpiController@ordersMonthly')->name('orders.monthly');
        Route::get('/delivery/board', 'App\Http\Controllers\Api\KpiController@deliveryBoard')->name('delivery.board');
        Route::get('/recent/orders',  'App\Http\Controllers\Api\KpiController@recentOrders')->name('recent.orders');
        Route::get('/recent/quotes',     'App\Http\Controllers\Api\KpiController@recentQuotes')->name('recent.quotes');
        Route::get('/recent/invoices',   'App\Http\Controllers\Api\KpiController@recentInvoices')->name('recent.invoices');
        Route::get('/recent/deliveries', 'App\Http\Controllers\Api\KpiController@recentDeliveries')->name('recent.deliveries');
        Route::get('/recent/purchases',  'App\Http\Controllers\Api\KpiController@recentPurchases')->name('recent.purchases');
        Route::get('/top-clients',       'App\Http\Controllers\Api\KpiController@topClients')->name('top.clients');
        Route::get('/nc-stats',          'App\Http\Controllers\Api\KpiController@ncStats')->name('nc.stats');
        Route::get('/supplier-delays',   'App\Http\Controllers\Api\KpiController@supplierDelays')->name('supplier.delays');
        Route::get('/otd',               'App\Http\Controllers\Api\KpiController@otd')->name('otd');
        Route::get('/mood',              'App\Http\Controllers\Api\KpiController@mood')->name('mood');
        Route::post('/mood',             'App\Http\Controllers\Api\KpiController@setMood')->name('mood.set');
    });

    // Assistant IA ERP
    Route::middleware(['auth', 'verified', 'has.role'])->prefix('ai')->name('ai.')->group(function () {
        Route::post('/chat', [\App\Http\Controllers\AI\AssistantChatController::class, 'chat'])->name('chat');
    });

    // Dashboard config (personnalisation par utilisateur)
    Route::middleware(['auth', 'verified', 'has.role', 'check.factory'])->group(function () {
        Route::get('/dashboard/config', 'App\Http\Controllers\DashboardConfigController@show')->name('dashboard.config.show');
        Route::put('/dashboard/config', 'App\Http\Controllers\DashboardConfigController@update')->name('dashboard.config.update');
    });

    // Vue du jour
    Route::middleware(['auth', 'verified', 'has.role', 'check.factory'])->prefix('today')->group(function () {
        Route::get('/config',              'App\Http\Controllers\TodayController@configShow')->name('today.config.show');
        Route::put('/config',              'App\Http\Controllers\TodayController@configUpdate')->name('today.config.update');
        Route::get('/invoices-overdue',    'App\Http\Controllers\TodayController@invoicesOverdue')->name('today.invoices.overdue');
        Route::get('/orders-late',         'App\Http\Controllers\TodayController@ordersLate')->name('today.orders.late');
        Route::get('/orders-due-week',     'App\Http\Controllers\TodayController@ordersDueWeek')->name('today.orders.due.week');
        Route::get('/quotes-expiring',     'App\Http\Controllers\TodayController@quotesExpiring')->name('today.quotes.expiring');
        Route::get('/leads-pending',       'App\Http\Controllers\TodayController@leadsPending')->name('today.leads.pending');
        Route::get('/recent-activity',     'App\Http\Controllers\TodayController@recentActivity')->name('today.recent.activity');
    });

    Route::group(['prefix' => 'collaboration', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/whiteboards', [CollaborationWhiteboardController::class, 'show'])->name('collaboration.whiteboards.index');
        Route::get('/whiteboards/{whiteboard}', [CollaborationWhiteboardController::class, 'show'])->name('collaboration.whiteboards.show');
    });


    Route::get('/reports', 'App\\Http\\Controllers\\ReportsController@index')->middleware(['auth', 'verified', 'has.role', 'check.factory'])->name('reports');
    Route::get('/reports/accounting', 'App\\Http\\Controllers\\ReportsController@accounting')->middleware(['auth', 'verified', 'has.role', 'check.factory'])->name('reports.accounting');

    Route::get('/documents', [DocumentController::class, 'index'])
        ->middleware(['auth', 'verified', 'has.role', 'check.factory'])
        ->name('documents.index');

    Route::group(['prefix' => 'spreadsheet', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'permission:spreadsheet-menu']], function () {
        Route::get('/', [SpreadsheetController::class, 'index'])->name('spreadsheet.index');
        Route::get('/create', [SpreadsheetController::class, 'create'])->name('spreadsheet.create');
        Route::post('/', [SpreadsheetController::class, 'store'])->name('spreadsheet.store');
        Route::get('/{spreadsheet}/edit', [SpreadsheetController::class, 'edit'])->name('spreadsheet.edit');
        Route::put('/{spreadsheet}', [SpreadsheetController::class, 'update'])->name('spreadsheet.update');
        Route::delete('/{spreadsheet}', [SpreadsheetController::class, 'destroy'])->name('spreadsheet.destroy');
        Route::post('/{spreadsheet}/save', [SpreadsheetController::class, 'save'])->name('spreadsheet.save');
    });

    Route::group(['prefix' => 'workshop', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workshop\WorkshopController@index')->middleware(['auth', 'verified', 'has.role', 'check.factory'])->name('workshop');
        Route::get('/Task/Lines', 'App\Http\Controllers\Workshop\WorkshopController@taskLines')->middleware(['auth', 'verified', 'has.role', 'check.factory'])->name('workshop.task.lines');
        Route::get('/Task/Statu/Id/{id}', 'App\Http\Controllers\Workshop\WorkshopController@statu')->name('workshop.task.statu.id');
        Route::get('/Task/Statu', 'App\Http\Controllers\Workshop\WorkshopController@statu')->name('workshop.task.statu');
        Route::get('/Stock/Detail/{id}', 'App\Http\Controllers\Workshop\WorkshopController@stockDetail')->name('workshop.stock.detail.id');
        Route::get('/Stock/Detail', 'App\Http\Controllers\Workshop\WorkshopController@stockDetail')->name('workshop.stock.detail');

        
        Route::get('/andon', 'App\Http\Controllers\Planning\AndonAlertController@taskAlertsDashboard')->name('workshop.andon');
        Route::post('/andon/store', 'App\Http\Controllers\Planning\AndonAlertController@triggerAlert')->name('workshop.andon.store');
        Route::post('/andon/inProgress/{id}', 'App\Http\Controllers\Planning\AndonAlertController@inProgressAlert')->name('workshop.andon.inProgress');
        Route::post('/andon/resolve/{id}', 'App\Http\Controllers\Planning\AndonAlertController@resolveAlert')->name('workshop.andon.resolve');

        Route::get('/andon/task-activity', 'App\Http\Controllers\Planning\AndonAlertController@taskActivityDashboard')->name('workshop.andon.task-activity');
        Route::get('/andon/orders-dashboard', 'App\Http\Controllers\Planning\AndonAlertController@orderWorkshopDashboard')->name('workshop.andon.orders.dashboard');
   
    });
    

    Route::group(['prefix' => 'companies', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Companies\CompaniesController@index')->name('companies');

        // JSON endpoints for React
        Route::get('/json/list', 'App\Http\Controllers\Companies\CompaniesController@listJson')->name('companies.json.list');
        Route::post('/json/store', 'App\Http\Controllers\Companies\CompaniesController@storeJson')->name('companies.json.store');
        Route::get('/json/select-data', 'App\Http\Controllers\Companies\CompaniesController@selectDataJson')->name('companies.json.select-data');

        // addresses routes
        Route::group(['prefix' => 'addresses'], function () {
            Route::post('/create/{id}', 'App\Http\Controllers\Companies\AddressesController@store')->name('addresses.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@update')->name('addresses.update');
            Route::get('/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@edit')->name('addresses.edit');
            Route::post('/json/store', 'App\Http\Controllers\Companies\AddressesController@storeJson')->name('addresses.json.store');
            Route::post('/json/update/{address}', 'App\Http\Controllers\Companies\AddressesController@updateJson')->name('addresses.json.update');
        });
    
        Route::post('/import', 'App\Http\Controllers\Admin\ImportsExportsController@importCompanies')->name('companies.import');
        Route::post('/edit/{id}', 'App\Http\Controllers\Companies\CompaniesController@update')->name('companies.edit.update');
        Route::post('/json/update/{company}', 'App\Http\Controllers\Companies\CompaniesController@updateJson')->name('companies.json.update');
        Route::get('/{id}', 'App\Http\Controllers\Companies\CompaniesController@show')->name('companies.show');
        Route::get('/{id}/json/timeline', 'App\Http\Controllers\Companies\CompaniesController@timelineJson')->name('companies.json.timeline');

        Route::get('/store/quote/{id}', 'App\Http\Controllers\Companies\CompaniesController@storeQuote')->name('companies.store.quote');

        //Rating
        Route::post('/supplier/ratings', 'App\Http\Controllers\Companies\SupplierRatingController@store')->name('companies.ratings.store');
    });

    $contactMiddleware = app()->environment('testing') ? [] : ['auth', 'verified', 'has.role', 'check.factory'];

    Route::group(['prefix' => 'companies/contacts', 'middleware' => $contactMiddleware], function () {
        Route::post('/create/{id}', 'App\Http\Controllers\Companies\ContactsController@store')->name('contacts.store');
        Route::match(['post', 'put'], '/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@update')->name('contacts.update');
        Route::get('/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@edit')->name('contacts.edit');
        Route::post('/json/store', 'App\Http\Controllers\Companies\ContactsController@storeJson')->name('contacts.json.store');
        Route::post('/json/update/{contact}', 'App\Http\Controllers\Companies\ContactsController@updateJson')->name('contacts.json.update');
    });

    Route::group(['prefix' => 'companies', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::post('/{company}/document-defaults/sync-contact/{contact}', 'App\Http\Controllers\Companies\CompanyDocumentDefaultsController@syncContact')->name('companies.document-defaults.sync-contact');
        Route::post('/{company}/document-defaults/sync-address/{address}', 'App\Http\Controllers\Companies\CompanyDocumentDefaultsController@syncAddress')->name('companies.document-defaults.sync-address');
    });

    Route::group(['prefix' => 'leads', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\LeadsController@index')->name('leads');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\LeadsController@update')->name('leads.update');
        Route::get('/store/opportunity/{id}', 'App\Http\Controllers\Workflow\LeadsController@storeOpportunity')->name('leads.store.opportunity');

        // JSON endpoints for React LeadsIndex
        Route::get('/json/list',                  'App\Http\Controllers\Workflow\LeadsController@listJson')->name('leads.json.list');
        Route::get('/json/kanban',                'App\Http\Controllers\Workflow\LeadsController@kanbanJson')->name('leads.json.kanban');
        Route::put('/json/kanban/{id}/move',      'App\Http\Controllers\Workflow\LeadsController@kanbanMoveJson')->name('leads.json.kanban-move');
        Route::post('/json/store',                'App\Http\Controllers\Workflow\LeadsController@storeJson')->name('leads.json.store');
        Route::get('/json/select-data',           'App\Http\Controllers\Workflow\LeadsController@selectDataJson')->name('leads.json.select-data');
        Route::get('/json/addresses/{companyId}', 'App\Http\Controllers\Workflow\LeadsController@addressesJson')->name('leads.json.addresses');
        Route::get('/json/contacts/{companyId}',  'App\Http\Controllers\Workflow\LeadsController@contactsJson')->name('leads.json.contacts');

        Route::get('/{id}', 'App\Http\Controllers\Workflow\LeadsController@show')->name('leads.show');
    });

    Route::group(['prefix' => 'opportunities', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\OpportunitiesController@index')->name('opportunities');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\OpportunitiesController@update')->name('opportunities.update');
        Route::get('/store/quote/{id}', 'App\Http\Controllers\Workflow\OpportunitiesController@storeQuote')->name('opportunities.store.quote');

        // JSON endpoints for React OpportunitiesIndex
        Route::get('/json/list',                  'App\Http\Controllers\Workflow\OpportunitiesController@listJson')->name('opportunities.json.list');
        Route::get('/json/kanban',                'App\Http\Controllers\Workflow\OpportunitiesController@kanbanJson')->name('opportunities.json.kanban');
        Route::put('/json/kanban/{id}/move',      'App\Http\Controllers\Workflow\OpportunitiesController@kanbanMoveJson')->name('opportunities.json.kanban-move');
        Route::post('/json/store',                'App\Http\Controllers\Workflow\OpportunitiesController@storeJson')->name('opportunities.json.store');
        Route::get('/json/select-data',           'App\Http\Controllers\Workflow\OpportunitiesController@selectDataJson')->name('opportunities.json.select-data');
        Route::get('/json/addresses/{companyId}', 'App\Http\Controllers\Workflow\OpportunitiesController@addressesJson')->name('opportunities.json.addresses');
        Route::get('/json/contacts/{companyId}',  'App\Http\Controllers\Workflow\OpportunitiesController@contactsJson')->name('opportunities.json.contacts');

        Route::get('/{id}', 'App\Http\Controllers\Workflow\OpportunitiesController@show')->name('opportunities.show');

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

    Route::group(['prefix' => 'quotes', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'check.task.status']], function () {
        //quote
        Route::get('/', 'App\Http\Controllers\Workflow\QuotesController@index')->name('quotes');
        Route::get('/lines', 'App\Http\Controllers\Workflow\QuoteLinesController@index')->name('quotes-lines');
        Route::get('/lines/json', 'App\Http\Controllers\Workflow\QuoteLinesController@listJson')->name('quote-lines.json.list');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\QuotesController@update')->name('quotes.update');
        // JSON API for React QuotesIndex
        Route::get('/json/list', 'App\Http\Controllers\Workflow\QuotesController@listJson')->name('quotes.json.list');
        Route::post('/json/store', 'App\Http\Controllers\Workflow\QuotesController@storeJson')->name('quotes.json.store');
        Route::get('/json/select-data', 'App\Http\Controllers\Workflow\QuotesController@selectDataJson')->name('quotes.json.select-data');
        Route::get('/json/addresses/{companyId}', 'App\Http\Controllers\Workflow\QuotesController@addressesJson')->name('quotes.json.addresses');
        Route::get('/json/contacts/{companyId}', 'App\Http\Controllers\Workflow\QuotesController@contactsJson')->name('quotes.json.contacts');
        Route::post('/json/address', 'App\Http\Controllers\Workflow\QuotesController@storeAddressJson')->name('quotes.json.address.store');
        Route::post('/json/contact', 'App\Http\Controllers\Workflow\QuotesController@storeContactJson')->name('quotes.json.contact.store');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\QuotesController@show')->name('quotes.show');
        //quote line
        Route::post('/{idQuote}/edit-detail-lines/{id}', 'App\Http\Controllers\Workflow\QuoteLinesController@update')->name('quotes.update.detail.line');
        Route::post('/{idQuote}/edit-detail-lines/{id}/image', 'App\Http\Controllers\Workflow\QuoteLinesController@StoreImage')->name('quotes.update.detail.picture');
        Route::post('/{idQuote}/lines/import', 'App\Http\Controllers\Workflow\QuoteLinesController@import')->name('quotes.lines.import');
        // JSON API for React QuoteLinesPage
        Route::get('/{quoteId}/lines/json', 'App\Http\Controllers\Workflow\QuoteLinesController@linesForQuoteJson')->name('quotes.lines.json.for-quote');
        Route::get('/{quoteId}/lines/json/select-data', 'App\Http\Controllers\Workflow\QuoteLinesController@selectDataForQuoteJson')->name('quotes.lines.json.select-data');
        Route::get('/{quoteId}/lines/json/price-list/{productId}', 'App\Http\Controllers\Workflow\QuoteLinesController@priceListForProductJson')->name('quotes.lines.json.price-list');
        Route::post('/{quoteId}/lines/json/store', 'App\Http\Controllers\Workflow\QuoteLinesController@storeLineJson')->name('quotes.lines.json.store');
        Route::post('/{quoteId}/lines/json/price-increase', 'App\Http\Controllers\Workflow\QuoteLinesController@priceIncreaseJson')->name('quotes.lines.json.price-increase');
        Route::put('/{quoteId}/lines/json/{id}', 'App\Http\Controllers\Workflow\QuoteLinesController@updateLineJson')->name('quotes.lines.json.update');
        Route::delete('/{quoteId}/lines/json/{id}', 'App\Http\Controllers\Workflow\QuoteLinesController@destroyLineJson')->name('quotes.lines.json.destroy');
        Route::post('/{quoteId}/lines/json/{id}/duplicate', 'App\Http\Controllers\Workflow\QuoteLinesController@duplicateLineJson')->name('quotes.lines.json.duplicate');
        Route::post('/{quoteId}/lines/json/{id}/breakdown', 'App\Http\Controllers\Workflow\QuoteLinesController@breakDownLineJson')->name('quotes.lines.json.breakdown');
        Route::post('/{quoteId}/lines/json/{id}/move', 'App\Http\Controllers\Workflow\QuoteLinesController@moveLineJson')->name('quotes.lines.json.move');
        Route::post('/{quoteId}/lines/json/reorder', 'App\Http\Controllers\Workflow\QuoteLinesController@reorderJson')->name('quotes.lines.json.reorder');
        Route::post('/{quoteId}/lines/json/store-order', 'App\Http\Controllers\Workflow\QuoteLinesController@storeOrderJson')->name('quotes.lines.json.store-order');
        Route::post('/{quoteId}/lines/json/import-sym', 'App\Http\Controllers\Workflow\QuoteLinesController@importSymJson')->name('quotes.lines.json.import-sym');
        Route::post('/{quoteId}/lines/json/{id}/create-product', 'App\Http\Controllers\Workflow\QuoteLinesController@createProductJson')->name('quotes.lines.json.create-product');
        Route::get('/{quoteId}/lines/json/{id}/tasks', 'App\Http\Controllers\Workflow\QuoteLinesController@tasksForLineJson')->name('quotes.lines.json.tasks');
        Route::patch('/{quoteId}/lines/json/{id}/calculated-price', 'App\Http\Controllers\Workflow\QuoteLinesController@toggleCalculatedPriceJson')->name('quotes.lines.json.calculated-price');
        Route::get('/{idQuote}/lines/{id}/detail-edit', 'App\Http\Controllers\Workflow\QuoteLinesController@detailEdit')->name('quotes.lines.detail.edit');
        Route::post('/{id}/delivery-simulation', 'App\Http\Controllers\Workflow\QuotesController@simulateDelivery')->name('quotes.delivery.simulation');
        //Project estimate
        Route::post('project-estimate/save/{id}', 'App\Http\Controllers\Workflow\QuotesController@saveProjectEstimate')->name('quotes.project.estimates');
        //import
        Route::post('/import', 'App\Http\Controllers\Admin\ImportsExportsController@importQuotes')->name('quotes.import');
        
    });
    

    Route::group(['prefix' => 'orders', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'check.task.status']], function () {
        //order
        Route::get('/', 'App\Http\Controllers\Workflow\OrdersController@index')->name('orders');
        Route::get('/lines', 'App\Http\Controllers\Workflow\OrderLinesController@index')->name('orders-lines');
        Route::get('/lines/json', 'App\Http\Controllers\Workflow\OrderLinesController@listJson')->name('order-lines.json.list');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\OrdersController@update')->name('orders.update');
        // JSON endpoints for React OrdersIndex
        Route::get('/json/list',                  'App\Http\Controllers\Workflow\OrdersController@listJson')->name('orders.json.list');
        Route::post('/json/store',                'App\Http\Controllers\Workflow\OrdersController@storeJson')->name('orders.json.store');
        Route::get('/json/select-data',           'App\Http\Controllers\Workflow\OrdersController@selectDataJson')->name('orders.json.select-data');
        Route::get('/json/addresses/{companyId}', 'App\Http\Controllers\Workflow\OrdersController@addressesJson')->name('orders.json.addresses');
        Route::get('/json/contacts/{companyId}',  'App\Http\Controllers\Workflow\OrdersController@contactsJson')->name('orders.json.contacts');
        Route::post('/json/address/store',        'App\Http\Controllers\Workflow\OrdersController@storeAddressJson')->name('orders.json.address.store');
        Route::post('/json/contact/store',        'App\Http\Controllers\Workflow\OrdersController@storeContactJson')->name('orders.json.contact.store');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\OrdersController@show')->name('orders.show');
        Route::get('/{id}/json/purchase-history', 'App\Http\Controllers\Workflow\OrdersController@purchaseHistoryJson')->name('orders.json.purchase-history');
        Route::post('/{order}/calculate-task-dates', 'App\Http\Controllers\Workflow\OrdersController@calculateTaskDates')->name('orders.calculate.task.dates');
        //order line
        Route::post('/{idOrder}/edit-detail-lines/{id}', 'App\Http\Controllers\Workflow\OrderLinesController@update')->name('orders.update.detail.line');
        Route::post('/{idOrder}/edit-detail-lines/{id}/image', 'App\Http\Controllers\Workflow\OrderLinesController@StoreImage')->name('orders.update.detail.picture');
        Route::post('/{idOrder}/lines/import', 'App\Http\Controllers\Workflow\OrderLinesController@import')->name('orders.lines.import');
        // JSON API — React OrderLinesPage
        Route::get('/{orderId}/lines/json',                             'App\Http\Controllers\Workflow\OrderLinesController@linesForOrderJson')->name('orders.lines.json.for-order');
        Route::get('/{orderId}/lines/json/select-data',                 'App\Http\Controllers\Workflow\OrderLinesController@selectDataForOrderJson')->name('orders.lines.json.select-data');
        Route::get('/{orderId}/lines/json/price-list/{productId}',      'App\Http\Controllers\Workflow\OrderLinesController@priceListForProductJson')->name('orders.lines.json.price-list');
        Route::post('/{orderId}/lines/json/store',                      'App\Http\Controllers\Workflow\OrderLinesController@storeLineJson')->name('orders.lines.json.store');
        Route::put('/{orderId}/lines/json/{id}',                        'App\Http\Controllers\Workflow\OrderLinesController@updateLineJson')->name('orders.lines.json.update');
        Route::delete('/{orderId}/lines/json/{id}',                     'App\Http\Controllers\Workflow\OrderLinesController@destroyLineJson')->name('orders.lines.json.destroy');
        Route::post('/{orderId}/lines/json/{id}/duplicate',             'App\Http\Controllers\Workflow\OrderLinesController@duplicateLineJson')->name('orders.lines.json.duplicate');
        Route::post('/{orderId}/lines/json/{id}/breakdown',             'App\Http\Controllers\Workflow\OrderLinesController@breakDownLineJson')->name('orders.lines.json.breakdown');
        Route::post('/{orderId}/lines/json/{id}/move',                  'App\Http\Controllers\Workflow\OrderLinesController@moveLineJson')->name('orders.lines.json.move');
        Route::post('/{orderId}/lines/json/reorder',                    'App\Http\Controllers\Workflow\OrderLinesController@reorderJson')->name('orders.lines.json.reorder');
        Route::post('/{orderId}/lines/json/price-increase',             'App\Http\Controllers\Workflow\OrderLinesController@priceIncreaseJson')->name('orders.lines.json.price-increase');
        Route::get('/{idOrder}/lines/{id}/detail-edit',                 'App\Http\Controllers\Workflow\OrderLinesController@detailEdit')->name('orders.lines.detail.edit');
        Route::get('/{orderId}/lines/json/{id}/tasks',                  'App\Http\Controllers\Workflow\OrderLinesController@tasksForLineJson')->name('orders.lines.json.tasks');
        Route::patch('/{orderId}/lines/json/{id}/calculated-price',     'App\Http\Controllers\Workflow\OrderLinesController@toggleCalculatedPriceJson')->name('orders.lines.json.calculated-price');
        Route::post('/{orderId}/lines/json/store-delivery',             'App\Http\Controllers\Workflow\OrderLinesController@storeDeliveryJson')->name('orders.lines.json.store-delivery');
        Route::post('/{orderId}/lines/json/store-invoice',              'App\Http\Controllers\Workflow\OrderLinesController@storeInvoiceJson')->name('orders.lines.json.store-invoice');
        Route::post('/{orderId}/lines/json/create-products',            'App\Http\Controllers\Workflow\OrderLinesController@createProductsFromLinesJson')->name('orders.lines.json.create-products');
        Route::post('/{orderId}/lines/json/import-sym',                 'App\Http\Controllers\Workflow\OrderLinesController@importSymJson')->name('orders.lines.json.import-sym');
        //import
        Route::post('/import', 'App\Http\Controllers\Admin\ImportsExportsController@importOrders')->name('orders.import');
        //construction site
        Route::post('/{id}/site', 'App\Http\Controllers\Workflow\OrderSiteController@store')->name('orders.site.store');
        Route::put('/{order}/site/{site}', 'App\Http\Controllers\Workflow\OrderSiteController@update')->name('orders.site.update');
        Route::delete('/{order}/site/{site}', 'App\Http\Controllers\Workflow\OrderSiteController@destroy')->name('orders.site.destroy');
        Route::post('/{order}/site/{site}/implantation', 'App\Http\Controllers\Workflow\OrderSiteController@storeImplantation')->name('orders.site.implantation.store');
        Route::put('/{order}/site/{site}/implantation/{implantation}', 'App\Http\Controllers\Workflow\OrderSiteController@updateImplantation')->name('orders.site.implantation.update');
        Route::delete('/{order}/site/{site}/implantation/{implantation}', 'App\Http\Controllers\Workflow\OrderSiteController@destroyImplantation')->name('orders.site.implantation.destroy');
    });

    Route::group(['prefix' => 'pre-orders', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'check.task.status']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\PreOrdersController@index')->name('pre-orders.index');
        Route::post('/upload', 'App\Http\Controllers\Workflow\PreOrdersController@upload')->name('pre-orders.upload');
        Route::get('/pdf/{preOrder}', 'App\Http\Controllers\Workflow\PreOrdersController@pdf')->name('pre-orders.pdf');
        Route::get('/{preOrder}', 'App\Http\Controllers\Workflow\PreOrdersController@show')->name('pre-orders.show');
        Route::get('/{preOrder}/source-pdf', 'App\Http\Controllers\Workflow\PreOrdersController@sourcePdf')->name('pre-orders.source-pdf');
        Route::post('/{preOrder}/matching', 'App\Http\Controllers\Workflow\PreOrdersController@matchArticles')->name('pre-orders.matching');
        Route::post('/{preOrder}/lines/{line}/accept-matching', 'App\Http\Controllers\Workflow\PreOrdersController@acceptMatching')->name('pre-orders.accept-matching');
        Route::post('/{preOrder}/accept-all-matching', 'App\Http\Controllers\Workflow\PreOrdersController@acceptAllMatching')->name('pre-orders.accept-all-matching');
        Route::post('/{preOrder}/convert', 'App\Http\Controllers\Workflow\PreOrdersController@convert')->name('pre-orders.convert');
    });

    Route::group(['prefix' => 'deliverys', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\DeliverysController@index')->name('deliverys');
        Route::get('/request', 'App\Http\Controllers\Workflow\DeliverysController@request')->name('deliverys-request');
        // JSON API for React DeliverysRequest
        Route::get('/request/company-data', 'App\Http\Controllers\Workflow\DeliverysController@requestCompanyData')->name('deliverys-request.company-data');
        Route::post('/request/store', 'App\Http\Controllers\Workflow\DeliverysController@storeDeliveryNoteApi')->name('deliverys-request.store');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\DeliverysController@update')->name('deliverys.update');
        // JSON API for React DeliverysIndex
        Route::get('/json/list', 'App\Http\Controllers\Workflow\DeliverysController@listJson')->name('deliverys.json.list');
        Route::get('/json/{id}/lines', 'App\Http\Controllers\Workflow\DeliverysController@linesJson')->name('deliverys.json.lines');
        Route::post('{id}/packaging/store/', 'App\Http\Controllers\Workflow\DeliverysController@packagingsStore')->name('deliverys.packagings.store');
        Route::post('{id}/packaging/update/', 'App\Http\Controllers\Workflow\DeliverysController@packagingsUpdate')->name('deliverys.packagings.update');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\DeliverysController@show')->name('deliverys.show');
    });

    Route::group(['prefix' => 'returns', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\\Http\\Controllers\\Workflow\\ReturnsController@index')->name('returns');
        Route::get('/json/list', 'App\\Http\\Controllers\\Workflow\\ReturnsController@listJson')->name('returns.json.list');
        Route::post('/json/store', 'App\\Http\\Controllers\\Workflow\\ReturnsController@storeJson')->name('returns.json.store');
        Route::post('/json/{id}/diagnose', 'App\\Http\\Controllers\\Workflow\\ReturnsController@diagnoseJson')->name('returns.json.diagnose');
        Route::post('/json/{id}/reopen', 'App\\Http\\Controllers\\Workflow\\ReturnsController@reopenJson')->name('returns.json.reopen');
        Route::post('/json/{id}/close', 'App\\Http\\Controllers\\Workflow\\ReturnsController@closeJson')->name('returns.json.close');
        Route::get('/{id}', 'App\\Http\\Controllers\\Workflow\\ReturnsController@show')->name('returns.show');
    });

    Route::group(['prefix' => 'invoices', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\InvoicesController@index')->name('invoices');
        Route::get('/store/delevery/{id}', 'App\Http\Controllers\Workflow\InvoicesController@storeFromDelevery')->name('invoices.store.from.delivery');
        Route::get('/request', 'App\Http\Controllers\Workflow\InvoicesController@request')->name('invoices-request');
        // JSON API for React InvoicesRequest
        Route::get('/request/lines', 'App\Http\Controllers\Workflow\InvoicesController@requestLines')->name('invoices-request.lines');
        Route::post('/request/store', 'App\Http\Controllers\Workflow\InvoicesController@storeInvoiceApi')->name('invoices-request.store');
        Route::post('/request/generate-all', 'App\Http\Controllers\Workflow\InvoicesController@generateInvoicesForCompanyApi')->name('invoices-request.generate-all');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\InvoicesController@update')->name('invoices.update');
        // JSON endpoint for React InvoicesIndex
        Route::get('/json/list', 'App\Http\Controllers\Workflow\InvoicesController@listJson')->name('invoices.json.list');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\InvoicesController@show')->name('invoices.show');
    });

    Route::group(['prefix' => 'credit-notes', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Workflow\CreditNoteController@index')->name('credit-notes');
        Route::post('/store/credit-notes', 'App\Http\Controllers\Workflow\CreditNoteController@CreateCreditNotes')->name('credit-notes.store.from.invoice');
        // JSON API for React CreditNotesIndex
        Route::get('/json/list', 'App\Http\Controllers\Workflow\CreditNoteController@listJson')->name('credit-notes.json.list');
        Route::get('/{id}', 'App\Http\Controllers\Workflow\CreditNoteController@show')->name('credit.notes.show');
        Route::post('/edit/{id}', 'App\Http\Controllers\Workflow\CreditNoteController@update')->name('credit.notes.update');
    });

    Route::group(['prefix' => 'purchases', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'check.task.status']], function () {
        
        Route::get('/request', 'App\Http\Controllers\Purchases\PurchasesRFQController@request')->name('purchases.request');
        Route::get('/request/tasks', 'App\Http\Controllers\Purchases\PurchasesRFQController@requestTasks')->name('purchases.request.tasks');
        Route::post('/request/store', 'App\Http\Controllers\Purchases\PurchasesRFQController@storePurchaseApi')->name('purchases.request.store');
        Route::get('/request/export-csv', 'App\Http\Controllers\Purchases\PurchasesRFQController@exportCsvApi')->name('purchases.request.export-csv');
        Route::get('/quotation', 'App\Http\Controllers\Purchases\PurchasesRFQController@quotation')->name('purchases.quotation'); 
        Route::get('/', 'App\Http\Controllers\Purchases\PurchasesController@purchase')->name('purchases'); 
        
        Route::post('/', 'App\Http\Controllers\Purchases\PurchasesController@storeBankPurchase')->name('purchases.store');

        // JSON endpoints for React PurchasesQuotationIndex / PurchasesQuotationShow
        Route::get('/quotation/api/list',           'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationList')->name('purchases.quotation.api.list');
        Route::get('/quotation/api/kpi',            'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationKpi')->name('purchases.quotation.api.kpi');
        Route::get('/quotation/api/products',       'App\Http\Controllers\Purchases\PurchasesRFQController@apiProducts')->name('purchases.quotation.api.products');
        Route::get('/quotation/api/company-data',   'App\Http\Controllers\Purchases\PurchasesRFQController@apiCompanyAddresses')->name('purchases.quotation.api.company-data');
        Route::get('/quotation/api/{quotation}',    'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationShow')->name('purchases.quotation.api.show');
        Route::patch('/quotation/api/{quotation}',  'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationUpdate')->name('purchases.quotation.api.update');
        Route::patch('/quotation/api/{quotation}/statu', 'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationUpdateStatu')->name('purchases.quotation.api.statu');
        Route::post('/quotation/api/{quotation}/lines',  'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationStoreLine')->name('purchases.quotation.api.lines.store');
        Route::patch('/quotation/api/{quotation}/lines/{line}', 'App\Http\Controllers\Purchases\PurchasesRFQController@apiQuotationUpdateLine')->name('purchases.quotation.api.lines.update');

        // JSON endpoints for React PurchasesIndex
        Route::get('/json/list',                'App\Http\Controllers\Purchases\PurchasesController@listJson')->name('purchases.json.list');
        Route::post('/json/store',              'App\Http\Controllers\Purchases\PurchasesController@storeJson')->name('purchases.json.store');
        Route::get('/json/select-data',         'App\Http\Controllers\Purchases\PurchasesController@selectDataJson')->name('purchases.json.select-data');
        Route::get('/json/addresses/{companyId}','App\Http\Controllers\Purchases\PurchasesController@addressesJson')->name('purchases.json.addresses');
        Route::get('/json/contacts/{companyId}', 'App\Http\Controllers\Purchases\PurchasesController@contactsJson')->name('purchases.json.contacts');
        Route::post('/json/address',            'App\Http\Controllers\Purchases\PurchasesController@storeAddressJson')->name('purchases.json.address.store');
        Route::post('/json/contact',            'App\Http\Controllers\Purchases\PurchasesController@storeContactJson')->name('purchases.json.contact.store');

        Route::get('/waiting/receipt', 'App\Http\Controllers\Purchases\PurchasesReceiptController@waintingReceipt')->name('purchases.wainting.receipt');
        Route::get('/waiting/receipt/json/init',         'App\Http\Controllers\Purchases\PurchasesReceiptController@waitingReceiptInit')->name('purchases.waiting.receipt.json.init');
        Route::post('/waiting/receipt/json/store',       'App\Http\Controllers\Purchases\PurchasesReceiptController@storeReceiptJson')->name('purchases.waiting.receipt.json.store');
        Route::post('/waiting/receipt/json/store-empty', 'App\Http\Controllers\Purchases\PurchasesReceiptController@storeEmptyReceiptJson')->name('purchases.waiting.receipt.json.store.empty');
        Route::get('/receipt', 'App\Http\Controllers\Purchases\PurchasesReceiptController@receipt')->name('purchases.receipt');
        Route::get('/receipt/json/list', 'App\Http\Controllers\Purchases\PurchasesReceiptController@listJson')->name('purchases.receipt.json.list');
        Route::get('/waiting/invoice', 'App\Http\Controllers\Purchases\PurchasesInvoiceController@waintingInvoice')->name('purchases.wainting.invoice');
        Route::get('/waiting/invoice/json/init',   'App\Http\Controllers\Purchases\PurchasesInvoiceController@waitingInvoiceInit')->name('purchases.waiting.invoice.json.init');
        Route::post('/waiting/invoice/json/store', 'App\Http\Controllers\Purchases\PurchasesInvoiceController@storeInvoiceJson')->name('purchases.waiting.invoice.json.store');
        Route::get('/invoice', 'App\Http\Controllers\Purchases\PurchasesInvoiceController@invoice')->name('purchases.invoice');
        Route::get('/invoice/json/list', 'App\Http\Controllers\Purchases\PurchasesInvoiceController@listJson')->name('purchases.invoice.json.list');

        //only for quote request to purchase order
        Route::post('/Purchase/Order/Create/{id}', 'App\Http\Controllers\Purchases\PurchasesController@storePurchaseOrderFromRFQ')->middleware(['auth'])->name('purchases.orders.store');
        
        Route::post('/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesController@updatePurchase')->middleware(['auth'])->name('purchase.update');
        Route::post('/quotation/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesRFQController@updatePurchaseQuotation')->middleware(['auth'])->name('quotation.update');
        Route::get('/quotation/{id}/duplicate', 'App\Http\Controllers\Purchases\PurchasesRFQController@duplicateQuotation')->middleware(['auth'])->name('purchases.quotations.duplicate');
        Route::post('/receipt/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesReceiptController@updatePurchaseReceipt')->middleware(['auth'])->name('receipt.update');
        Route::post('/receipt/control/{id}', 'App\Http\Controllers\Purchases\PurchasesReceiptController@updateReceiptControl')->middleware(['auth'])->name('purchase.receipts.reception_control');
        Route::post('/receipt/line/{purchaseReceiptLine}/inspection', 'App\Http\Controllers\Purchases\PurchasesReceiptController@updateLineInspection')->middleware(['auth'])->name('purchase.receipts.lines.update');
        Route::post('/receipt/{id}/manual-line', 'App\Http\Controllers\Purchases\PurchasesReceiptController@storeManualReceiptLine')->middleware(['auth'])->name('purchase.receipts.lines.manual');
        Route::get('/receipt/{id}/lines/json', 'App\Http\Controllers\Purchases\PurchasesReceiptController@receiptLinesJson')->middleware(['auth'])->name('purchases.receipt.lines.json');
        Route::post('/invoice/edit/{id}', 'App\Http\Controllers\Purchases\PurchasesInvoiceController@updatePurchaseInvoice')->middleware(['auth'])->name('invoice.update');

        // JSON API — React PurchaseLinesPage
        Route::get('/{purchaseId}/lines/json',                    'App\Http\Controllers\Purchases\PurchaseLinesController@linesForPurchaseJson')->name('purchases.lines.json.for-purchase');
        Route::get('/{purchaseId}/lines/json/select-data',        'App\Http\Controllers\Purchases\PurchaseLinesController@selectDataForPurchaseJson')->name('purchases.lines.json.select-data');
        Route::post('/{purchaseId}/lines/json/store',             'App\Http\Controllers\Purchases\PurchaseLinesController@storeLineJson')->name('purchases.lines.json.store');
        Route::put('/{purchaseId}/lines/json/{id}',               'App\Http\Controllers\Purchases\PurchaseLinesController@updateLineJson')->name('purchases.lines.json.update');
        Route::delete('/{purchaseId}/lines/json/{id}',            'App\Http\Controllers\Purchases\PurchaseLinesController@destroyLineJson')->name('purchases.lines.json.destroy');
        Route::post('/{purchaseId}/lines/json/{id}/duplicate',    'App\Http\Controllers\Purchases\PurchaseLinesController@duplicateLineJson')->name('purchases.lines.json.duplicate');
        Route::post('/{purchaseId}/lines/json/reorder',           'App\Http\Controllers\Purchases\PurchaseLinesController@reorderJson')->name('purchases.lines.json.reorder');
        Route::post('/{purchaseId}/lines/json/store-receipt',     'App\Http\Controllers\Purchases\PurchaseLinesController@storeReceiptJson')->name('purchases.lines.json.store-receipt');

        Route::get('/{id}', 'App\Http\Controllers\Purchases\PurchasesController@showPurchase')->middleware(['auth'])->name('purchases.show');
        Route::get('/quotation/{id}', 'App\Http\Controllers\Purchases\PurchasesRFQController@showQuotation')->middleware(['auth'])->name('purchases.quotations.show');
        Route::get('/quotation/group/{group}/compare', 'App\Http\Controllers\Purchases\PurchasesRFQController@compareQuotationGroup')->middleware(['auth'])->name('purchases.quotations.compare');
        Route::get('/receipt/{id}', 'App\Http\Controllers\Purchases\PurchasesReceiptController@showReceipt')->middleware(['auth'])->name('purchase.receipts.show');
        Route::get('/invoice/{id}', 'App\Http\Controllers\Purchases\PurchasesInvoiceController@showInvoice')->middleware(['auth'])->name('purchase.invoices.show');
    });

    Route::group(['prefix' => 'print', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/order/manufacturing/{Document}', 'App\Http\Controllers\PrintController@printOrderManufacturingInstruction')->name('print.manufacturing.instruction');
    });

    Route::group(['prefix' => 'pdf', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
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

    Route::group(['prefix' => 'accounting', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Accounting\AccountingController@index')->middleware(['auth'])->name('accounting');
        Route::get('/payment-conditions', 'App\Http\Controllers\Accounting\AccountingController@paymentConditions')->name('accounting.paymentConditions');
        Route::get('/payment-methods', 'App\Http\Controllers\Accounting\AccountingController@paymentMethods')->name('accounting.paymentMethods');
        Route::get('/vats', 'App\Http\Controllers\Accounting\AccountingController@vats')->name('accounting.vats');
        Route::get('/allocations', 'App\Http\Controllers\Accounting\AccountingController@allocations')->name('accounting.allocations');
        Route::get('/deliveries', 'App\Http\Controllers\Accounting\AccountingController@deliveries')->name('accounting.deliveries');
        Route::get('/assets', 'App\Http\Controllers\Accounting\AccountingController@assets')->name('accounting.assets');
        
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
        });
    });

    Route::group(['prefix' => 'assets', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\\Http\\Controllers\\AssetController@index')->name('assets');
        Route::get('/create', 'App\\Http\\Controllers\\AssetController@create')->name('assets.create');
        Route::post('/create', 'App\\Http\\Controllers\\AssetController@store')->name('assets.store');
        Route::get('/{id}', 'App\\Http\\Controllers\\AssetController@show')->name('assets.show');
        Route::get('/edit/{id}', 'App\\Http\\Controllers\\AssetController@edit')->name('assets.edit');
        Route::post('/edit/{id}', 'App\\Http\\Controllers\\AssetController@update')->name('assets.update');
        Route::delete('/{id}', 'App\\Http\\Controllers\\AssetController@destroy')->name('assets.destroy');
    });

    Route::group(['prefix' => 'gmao', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/dashboard', 'App\\Http\\Controllers\\Maintenance\\DashboardController')->name('gmao.dashboard');
        Route::get('/work-orders', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@index')->name('gmao.work-orders.index');
        Route::get('/work-orders/create', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@create')->name('gmao.work-orders.create');
        Route::post('/work-orders', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@store')->name('gmao.work-orders.store');
        Route::get('/work-orders/{id}', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@show')->name('gmao.work-orders.show');
        Route::get('/work-orders/{id}/edit', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@edit')->name('gmao.work-orders.edit');
        Route::put('/work-orders/{id}', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@update')->name('gmao.work-orders.update');
        Route::delete('/work-orders/{id}', 'App\\Http\\Controllers\\Maintenance\\WorkOrderController@destroy')->name('gmao.work-orders.destroy');
        Route::get('/maintenance-plans', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@index')->name('gmao.maintenance-plans.index');
        Route::get('/maintenance-plans/create', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@create')->name('gmao.maintenance-plans.create');
        Route::post('/maintenance-plans', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@store')->name('gmao.maintenance-plans.store');
        Route::get('/maintenance-plans/{id}', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@show')->name('gmao.maintenance-plans.show');
        Route::get('/maintenance-plans/{id}/edit', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@edit')->name('gmao.maintenance-plans.edit');
        Route::put('/maintenance-plans/{id}', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@update')->name('gmao.maintenance-plans.update');
        Route::delete('/maintenance-plans/{id}', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@destroy')->name('gmao.maintenance-plans.destroy');
        Route::post('/maintenance-plans/{id}/generate-work-order', 'App\\Http\\Controllers\\Maintenance\\MaintenancePlanController@generateWorkOrder')->name('gmao.maintenance-plans.generate-work-order');
    });

    Route::group(['prefix' => 'times', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
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

    Route::group(['prefix' => 'products', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
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

        // JSON API endpoints for React ProductsIndex
        Route::get('/json/list', 'App\Http\Controllers\Products\ProductsController@listJson')->name('products.json.list');
        Route::post('/json/store', 'App\Http\Controllers\Products\ProductsController@storeJson')->name('products.json.store');
        Route::get('/json/select-data', 'App\Http\Controllers\Products\ProductsController@selectDataJson')->name('products.json.select-data');
        Route::get('/{id}/json/history', 'App\Http\Controllers\Products\ProductsController@historyJson')->name('products.json.history');

        Route::group(['prefix' => '{product}/customer-price-list'], function () {
            Route::post('/', 'App\Http\Controllers\Products\CustomerPriceListController@store')->name('products.customer-price-list.store');
            Route::put('/{priceList}', 'App\Http\Controllers\Products\CustomerPriceListController@update')->name('products.customer-price-list.update');
            Route::delete('/{priceList}', 'App\Http\Controllers\Products\CustomerPriceListController@destroy')->name('products.customer-price-list.destroy');
        });

        //import
        Route::post('/import', 'App\Http\Controllers\Admin\ImportsExportsController@importProducts')->name('products.import');

        // Serial numbers routes
        Route::group(['prefix' => 'serial-numbers', 'middleware' => ['permission:stock-lot-serial-management']], function () {
            Route::get('/', 'App\Http\Controllers\Products\SerialNumbersController@index')->name('products.serialNumbers');
            Route::get('/json/list', 'App\Http\Controllers\Products\SerialNumbersController@listJson')->name('products.serialNumbers.json.list');
        });

        Route::group(['prefix' => 'batches', 'middleware' => ['permission:stock-lot-serial-management']], function () {
            Route::get('/', 'App\Http\Controllers\Products\BatchesController@index')->name('products.batches');
        });

        // Stock routes
        Route::group(['prefix' => 'Stock', 'middleware' => ['permission:stock-lot-serial-management']], function () {
            Route::get('/', 'App\Http\Controllers\Products\StockController@index')->name('products.stock');
            Route::post('/create', 'App\Http\Controllers\Products\StockController@store')->name('products.stock.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Products\StockController@update')->name('products.stock.update');
            Route::get('/{id}', 'App\Http\Controllers\Products\StockController@show')->name('products.stock.show');

            // Stock detail
            Route::get('/detail/{id}', 'App\Http\Controllers\Products\StockController@detail')->name('products.stock.detail.show');
            Route::post('/detail/edit/{id}', 'App\Http\Controllers\Products\StockController@detailUpdate')->name('products.stock.detail.update');
            Route::patch('/detail/update/{id}', 'App\Http\Controllers\Products\StockController@detailUpdateJson')->name('products.stock.detail.update.json');
        });

        // Stock Location routes
        Route::group(['prefix' => 'stock/location', 'middleware' => ['permission:stock-lot-serial-management']], function () {
            Route::post('/create', 'App\Http\Controllers\Products\StockLocationController@store')->name('products.stocklocation.store');
            Route::post('/edit/{id}', 'App\Http\Controllers\Products\StockLocationController@update')->name('products.stocklocation.update');
            Route::get('/{id}', 'App\Http\Controllers\Products\StockLocationController@show')->name('products.stocklocation.show');
        });

        // Stock Location Products routes
        Route::group(['prefix' => 'stock/location/product', 'middleware' => ['permission:stock-lot-serial-management']], function () {
            Route::post('/create', 'App\Http\Controllers\Products\StockLocationProductsController@store')->name('products.stockline.store');
            Route::post('/create/internal-order', 'App\Http\Controllers\Products\StockLocationProductsController@storeFromInternalOrder')->name('products.stockline.store.from.internal.order');
            Route::post('/create/purchase-order', 'App\Http\Controllers\Products\StockLocationProductsController@storeFromPurchaseOrder')->name('products.stockline.store.from.purchase.order');
            Route::post('/edit/{id}', 'App\Http\Controllers\Products\StockLocationProductsController@update')->name('products.stockline.update');
            Route::get('/{id}', 'App\Http\Controllers\Products\StockLocationProductsController@show')->name('products.stockline.show');
            Route::post('/entry', 'App\Http\Controllers\Products\StockLocationProductsController@entry')->name('products.stockline.manual.entry');
            Route::post('/entry/internal-order', 'App\Http\Controllers\Products\StockLocationProductsController@entryFromInternalOrder')->name('products.stockline.entry.from.internal.order');
            Route::post('/entry/purchase-order', 'App\Http\Controllers\Products\StockLocationProductsController@entryFromPurchaseOrder')->name('products.stockline.entry.from.purchase.order');
            Route::post('/sorting', 'App\Http\Controllers\Products\StockLocationProductsController@sorting')->name('products.stockline.sorting');
            Route::post('/transfer', 'App\Http\Controllers\Products\StockLocationProductsController@transfer')->name('products.stockline.transfer');
        });
        
        Route::get('/{id}', 'App\Http\Controllers\Products\ProductsController@show')->name('products.show');
    });

    Route::group(['prefix' => 'task', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::put('/sync', 'App\Http\Controllers\Planning\TaskController@sync')->name('task.sync');
        Route::get('/{id_type}/{id_page}/show/{id_line}', 'App\Http\Controllers\Planning\TaskController@manage')->name('task.manage');
        Route::get('/{id_type}/{id_page}/delete/{id_task}', 'App\Http\Controllers\Planning\TaskController@delete')->name('task.delete');
        Route::post('/create/{id}', 'App\Http\Controllers\Planning\TaskController@store')->name('task.store');
        Route::post('/update/{id}', 'App\Http\Controllers\Planning\TaskController@update')->name('task.update');

        // JSON API — React TaskManagePage
        Route::get('/{id_type}/{id_page}/json/{id_line}',                              'App\Http\Controllers\Planning\TaskManageApiController@initialDataJson')->name('task.manage.json.initial');
        Route::get('/{id_type}/{id_page}/json/{id_line}/select-data',                  'App\Http\Controllers\Planning\TaskManageApiController@selectDataJson')->name('task.manage.json.select-data');
        Route::post('/{id_type}/{id_page}/json/{id_line}/task',                        'App\Http\Controllers\Planning\TaskManageApiController@storeTaskJson')->name('task.manage.json.task.store');
        Route::put('/{id_type}/{id_page}/json/task/{id}',                              'App\Http\Controllers\Planning\TaskManageApiController@updateTaskJson')->name('task.manage.json.task.update');
        Route::delete('/{id_type}/{id_page}/json/task/{id}',                           'App\Http\Controllers\Planning\TaskManageApiController@destroyTaskJson')->name('task.manage.json.task.destroy');
        Route::post('/{id_type}/{id_page}/json/task/{id}/duplicate',                   'App\Http\Controllers\Planning\TaskManageApiController@duplicateTaskJson')->name('task.manage.json.task.duplicate');
        Route::post('/{id_type}/{id_page}/json/{id_line}/sub-assembly',                'App\Http\Controllers\Planning\TaskManageApiController@storeSubAssemblyJson')->name('task.manage.json.subassembly.store');
        Route::put('/{id_type}/{id_page}/json/sub-assembly/{id}',                      'App\Http\Controllers\Planning\TaskManageApiController@updateSubAssemblyJson')->name('task.manage.json.subassembly.update');
        Route::delete('/{id_type}/{id_page}/json/sub-assembly/{id}',                   'App\Http\Controllers\Planning\TaskManageApiController@destroySubAssemblyJson')->name('task.manage.json.subassembly.destroy');
        Route::post('/{id_type}/{id_page}/json/sub-assembly/{id}/duplicate',           'App\Http\Controllers\Planning\TaskManageApiController@duplicateSubAssemblyJson')->name('task.manage.json.subassembly.duplicate');
        Route::post('/{id_type}/{id_page}/json/{id_line}/import-csv',                  'App\Http\Controllers\Planning\TaskManageApiController@importCsvJson')->name('task.manage.json.import-csv');
        Route::post('/{id_type}/{id_page}/json/{id_line}/apply-nomenclature/{tpl_id}', 'App\Http\Controllers\Planning\TaskManageApiController@applyNomenclatureJson')->name('task.manage.json.apply-nomenclature');
    });


    Route::group(['prefix' => 'production', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'check.task.status']], function () {
        Route::get('/Task/Statu/Id/{id}', 'App\Http\Controllers\Planning\TaskController@statu')->name('production.task.statu.id');
        Route::get('/Task/Statu', 'App\Http\Controllers\Planning\TaskController@statu')->name('production.task.statu');
        Route::get('/Task', 'App\Http\Controllers\Planning\TaskController@index')->name('production.task');
        Route::get('/Task/gtd', 'App\Http\Controllers\Planning\TaskController@gtd')->name('production.task.gtd');
        Route::get('/kanban', 'App\Http\Controllers\Planning\TaskController@kanban')->name('production.kanban');
        Route::get('/calendar/orders', 'App\Http\Controllers\Planning\CalendarController@calendarOders')->name('production.calendar.orders');
        Route::get('/calendar/tasks', 'App\Http\Controllers\Planning\CalendarController@calendarTasks')->name('production.calendar.tasks');
        Route::get('/gantt', 'App\Http\Controllers\Planning\GanttController@index')->name('production.gantt');
        Route::get('/gantt/order/{order_id}', 'App\Http\Controllers\Planning\GanttController@getTasksByOrder')->name('production.gantt.order');
        
        Route::get('/load-planning', 'App\Http\Controllers\Planning\PlanningController@index')->name('production.load.planning');
        Route::get('/load-planning/data', 'App\Http\Controllers\Planning\PlanningController@dataJson')->name('production.load.planning.data');
        Route::get('/load-planning/calculation-status', 'App\Http\Controllers\Planning\PlanningController@calculationStatus')->name('production.load.planning.calculation.status');
        Route::post('/load-planning/calculate-dates', 'App\Http\Controllers\Planning\PlanningController@calculateDates')->name('production.load.planning.calculate.dates');
        Route::post('/load-planning/calculate-resources', 'App\Http\Controllers\Planning\PlanningController@calculateResources')->name('production.load.planning.calculate.resources');

        // JSON API — TaskStatuApp (React)
        Route::get('/Task/Statu/Api/{id}',               'App\Http\Controllers\Api\TaskStatuController@show')->name('production.task.statu.api.show');
        Route::post('/Task/Statu/Api/{id}/start',        'App\Http\Controllers\Api\TaskStatuController@start')->name('production.task.statu.api.start');
        Route::post('/Task/Statu/Api/{id}/pause',        'App\Http\Controllers\Api\TaskStatuController@pause')->name('production.task.statu.api.pause');
        Route::post('/Task/Statu/Api/{id}/finish',       'App\Http\Controllers\Api\TaskStatuController@finish')->name('production.task.statu.api.finish');
        Route::post('/Task/Statu/Api/{id}/good-qty',     'App\Http\Controllers\Api\TaskStatuController@goodQty')->name('production.task.statu.api.good_qty');
        Route::post('/Task/Statu/Api/{id}/good-qty-stock','App\Http\Controllers\Api\TaskStatuController@goodQtyStock')->name('production.task.statu.api.good_qty_stock');
        Route::post('/Task/Statu/Api/{id}/bad-qty',      'App\Http\Controllers\Api\TaskStatuController@badQty')->name('production.task.statu.api.bad_qty');
        Route::put('/Task/Statu/Api/{id}/date',          'App\Http\Controllers\Api\TaskStatuController@updateDate')->name('production.task.statu.api.date');
        Route::put('/Task/Statu/Api/{id}/resource',      'App\Http\Controllers\Api\TaskStatuController@updateResource')->name('production.task.statu.api.resource');
        Route::post('/Task/Statu/Api/{id}/nc',           'App\Http\Controllers\Api\TaskStatuController@createNc')->name('production.task.statu.api.nc');
    });

    Route::group(['prefix' => 'nesting', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Planning\NestingController@index')->name('nesting.index');
        Route::get('/document', 'App\Http\Controllers\Planning\NestingController@document')->name('nesting.document');
        Route::get('/parts', 'App\Http\Controllers\Planning\NestingController@parts')->name('nesting.parts');
    });

    Route::group(['prefix' => 'admin'], function () {
        
        Route::post('/factory/announcement/create', 'App\Http\Controllers\Admin\FactoryController@storeAnnouncement')->middleware(['auth'])->name('admin.factory.announcement.create');
        Route::get('/factory/announcement/delete/{id}', 'App\Http\Controllers\Admin\FactoryController@deleteAnnouncement')->middleware(['auth'])->name('admin.factory.announcement.delete');
        Route::post('/factory/update', 'App\Http\Controllers\Admin\FactoryController@update')->middleware(['auth'])->name('admin.factory.update');
        Route::get('/factory', 'App\Http\Controllers\Admin\FactoryController@index')->middleware(['auth'])->name('admin.factory');

        Route::get('/roles-permissions', 'App\Http\Controllers\Admin\RoleController@index')->middleware(['auth'])->name('admin.roles.permissions');
        Route::post('/factory/role/store', 'App\Http\Controllers\Admin\RoleController@store')->middleware(['auth'])->name('admin.factory.role.store');
        Route::post('/factory/role/update/{id}', 'App\Http\Controllers\Admin\RoleController@update')->middleware(['auth'])->name('admin.factory.role.update');
        Route::get('/factory/role/delete/{role}', 'App\Http\Controllers\Admin\RoleController@destroy')->middleware(['auth'])->name('admin.factory.role.destroy');
        Route::post('/factory/permissions/store', 'App\Http\Controllers\Admin\PermissionController@store')->middleware(['auth'])->name('admin.factory.permissions.store');
        Route::get('/factory/permissions/delete/{permission}', 'App\Http\Controllers\Admin\PermissionController@destroy')->middleware(['auth'])->name('admin.factory.permissions.destroy');
        Route::post('/factory/role/permissions/store', 'App\Http\Controllers\Admin\RoleController@RolePemissionStore')->middleware(['auth'])->name('admin.factory.rolepermissions.store');
        Route::get('/integrations/n2p', [\App\Http\Controllers\Integrations\N2PSettingsController::class, 'edit'])->middleware(['auth'])->name('admin.integrations.n2p');
        Route::put('/integrations/n2p', [\App\Http\Controllers\Integrations\N2PSettingsController::class, 'update'])->middleware(['auth'])->name('admin.integrations.n2p.update');

        Route::middleware(['auth'])->prefix('integrations/qonto')->name('admin.integrations.qonto.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'index'])->name('index');
            Route::get('/connect', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'connect'])->name('connect');
            Route::post('/sync', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'sync'])->name('sync');
            Route::post('/settings', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'settings'])->name('settings');
            Route::post('/disconnect', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'disconnect'])->name('disconnect');
            Route::post('/reviews/{reviewId}/resolve', [\App\Http\Controllers\Integrations\QontoSettingsController::class, 'resolve'])->name('resolve');
        });

        Route::post('/factory/custom-field/store', 'App\Http\Controllers\Admin\FactoryController@storeCustomField')->middleware(['auth'])->name('admin.factory.custom.field.store');
        Route::post('/factory/custom-field-value/storeOrUpdate/{id}/{type}', 'App\Http\Controllers\Admin\FactoryController@storeOrUpdateCustomField')->middleware(['auth'])->name('admin.factory.custom.field.value.store.update');
        
        Route::post('/document-code-template/store', 'App\Http\Controllers\Admin\DocumentCodeTemplateController@store')->middleware(['auth'])->name('admin.document.code.template.store');
        Route::post('/document-code-template/update/{id}', 'App\Http\Controllers\Admin\DocumentCodeTemplateController@update')->middleware(['auth'])->name('admin.document.code.template.update');
        
        Route::get('/estimated-budgets-settings', 'App\Http\Controllers\Admin\FactoryController@estimatedBudgetsSettingView')->middleware(['auth'])->name('admin.estimated.budgets.settings');
        Route::get('/estimated-budgets/json/list',         'App\Http\Controllers\Admin\FactoryController@estimatedBudgetsJsonList')->middleware(['auth'])->name('admin.estimated.budgets.json.list');
        Route::post('/estimated-budgets/json/store',       'App\Http\Controllers\Admin\FactoryController@estimatedBudgetsJsonStore')->middleware(['auth'])->name('admin.estimated.budgets.json.store');
        Route::put('/estimated-budgets/json/update/{id}',  'App\Http\Controllers\Admin\FactoryController@estimatedBudgetsJsonUpdate')->middleware(['auth'])->name('admin.estimated.budgets.json.update');
        Route::delete('/estimated-budgets/json/{id}',      'App\Http\Controllers\Admin\FactoryController@estimatedBudgetsJsonDestroy')->middleware(['auth'])->name('admin.estimated.budgets.json.destroy');

        Route::get('/kanban-settings',                    'App\Http\Controllers\Admin\FactoryController@kanbanSettingView')->middleware(['auth'])->name('admin.kanban.settings');
        Route::get('/kanban-settings/json/list',           'App\Http\Controllers\Admin\FactoryController@kanbanSettingJsonList')->middleware(['auth'])->name('admin.kanban.settings.json.list');
        Route::post('/kanban-settings/json/store',         'App\Http\Controllers\Admin\FactoryController@kanbanSettingJsonStore')->middleware(['auth'])->name('admin.kanban.settings.json.store');
        Route::patch('/kanban-settings/json/{id}/up',      'App\Http\Controllers\Admin\FactoryController@kanbanSettingJsonUp')->middleware(['auth'])->name('admin.kanban.settings.json.up');
        Route::patch('/kanban-settings/json/{id}/down',    'App\Http\Controllers\Admin\FactoryController@kanbanSettingJsonDown')->middleware(['auth'])->name('admin.kanban.settings.json.down');
        Route::delete('/kanban-settings/json/{id}',        'App\Http\Controllers\Admin\FactoryController@kanbanSettingJsonDestroy')->middleware(['auth'])->name('admin.kanban.settings.json.destroy');

        Route::get('/imports-exports', 'App\Http\Controllers\Admin\ImportsExportsController@index')->middleware(['auth'])->name('admin.imports.exports');
        Route::get('/invoice-export/json/list',        'App\Http\Controllers\Admin\ImportsExportsController@invoiceExportJsonList')->middleware(['auth'])->name('admin.invoice.export.json.list');
        Route::post('/invoice-export/export/{ext}',    'App\Http\Controllers\Admin\ImportsExportsController@invoiceExport')->middleware(['auth'])->name('admin.invoice.export');
        Route::get('/fec-export/json/list',            'App\Http\Controllers\Admin\ImportsExportsController@fecExportJsonList')->middleware(['auth'])->name('admin.fec.export.json.list');
        Route::post('/fec-export/export/{ext}',        'App\Http\Controllers\Admin\ImportsExportsController@fecExport')->middleware(['auth'])->name('admin.fec.export');

        Route::get('/logs-view', 'App\Http\Controllers\Admin\FactoryController@logsView')->middleware(['auth'])->name('admin.logs.view');
    
        Route::get('/emails/templates', 'App\Http\Controllers\Admin\EmailTemplateController@index')->name('admin.emails.templates.index');
        Route::post('/emails/templates/store', 'App\Http\Controllers\Admin\EmailTemplateController@store')->name('admin.emails.templates.store');
        Route::post('/emails/templates/update/{emailTemplate}', 'App\Http\Controllers\Admin\EmailTemplateController@update')->name('admin.emails.templates.update');
        Route::delete('/emails/templates/delete/{emailTemplate}', 'App\Http\Controllers\Admin\EmailTemplateController@destroy')->name('admin.emails.templates.delete');

    });

    Route::group(['prefix' => 'human-resources', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Admin\HumanResourcesController@index')->name('human.resources');
        Route::get('/index', 'App\Http\Controllers\Admin\HumanResourcesController@index')->name('human.resources.index');
        Route::get('/attendance', 'App\Http\Controllers\Admin\HumanResourcesController@attendanceReport')->name('human.resources.attendance');

        // Index User route
        Route::get('/users', 'App\Http\Controllers\Admin\HumanResourcesController@indexUsers')->name('human.resources.index.users');

        // Show User
        Route::get('/user/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@ShowUser')->name('human.resources.show.user');
    
        // Update User
        Route::match(['post', 'put'], '/update/user/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@UpdateUser')->name('human.resources.update.user');

        //lock User
        Route::post('/lock/user/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@LockUser')->name('human.resources.lock.user');

        // Employment Contract
        Route::group(['prefix' => 'contract'], function () {
            // Create Employment Contract
            Route::post('/create', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserEmploymentContract')->name('human.resources.create.contract');
            Route::post('/store', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserEmploymentContract')->name('human.resources.store.user.contract');
    
            // Update Employment Contract
            Route::post('/update', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserEmploymentContract')->name('human.resources.update.contract');
            Route::put('/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserEmploymentContract')->name('human.resources.update.user.contract');
        });

        // Employment Contract
        Route::group(['prefix' => 'expense'], function () {
            // Create Expense category
            Route::post('/create/category', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserExpenseCategorie')->name('human.resources.create.expense.category');
            Route::post('/category', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserExpenseCategorie')->name('human.resources.store.user.expense.category');
            // Update Expense category
            Route::post('/update/category', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserExpenseCategorie')->name('human.resources.update.expense.category');
            Route::put('/category/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserExpenseCategorie')->name('human.resources.update.user.expense.category');
            // Create Expense Report User
            Route::post('/create/report', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserExpenseReport')->name('human.resources.create.expense.report');
            Route::post('/report', 'App\Http\Controllers\Admin\HumanResourcesController@storeUserExpenseReport')->name('human.resources.store.user.expense.report');
            // Update Expense Report User
            Route::post('/update/report', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserExpenseReport')->name('human.resources.update.expense.report');
            Route::put('/report/{id}', 'App\Http\Controllers\Admin\HumanResourcesController@updateUserExpenseReport')->name('human.resources.update.user.expense.report');
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

    Route::group(['prefix' => 'quality', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Quality\QualityController@index')->name('quality');
        Route::get('/inspection-projects', 'App\Http\Controllers\Inspection\InspectionProjectController@indexView')->name('quality.inspection.projects');

        // Routes for Process Diagrams
        Route::get('/process-diagrams', 'App\Http\Controllers\Quality\ProcessDiagramController@indexView')->name('quality.process-diagrams');
        Route::group(['prefix' => 'process-diagrams'], function () {
            Route::get('/list',    'App\Http\Controllers\Quality\ProcessDiagramController@index')->name('quality.process-diagrams.index');
            Route::post('/list',   'App\Http\Controllers\Quality\ProcessDiagramController@store')->name('quality.process-diagrams.store');
            Route::get('/{id}',    'App\Http\Controllers\Quality\ProcessDiagramController@show')->name('quality.process-diagrams.show');
            Route::put('/{id}',    'App\Http\Controllers\Quality\ProcessDiagramController@update')->name('quality.process-diagrams.update');
            Route::delete('/{id}', 'App\Http\Controllers\Quality\ProcessDiagramController@delete')->name('quality.process-diagrams.delete');
        });

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
            Route::post('/close/{id}', 'App\Http\Controllers\Quality\QualityNonConformityController@closeResolutionDate')->name('quality.nonConformitie.close.resolutionDate');
            Route::post('/reopen/{id}', 'App\Http\Controllers\Quality\QualityNonConformityController@reopenResolutionDate')->name('quality.nonConformitie.reopen.resolutionDate');
            // React API routes
            Route::get('/api/list', 'App\Http\Controllers\Quality\QualityNonConformityController@apiList')->name('quality.nonConformitie.api.list');
            Route::post('/api/store', 'App\Http\Controllers\Quality\QualityNonConformityController@apiStore')->name('quality.nonConformitie.api.store');
            Route::post('/api/{id}/update', 'App\Http\Controllers\Quality\QualityNonConformityController@apiUpdate')->name('quality.nonConformitie.api.update');
            Route::post('/api/{id}/close', 'App\Http\Controllers\Quality\QualityNonConformityController@apiClose')->name('quality.nonConformitie.api.close');
            Route::post('/api/{id}/reopen', 'App\Http\Controllers\Quality\QualityNonConformityController@apiReopen')->name('quality.nonConformitie.api.reopen');
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

        // JSON API routes for React component
        Route::prefix('json')->group(function () {
            Route::get('/devices',              'App\Http\Controllers\Quality\QualityControlDeviceController@jsonList')->name('quality.json.devices');
            Route::post('/devices/create',      'App\Http\Controllers\Quality\QualityControlDeviceController@jsonStore')->name('quality.json.devices.create');
            Route::post('/devices/update/{id}', 'App\Http\Controllers\Quality\QualityControlDeviceController@jsonUpdate')->name('quality.json.devices.update');
            Route::get('/select-data',          'App\Http\Controllers\Quality\QualityController@jsonSelectData')->name('quality.json.select-data');
            Route::post('/failure/create',      'App\Http\Controllers\Quality\QualityFailureController@jsonStore')->name('quality.json.failure.create');
            Route::post('/failure/update/{id}', 'App\Http\Controllers\Quality\QualityFailureController@jsonUpdate')->name('quality.json.failure.update');
            Route::post('/cause/create',        'App\Http\Controllers\Quality\QualityCauseController@jsonStore')->name('quality.json.cause.create');
            Route::post('/cause/update/{id}',   'App\Http\Controllers\Quality\QualityCauseController@jsonUpdate')->name('quality.json.cause.update');
            Route::post('/correction/create',      'App\Http\Controllers\Quality\QualityCorrectionController@jsonStore')->name('quality.json.correction.create');
            Route::post('/correction/update/{id}', 'App\Http\Controllers\Quality\QualityCorrectionController@jsonUpdate')->name('quality.json.correction.update');
        });
    });

    Route::group(['prefix' => 'inspection-projects', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', 'App\Http\Controllers\Inspection\InspectionProjectController@index')->name('inspection.projects.index');
        Route::post('/', 'App\Http\Controllers\Inspection\InspectionProjectController@store')->name('inspection.projects.store');
        Route::get('/{id}', 'App\Http\Controllers\Inspection\InspectionProjectController@show')->name('inspection.projects.show');
        Route::put('/{id}', 'App\Http\Controllers\Inspection\InspectionProjectController@update')->name('inspection.projects.update');
        Route::post('/{id}/documents', 'App\Http\Controllers\Inspection\InspectionDocumentController@store')->name('inspection.projects.documents.store');
        Route::post('/documents/{id}/submit', 'App\Http\Controllers\Inspection\InspectionDocumentController@submit')->name('inspection.documents.submit');
        Route::post('/documents/{id}/approve', 'App\Http\Controllers\Inspection\InspectionDocumentController@approve')->name('inspection.documents.approve');
        Route::post('/documents/{id}/obsolete', 'App\Http\Controllers\Inspection\InspectionDocumentController@obsolete')->name('inspection.documents.obsolete');
        Route::post('/{id}/control-points', 'App\Http\Controllers\Inspection\InspectionControlPointController@store')->name('inspection.projects.points.store');
        Route::put('/control-points/{id}', 'App\Http\Controllers\Inspection\InspectionControlPointController@update')->name('inspection.points.update');
        Route::delete('/control-points/{id}', 'App\Http\Controllers\Inspection\InspectionControlPointController@destroy')->name('inspection.points.destroy');
        Route::post('/{id}/sessions', 'App\Http\Controllers\Inspection\InspectionMeasureSessionController@store')->name('inspection.projects.sessions.store');
        Route::post('/sessions/{id}/submit', 'App\Http\Controllers\Inspection\InspectionMeasureSessionController@submit')->name('inspection.sessions.submit');
        Route::post('/sessions/{id}/close', 'App\Http\Controllers\Inspection\InspectionMeasureSessionController@close')->name('inspection.sessions.close');
        Route::get('/{id}/export/pdf', 'App\Http\Controllers\Inspection\InspectionProjectController@exportPdf')->name('inspection.projects.export.pdf');
        Route::get('/{id}/export/xlsx', 'App\Http\Controllers\Inspection\InspectionProjectController@exportXlsx')->name('inspection.projects.export.xlsx');
    });

    Route::group(['prefix' => 'methods', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        // Index route
        Route::get('/', 'App\Http\Controllers\Methods\MethodsController@index')->name('methods');
    
        // Routes for Unit
        Route::group(['prefix' => 'unit'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\UnitsController@index')->name('methods.unit');
            Route::post('/create', 'App\Http\Controllers\Methods\UnitsController@store')->name('methods.unit.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\UnitsController@update')->name('methods.unit.update');
        });
    
        // Routes for Family
        Route::group(['prefix' => 'family'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\FamiliesController@index')->name('methods.family');
            Route::post('/create', 'App\Http\Controllers\Methods\FamiliesController@store')->name('methods.family.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\FamiliesController@update')->name('methods.family.update');
        });
    
        // Routes for Service
        Route::group(['prefix' => 'service'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\ServicesController@index')->name('methods.service');
            Route::post('/create', 'App\Http\Controllers\Methods\ServicesController@store')->name('methods.service.create');
            Route::get('/show/{id}', 'App\Http\Controllers\Methods\ServicesController@show')->name('methods.service.show');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\ServicesController@update')->name('methods.service.update');
            Route::post('/edit/{id}/image', 'App\Http\Controllers\Methods\ServicesController@StoreImage')->name('methods.service.update.picture');
        });
    
        // Routes for Section
        Route::group(['prefix' => 'section'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\SectionsController@index')->name('methods.section');
            Route::post('/create', 'App\Http\Controllers\Methods\SectionsController@store')->name('methods.section.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\SectionsController@update')->name('methods.section.update');
        });

        Route::get('/overview', 'App\Http\Controllers\Methods\MethodsController@overview')->name('methods.overview');
    
        // Routes for Ressources
        Route::group(['prefix' => 'ressources'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\RessourcesController@index')->name('methods.ressource');
            Route::post('/create', 'App\Http\Controllers\Methods\RessourcesController@store')->name('methods.ressource.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\RessourcesController@update')->name('methods.ressource.update');
            Route::post('/edit/{id}/image', 'App\Http\Controllers\Methods\RessourcesController@StoreImage')->name('methods.ressource.update.picture');
        });
    
        // Routes for Location
        Route::group(['prefix' => 'location'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\LocationsController@index')->name('methods.location');
            Route::post('/create', 'App\Http\Controllers\Methods\LocationsController@store')->name('methods.location.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\LocationsController@update')->name('methods.location.update');
        });
    
        // Routes for Tool
        Route::group(['prefix' => 'tool'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\ToolsController@index')->name('methods.tool');
            Route::post('/create', 'App\Http\Controllers\Methods\ToolsController@store')->name('methods.tool.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\ToolsController@update')->name('methods.tool.update');
            Route::post('/edit/{id}/image', 'App\Http\Controllers\Methods\ToolsController@StoreImage')->name('methods.tool.update.picture');
        });

        // Routes for Standard Nomenclature
        Route::group(['prefix' => 'standard-nomenclature'], function () {
            Route::get('/', 'App\Http\Controllers\Methods\StandardNomenclatureController@index')->name('methods.standard.nomenclature');
            Route::post('/create', 'App\Http\Controllers\Methods\StandardNomenclatureController@store')->name('methods.standard.nomenclature.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\Methods\StandardNomenclatureController@update')->name('methods.standard.nomenclature.update');
        });
    });

    Route::group(['prefix' => 'osh', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::group(['prefix' => 'conformities'], function () {
            Route::get('/', 'App\Http\Controllers\OSH\ConformitiesController@index')->name('osh.conformities');
            Route::post('/create', 'App\Http\Controllers\OSH\ConformitiesController@store')->name('osh.conformities.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\OSH\ConformitiesController@update')->name('osh.conformities.update');
        });

        Route::group(['prefix' => 'incidents'], function () {
            Route::get('/', 'App\Http\Controllers\OSH\IncidentsController@index')->name('osh.incidents');
            Route::post('/create', 'App\Http\Controllers\OSH\IncidentsController@store')->name('osh.incidents.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\OSH\IncidentsController@update')->name('osh.incidents.update');
        });

        Route::group(['prefix' => 'risks'], function () {
            Route::get('/', 'App\Http\Controllers\OSH\RisksController@index')->name('osh.risks');
            Route::post('/create', 'App\Http\Controllers\OSH\RisksController@store')->name('osh.risks.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\OSH\RisksController@update')->name('osh.risks.update');
        });

        Route::group(['prefix' => 'trainings'], function () {
            Route::get('/', 'App\Http\Controllers\OSH\TrainingsController@index')->name('osh.training');
            Route::post('/create', 'App\Http\Controllers\OSH\TrainingsController@store')->name('osh.training.create');
            Route::post('/edit/{id}', 'App\Http\Controllers\OSH\TrainingsController@update')->name('osh.training.update');
        });
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/get', 'App\Http\Controllers\NotificationsController@getNotificationsData')->middleware(['auth'])->name('notifications.get');
        Route::get('/show', 'App\Http\Controllers\UsersController@profile')->middleware(['auth'])->name('notifications.show');
        Route::post('/show', 'App\Http\Controllers\UsersController@settingNotification')->middleware(['auth'])->name('notifications.setting');
    });

    Route::get('/{type}/{id}/email', 'App\Http\Controllers\EmailController@create')->name('email.create');
    Route::post('/{type}/{id}/email', 'App\Http\Controllers\EmailController@send')->name('email.send');

    Route::post('upload-file', 'App\Http\Controllers\FileUpload@fileUpload')->middleware(['auth'])->name('file.store');
    Route::post('upload-photo', 'App\Http\Controllers\FileUpload@photoUpload')->middleware(['auth'])->name('photo.store');

    Route::get('/licence', function () {return view('licence');})->middleware(['auth'])->name('licence');

    Route::get('/rgpd-policy', function () {return view('rgpd-policy');})->middleware(['auth'])->name('rgpd.policy');

    Route::get('/iframe-mode', function () {return view('iframe-mode');})->middleware(['auth'])->name('iframe.mode');

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'App\Http\Controllers\UsersController@List')->middleware(['auth'])->name('users');
        Route::get('/Profile/{id}', 'App\Http\Controllers\UsersController@profile')->middleware(['auth'])->name('user.profile');
        Route::get('/Profile/Update', 'App\Http\Controllers\UsersController@update')->middleware(['auth'])->name('user.profile.update');

        // JSON API — React UserProfilePage
        Route::prefix('profile/json')->middleware('auth')->group(function () {
            Route::put('/account',                    'App\Http\Controllers\UserProfileApiController@updateAccount')->name('profile.json.account.update');
            Route::put('/information',                'App\Http\Controllers\UserProfileApiController@updateInformation')->name('profile.json.information.update');
            Route::post('/notifications/{id}/read',   'App\Http\Controllers\UserProfileApiController@readNotification')->name('profile.json.notification.read');
            Route::post('/notifications/read-all',    'App\Http\Controllers\UserProfileApiController@readAllNotifications')->name('profile.json.notification.read-all');
            Route::get('/notifications/history',      'App\Http\Controllers\UserProfileApiController@getNotificationHistory')->name('profile.json.notification.history');
            Route::put('/auto-email-reports',         'App\Http\Controllers\UserProfileApiController@saveAutoEmailReports')->name('profile.json.auto-email-reports.save');
        });
    });

    Route::match(
        ['get', 'post'],
        '/navbar/search',
        'App\Http\Controllers\SearchController@showNavbarSearchResults'

    );
    Route::get('/production-trace/{serialNumber}', [ProductionTraceController::class, 'show'])->name('production.trace.show');

    Route::get('/production-trace/{serial}', 'App\Http\Controllers\ProductionTraceController@show')
        ->middleware(['auth', 'verified', 'has.role', 'check.factory'])
        ->name('production.trace');

    Route::group(['prefix' => 'energy-consumptions', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory']], function () {
        Route::get('/', [EnergyConsumptionController::class, 'index'])->name('energy-consumptions.index');
        Route::post('/', [EnergyConsumptionController::class, 'store'])->name('energy-consumptions.store');
        Route::get('/{id}', [EnergyConsumptionController::class, 'show'])->name('energy-consumptions.show');
    });


    require __DIR__.'/auth.php';

    Route::get('/home', 'App\Http\Controllers\HomeController@index')->middleware(['auth'])->name('home');

    Livewire::setUpdateRoute(function ($handle) {
        return Route::post('/livewire/update', $handle);
    });

});

    // ─── AUDITS INTERNES ISO 9001 ──────────────────────────────────────────────
    Route::group(['prefix' => 'audit', 'middleware' => ['auth', 'verified', 'has.role', 'check.factory', 'permission:audit-menu']], function () {
        Route::get('/', 'App\Http\Controllers\Audit\AuditController@index')->name('audit.index');

        // Plans
        Route::get('/api/plans',        'App\Http\Controllers\Audit\AuditController@plansIndex')->name('audit.api.plans.index');
        Route::post('/api/plans',       'App\Http\Controllers\Audit\AuditController@plansStore')->name('audit.api.plans.store');
        Route::put('/api/plans/{id}',   'App\Http\Controllers\Audit\AuditController@plansUpdate')->name('audit.api.plans.update');
        Route::delete('/api/plans/{id}','App\Http\Controllers\Audit\AuditController@plansDestroy')->name('audit.api.plans.destroy');

        // Processes
        Route::get('/api/processes',         'App\Http\Controllers\Audit\AuditController@processesIndex')->name('audit.api.processes.index');
        Route::post('/api/processes',        'App\Http\Controllers\Audit\AuditController@processesStore')->name('audit.api.processes.store');
        Route::put('/api/processes/{id}',    'App\Http\Controllers\Audit\AuditController@processesUpdate')->name('audit.api.processes.update');
        Route::delete('/api/processes/{id}', 'App\Http\Controllers\Audit\AuditController@processesDestroy')->name('audit.api.processes.destroy');

        // Schedules
        Route::get('/api/schedules',         'App\Http\Controllers\Audit\AuditController@schedulesIndex')->name('audit.api.schedules.index');
        Route::post('/api/schedules',        'App\Http\Controllers\Audit\AuditController@schedulesStore')->name('audit.api.schedules.store');
        Route::put('/api/schedules/{id}',    'App\Http\Controllers\Audit\AuditController@schedulesUpdate')->name('audit.api.schedules.update');
        Route::delete('/api/schedules/{id}', 'App\Http\Controllers\Audit\AuditController@schedulesDestroy')->name('audit.api.schedules.destroy');

        // Checklists (read-only — seeded from ISO 9001)
        Route::get('/api/checklists', 'App\Http\Controllers\Audit\AuditController@checklistsIndex')->name('audit.api.checklists.index');

        // Executions
        Route::post('/api/executions',           'App\Http\Controllers\Audit\AuditController@executionsStore')->name('audit.api.executions.store');
        Route::get('/api/executions/{id}',       'App\Http\Controllers\Audit\AuditController@executionsShow')->name('audit.api.executions.show');
        Route::put('/api/executions/{id}',       'App\Http\Controllers\Audit\AuditController@executionsUpdate')->name('audit.api.executions.update');
        Route::post('/api/executions/{id}/close','App\Http\Controllers\Audit\AuditController@executionsClose')->name('audit.api.executions.close');

        // Findings
        Route::post('/api/findings',        'App\Http\Controllers\Audit\AuditController@findingsStore')->name('audit.api.findings.store');
        Route::put('/api/findings/{id}',    'App\Http\Controllers\Audit\AuditController@findingsUpdate')->name('audit.api.findings.update');
        Route::delete('/api/findings/{id}', 'App\Http\Controllers\Audit\AuditController@findingsDestroy')->name('audit.api.findings.destroy');

        // Dashboard KPI
        Route::get('/api/dashboard', 'App\Http\Controllers\Audit\AuditController@apiDashboard')->name('audit.api.dashboard');
    });

    Route::prefix('api/spreadsheet/data')->middleware(['auth', 'verified', 'has.role', 'check.factory', 'permission:spreadsheet-menu'])->name('spreadsheet.data.')->group(function () {
        Route::get('/stock/{reference}', [SpreadsheetDataController::class, 'stock'])->name('stock');
        Route::get('/orders', [SpreadsheetDataController::class, 'orders'])->name('orders');
        Route::get('/revenue', [SpreadsheetDataController::class, 'revenue'])->name('revenue');
        Route::get('/production/kpis', [SpreadsheetDataController::class, 'productionKpis'])->name('productionKpis');
        Route::get('/orders/late', [SpreadsheetDataController::class, 'lateOrders'])->name('lateOrders');
        Route::get('/orders/summary', [SpreadsheetDataController::class, 'ordersSummary'])->name('ordersSummary');
        Route::get('/context', [SpreadsheetDataController::class, 'context'])->name('context');
        Route::get('/customers', [SpreadsheetDataController::class, 'customers'])->name('customers');
        Route::get('/products', [SpreadsheetDataController::class, 'products'])->name('products');
    });
