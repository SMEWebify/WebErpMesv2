<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Workflow\Orders;
use App\Notifications\OrderNotification;

class OrderService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * New Order
     *
     * @param string $code
     * @param string $label
     * @param string $customerReference
     * @param object $companyId
     * @param object $companyContactId
     * @param object $companyAddressId
     * @param object $validityDate
     * @param string $status
     * @param object $userId
     * @param object $paymentConditionId
     * @param object $paymentMethodId
     * @param object $deliveryId
     * @param string $comment
     * @param string $type
     * @return Orders
     */
    public function createOrder(
        $code,
        $label,
        $customerReference,
        $companyId,
        $companyContactId,
        $companyAddressId,
        $validityDate,
        $status,
        $userId,
        $paymentConditionId,
        $paymentMethodId,
        $deliveryId,
        $comment,
        $type,
        $quotes_id,
        $filename
    ) {
        try {
            $OrdersCreated = Orders::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'label' => $label,
                'customer_reference' => $customerReference,
                'companies_id' => $companyId,
                'companies_contacts_id' => $companyContactId,
                'companies_addresses_id' => $companyAddressId,
                'validity_date' => $validityDate,
                'statu' => $status,
                'user_id' => $userId,
                'accounting_payment_conditions_id' => $paymentConditionId,
                'accounting_payment_methods_id' => $paymentMethodId,
                'accounting_deliveries_id' => $deliveryId,
                'comment' => $comment,
                'type' => $type,
                'quotes_id' => $quotes_id,
                'csv_file_name' => $filename,
            ]);

            // notification
            $this->notificationService->sendNotification(OrderNotification::class, $OrdersCreated, 'orders_notification');

            Log::channel('orders')->info(__('general_content.order_created_log_trans_key'), [
                'user_id' => $userId,
                'order_id' => $OrdersCreated->id,
                'parameters' => [
                    'code' => $code,
                    'company_id' => $companyId,
                    'contact_id' => $companyContactId,
                    'address_id' => $companyAddressId,
                    'status' => $status,
                ],
            ]);

            return $OrdersCreated;
        } catch (\Exception $e) {
            Log::channel('orders')->error(__('general_content.order_creation_failed_log_trans_key'), [
                'user_id' => $userId,
                'exception' => $e->getMessage(),
                'parameters' => [
                    'code' => $code,
                    'company_id' => $companyId,
                    'contact_id' => $companyContactId,
                    'address_id' => $companyAddressId,
                    'status' => $status,
                ],
            ]);
            throw $e;
        }
    }

    
}
