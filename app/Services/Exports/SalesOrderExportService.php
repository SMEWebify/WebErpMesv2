<?php

namespace App\Services\Exports;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SalesOrderExportService
{
    public function get(array $options): array
    {
        $includeLines = (bool) ($options['include_lines'] ?? false);
        $dateFormat = $options['date_format'] ?? 'Y-m-d';
        $dateTimeFormat = $options['datetime_format'] ?? 'Y-m-d H:i:s';

        $query = Orders::query()->with([
            'companie',
            'contact',
            'adresse',
            'UserManagement',
            'payment_condition',
            'payment_method',
            'delevery_method',
            'Quote',
        ]);

        if ($includeLines) {
            $query->with([
                'OrderLines' => function ($lineQuery) {
                    $lineQuery
                        ->orderBy('ordre')
                        ->with([
                            'Product',
                            'Unit',
                            'VAT',
                            'Task' => function ($taskQuery) {
                                $taskQuery
                                    ->orderBy('ordre')
                                    ->with([
                                        'service',
                                        'Unit',
                                        'MethodsTools',
                                        'Products',
                                        'Component',
                                        'status',
                                    ]);
                            },
                        ]);
                },
            ]);
        }

        if (!empty($options['status'])) {
            $query->where('statu', (int) $options['status']);
        }

        if (!empty($options['from'])) {
            $query->whereDate('created_at', '>=', $options['from']);
        }

        if (!empty($options['to'])) {
            $query->whereDate('created_at', '<=', $options['to']);
        }

        return $query
            ->orderBy('id')
            ->get()
            ->map(function (Orders $order) use ($includeLines, $dateFormat, $dateTimeFormat) {
                return $this->mapOrder($order, $dateFormat, $dateTimeFormat, $includeLines);
            })
            ->all();
    }

    private function mapOrder(Orders $order, string $dateFormat, string $dateTimeFormat, bool $includeLines): array
    {
        $payload = [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'code' => $order->code,
            'label' => $order->label,
            'customer_reference' => $order->customer_reference,
            'company' => $this->mapCompany($order->companie),
            'company_contact' => $this->mapCompanyContact($order->contact),
            'company_address' => $this->mapCompanyAddress($order->adresse),
            'validity_date' => $this->formatDate($order->validity_date, $dateFormat),
            'statu' => $this->castInt($order->statu),
            'user' => $this->mapUser($order->UserManagement),
            'payment_condition' => $this->mapPaymentCondition($order->payment_condition),
            'payment_method' => $this->mapPaymentMethod($order->payment_method),
            'delivery_method' => $this->mapDeliveryMethod($order->delevery_method),
            'quote' => $this->mapQuote($order->Quote),
            'comment' => $order->comment,
            'type' => $this->castInt($order->type),
            'csv_file_name' => $order->csv_file_name,
            'created_at' => $this->formatDateTime($order->created_at, $dateTimeFormat),
            'updated_at' => $this->formatDateTime($order->updated_at, $dateTimeFormat),
        ];

        if ($includeLines) {
            $payload['order_lines'] = $this->mapOrderLines($order, $dateFormat, $dateTimeFormat);
        }

        return $payload;
    }

    private function mapOrderLines(Orders $order, string $dateFormat, string $dateTimeFormat): array
    {
        return ($order->OrderLines ?? collect())
            ->map(function (OrderLines $line) use ($dateFormat, $dateTimeFormat) {
                return $this->mapOrderLine($line, $dateFormat, $dateTimeFormat);
            })
            ->values()
            ->all();
    }

    private function mapOrderLine(OrderLines $line, string $dateFormat, string $dateTimeFormat): array
    {
        return [
            'id' => $line->id,
            'orders_id' => $this->castInt($line->orders_id),
            'ordre' => $this->castInt($line->ordre),
            'code' => $line->code,
            'product_id' => $line->product_id,
            'product' => $this->mapProduct($line->Product),
            'label' => $line->label,
            'qty' => $this->castFloat($line->qty, 3),
            'delivered_qty' => $this->castFloat($line->delivered_qty, 3),
            'delivered_remaining_qty' => $this->castFloat($line->delivered_remaining_qty, 3),
            'invoiced_qty' => $this->castFloat($line->invoiced_qty, 3),
            'invoiced_remaining_qty' => $this->castFloat($line->invoiced_remaining_qty, 3),
            'unit' => $this->mapUnit($line->Unit),
            'selling_price' => $this->castFloat($line->selling_price, 3),
            'discount' => $this->castFloat($line->discount, 3),
            'vat' => $this->mapVat($line->VAT),
            'delivery_date' => $this->formatDate($line->delivery_date, $dateFormat),
            'tasks_status' => $this->castInt($line->tasks_status),
            'internal_delay' => $this->formatDate($line->internal_delay, $dateFormat),
            'delivery_status' => $this->castInt($line->delivery_status),
            'invoice_status' => $this->castInt($line->invoice_status),
            'use_calculated_price' => $this->castInt($line->use_calculated_price),
            'created_at' => $this->formatDateTime($line->created_at, $dateTimeFormat),
            'updated_at' => $this->formatDateTime($line->updated_at, $dateTimeFormat),
            'tasks' => $this->mapTasks($line, $dateFormat, $dateTimeFormat),
        ];
    }

    private function mapTasks(OrderLines $line, string $dateFormat, string $dateTimeFormat): array
    {
        return ($line->Task ?? collect())
            ->map(function (Task $task) use ($dateFormat, $dateTimeFormat) {
                return [
                    'id' => $task->id,
                    'code' => $task->code,
                    'label' => $task->label,
                    'ordre' => $this->castInt($task->ordre),
                    'quote_lines_id' => $this->castInt($task->quote_lines_id),
                    'order_lines_id' => $this->castInt($task->order_lines_id),
                    'products_id' => $this->castInt($task->products_id),
                    'product' => $this->mapProduct($task->Products),
                    'sub_assembly_id' => $this->castInt($task->sub_assembly_id),
                    'service' => $this->mapService($task->service),
                    'component_id' => $this->castInt($task->component_id),
                    'component' => $this->mapProduct($task->Component),
                    'seting_time' => $this->castFloat($task->seting_time, 3),
                    'unit_time' => $this->castFloat($task->unit_time, 3),
                    'remaining_time' => $this->castFloat($task->remaining_time, 3),
                    'status_id' => $this->castInt($task->status_id),
                    'status' => $this->mapStatus($task->status),
                    'type' => $this->castInt($task->type),
                    'delay' => $this->formatDate($task->delay, $dateFormat),
                    'qty' => $this->castFloat($task->qty, 3),
                    'qty_init' => $this->castFloat($task->qty_init, 3),
                    'qty_aviable' => $this->castFloat($task->qty_aviable, 3),
                    'unit_cost' => $this->castFloat($task->unit_cost, 3),
                    'unit_price' => $this->castFloat($task->unit_price, 3),
                    'unit' => $this->mapUnit($task->Unit),
                    'x_size' => $this->castFloat($task->x_size, 3),
                    'y_size' => $this->castFloat($task->y_size, 3),
                    'z_size' => $this->castFloat($task->z_size, 3),
                    'x_oversize' => $this->castFloat($task->x_oversize, 3),
                    'y_oversize' => $this->castFloat($task->y_oversize, 3),
                    'z_oversize' => $this->castFloat($task->z_oversize, 3),
                    'diameter' => $this->castFloat($task->diameter, 3),
                    'diameter_oversize' => $this->castFloat($task->diameter_oversize, 3),
                    'to_schedule' => $this->castInt($task->to_schedule),
                    'start_date' => $this->formatDateTime($task->start_date, $dateTimeFormat),
                    'end_date' => $this->formatDateTime($task->end_date, $dateTimeFormat),
                    'not_recalculate' => $this->castInt($task->not_recalculate),
                    'material' => $task->material,
                    'thickness' => $this->castFloat($task->thickness, 3),
                    'weight' => $this->castFloat($task->weight, 3),
                    'tool' => $this->mapTool($task->MethodsTools),
                    'origin' => $task->origin,
                    'created_at' => $this->formatDateTime($task->created_at, $dateTimeFormat),
                    'updated_at' => $this->formatDateTime($task->updated_at, $dateTimeFormat),
                ];
            })
            ->values()
            ->all();
    }

    private function formatDate($value, string $format): ?string
    {
        return $this->formatTemporal($value, $format);
    }

    private function formatDateTime($value, string $format): ?string
    {
        return $this->formatTemporal($value, $format);
    }

    private function formatTemporal($value, string $format): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format($format);
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    private function castFloat($value, ?int $precision = null): ?float
    {
        if ($value === null) {
            return null;
        }

        $floatValue = (float) $value;

        return $precision !== null ? round($floatValue, $precision) : $floatValue;
    }

    private function castInt($value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    private function mapCompany(?Companies $company): ?array
    {
        if (!$company) {
            return null;
        }

        return [
            'id' => $company->id,
            'code' => $company->code,
            'label' => $company->label,
        ];
    }

    private function mapCompanyContact(?CompaniesContacts $contact): ?array
    {
        if (!$contact) {
            return null;
        }

        return [
            'id' => $contact->id,
            'label' => $this->buildContactLabel($contact),
            'function' => $contact->function,
            'phone' => $contact->number,
            'mobile' => $contact->mobile,
            'email' => $contact->mail,
        ];
    }

    private function mapCompanyAddress(?CompaniesAddresses $address): ?array
    {
        if (!$address) {
            return null;
        }

        return [
            'id' => $address->id,
            'label' => $address->label,
            'address' => $address->adress,
            'zipcode' => $address->zipcode,
            'city' => $address->city,
            'country' => $address->country,
        ];
    }

    private function mapUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    private function mapPaymentCondition(?AccountingPaymentConditions $condition): ?array
    {
        if (!$condition) {
            return null;
        }

        return [
            'id' => $condition->id,
            'code' => $condition->code,
            'label' => $condition->label,
        ];
    }

    private function mapPaymentMethod(?AccountingPaymentMethod $method): ?array
    {
        if (!$method) {
            return null;
        }

        return [
            'id' => $method->id,
            'code' => $method->code,
            'label' => $method->label,
        ];
    }

    private function mapDeliveryMethod(?AccountingDelivery $delivery): ?array
    {
        if (!$delivery) {
            return null;
        }

        return [
            'id' => $delivery->id,
            'code' => $delivery->code,
            'label' => $delivery->label,
        ];
    }

    private function mapQuote(?Quotes $quote): ?array
    {
        if (!$quote) {
            return null;
        }

        return [
            'id' => $quote->id,
            'code' => $quote->code,
            'label' => $quote->label,
        ];
    }

    private function mapProduct(?Products $product): ?array
    {
        if (!$product) {
            return null;
        }

        return [
            'id' => $product->id,
            'code' => $product->code,
            'label' => $product->label,
        ];
    }

    private function mapUnit(?MethodsUnits $unit): ?array
    {
        if (!$unit) {
            return null;
        }

        return [
            'id' => $unit->id,
            'code' => $unit->code,
            'label' => $unit->label,
        ];
    }

    private function mapVat(?AccountingVat $vat): ?array
    {
        if (!$vat) {
            return null;
        }

        return [
            'id' => $vat->id,
            'code' => $vat->code,
            'label' => $vat->label,
            'rate' => $this->castFloat($vat->rate, 3),
        ];
    }

    private function mapService(?MethodsServices $service): ?array
    {
        if (!$service) {
            return null;
        }

        return [
            'id' => $service->id,
            'code' => $service->code,
            'label' => $service->label,
        ];
    }

    private function mapTool(?MethodsTools $tool): ?array
    {
        if (!$tool) {
            return null;
        }

        return [
            'id' => $tool->id,
            'code' => $tool->code,
            'label' => $tool->label,
        ];
    }

    private function mapStatus(?Status $status): ?array
    {
        if (!$status) {
            return null;
        }

        return [
            'id' => $status->id,
            'label' => $status->title,
        ];
    }

    private function buildContactLabel(CompaniesContacts $contact): string
    {
        $parts = array_filter([
            $contact->civility,
            $contact->first_name,
            $contact->name,
        ]);

        return trim(implode(' ', $parts));
    }
}
