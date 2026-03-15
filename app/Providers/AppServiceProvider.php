<?php

namespace App\Providers;

use App\Models\Purchases\PurchaseLines;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Observers\OrdersObserver;
use App\Services\SelectDataService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SelectDataService::class, function ($app) {
            return new SelectDataService();
        });

        $this->app->resolving(Command::class, function (Command $command, $app) {
            $command->setLaravel($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function  boot(Dispatcher $events)
    {
        Paginator::useBootstrap();

        Orders::observe(OrdersObserver::class);

        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {

            $OrdersNotFinishCount = Cache::remember('menu_orders_not_finish', 60, function() {
                return Orders::whereNotIn('statu', [3, 6])->count();
            });
            
            $DeliverysRequestsCount = Cache::remember('menu_deliverys_requests', 60, function() {
                return OrderLines::where(
                    function($query) {
                        return $query
                            ->where('delivery_status', '=', '1')
                            ->orWhere('delivery_status', '=', '2');
                    })
                    ->whereHas('order', function($q){
                        $q->whereNotIn('statu', [5, 6])
                            ->where('type', '=', '1');
                    })->count();
            });

            $InvoicesRequestsCount = Cache::remember('menu_invoices_requests', 60, function() {
                return DeliveryLines::where(
                    function($query) {
                        return $query
                            ->where('invoice_status', '=', '1')
                            ->orWhere('invoice_status', '=', '2');
                    })->count();
            });

            /* not use because Status is not init on start of instal, and what is incident if statu is null on menu init ?*/ 
            //$Status = Status::select('id')->orderBy('order')->first();
            /*$PurchasesRequestsCount = Task::where('status_id', '=', $Status->id)
                                                                        ->whereNotNull('order_lines_id')
                                                                        ->where(
                                                                            function($query) {
                                                                                return $query
                                                                                    ->Where('type', '=', '2')
                                                                                    ->orWhere('type', '=', '3')
                                                                                    ->orWhere('type', '=', '4')
                                                                                    ->orWhere('type', '=', '5')
                                                                                    ->orWhere('type', '=', '6')
                                                                                    ->orWhere('type', '=', '7');
                                                                            })->get();*/

            $PurchasesWaintingReceiptCount = Cache::remember('menu_purchases_receipt', 60, function() {
                return PurchaseLines::whereColumn('receipt_qty','<=', 'qty')->count();
            });
            $PurchasesWaintingInvoiceCount = Cache::remember('menu_purchases_invoice', 60, function() {
                return PurchaseLines::whereColumn('invoiced_qty','<=', 'qty')->count();
            });

            $event->menu->addBefore('orders_lines_list', [
                'text' => 'orders_list_trans_key',
                'url'  => 'orders',
                'label'       => $OrdersNotFinishCount,
                'label_color' => 'success',
            ]);

            $event->menu->addIn('delivery_notes', [
                'text' => 'deliverys_notes_request_trans_key',
                'url'  => 'deliverys/request',
                'label'       => $DeliverysRequestsCount,
                'label_color' => 'warning',
            ]);

            $event->menu->addIn('invoices', [
                'text' => 'invoices_request_trans_key',
                'url'  => 'invoices/request',
                'label'       => $InvoicesRequestsCount,
                'label_color' => 'warning',
            ]);

            /*$event->menu->addBefore('requests_for_quotation', [
                'text' => 'purchase_request_trans_key',
                'url'  => 'purchases/request',
                'label'       => $PurchasesRequestsCount,
                'label_color' => 'warning',
            ]);*/

            $event->menu->addBefore('po_receipt', [
                'text' => 'waiting_to_receipt_trans_key',
                'url'  => 'purchases/waiting/receipt',
                'label'       => $PurchasesWaintingReceiptCount,
                'label_color' => 'warning',
            ]);

            $event->menu->addBefore('invoice_supplier', [
                'text' => 'waiting_to_invoice_trans_key',
                'url'  => 'purchases/waiting/invoice',
                'label'       => $PurchasesWaintingInvoiceCount,
                'label_color' => 'warning',
            ]);
        });

         Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('Admin');
        });
    }
}
