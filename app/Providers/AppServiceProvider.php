<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use Illuminate\Pagination\Paginator;
use App\Models\Workflow\DeliveryLines;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use App\Models\Purchases\PurchaseReceiptLines;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function  boot(Dispatcher $events)
    {
        Paginator::useBootstrap();

        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {

            $OrdersNotFinishCount = Orders::where('statu', '!=', '3')->count();
            
            $DeliverysRequestsCount = OrderLines::where(
                function($query) {
                    return $query
                        ->where('delivery_status', '=', '1')
                        ->orWhere('delivery_status', '=', '2');
                })
                ->whereHas('order', function($q){
                    $q->where('type', '=', '1');
                })->count();

            $InvoicesRequestsCount =  DeliveryLines::where(
                                                        function($query) {
                                                            return $query
                                                                ->where('invoice_status', '=', '1')
                                                                ->orWhere('invoice_status', '=', '2');
                                                        })->count();

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

            $PurchasesWaintingReceiptCount = PurchaseLines::where('receipt_qty','<=', 'qty')->count();
            $PurchasesWaintingInvoiceCount = PurchaseReceiptLines::whereHas('purchaseReceipt', function($q){$q->where('statu',1);})->count();

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
    }
}
