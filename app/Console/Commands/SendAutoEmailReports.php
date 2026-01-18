<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\AutoEmailReportMail;
use App\Models\UserAutoEmailReport;
use App\Models\Workflow\Orders;
use App\Models\Products\StockLocationProducts;

class SendAutoEmailReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-auto-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic email reports to users at their chosen time.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i');

        $reports = UserAutoEmailReport::with('user')
            ->where('enabled', true)
            ->where('send_time', $currentTime)
            ->get();

        if ($reports->isEmpty()) {
            $this->info('No automatic email reports scheduled for this time.');
            return Command::SUCCESS;
        }

        foreach ($reports as $report) {
            $reportDefinition = $this->reportDefinitions($report->report_type);

            if (!$reportDefinition) {
                continue;
            }

            $reportData = $reportDefinition['builder']();

            Mail::to($report->user->email)->send(
                new AutoEmailReportMail(
                    $report->user,
                    $reportDefinition['title'],
                    $reportData
                )
            );
        }

        $this->info('Automatic email reports sent.');

        return Command::SUCCESS;
    }

    private function reportDefinitions(string $type): ?array
    {
        $definitions = [
            UserAutoEmailReport::REPORT_OVERDUE_ORDERS => [
                'title' => __('general_content.overdue_orders_report_trans_key'),
                'builder' => fn () => $this->buildOverdueOrdersReport(),
            ],
            UserAutoEmailReport::REPORT_TOMORROW_ORDERS => [
                'title' => __('general_content.tomorrow_orders_report_trans_key'),
                'builder' => fn () => $this->buildTomorrowOrdersReport(),
            ],
            UserAutoEmailReport::REPORT_LOW_STOCK => [
                'title' => __('general_content.low_stock_report_trans_key'),
                'builder' => fn () => $this->buildLowStockReport(),
            ],
        ];

        return $definitions[$type] ?? null;
    }

    private function buildOverdueOrdersReport(): array
    {
        $orders = Orders::with('companie')
            ->whereNotIn('statu', [3, 6])
            ->whereNotNull('validity_date')
            ->whereDate('validity_date', '<', Carbon::today())
            ->orderBy('validity_date')
            ->get();

        $rows = $orders->map(function (Orders $order) {
            return [
                $order->code,
                $order->label,
                $order->companie?->label ?? '-',
                Carbon::parse($order->validity_date)->format('d/m/Y'),
            ];
        })->all();

        return [
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'columns' => [
                __('general_content.code_trans_key'),
                __('general_content.label_trans_key'),
                __('general_content.companie_trans_key'),
                __('general_content.validity_date_trans_key'),
            ],
            'rows' => $rows,
        ];
    }

    private function buildTomorrowOrdersReport(): array
    {
        $orders = Orders::with('companie')
            ->whereNotIn('statu', [3, 6])
            ->whereNotNull('validity_date')
            ->whereDate('validity_date', Carbon::tomorrow())
            ->orderBy('validity_date')
            ->get();

        $rows = $orders->map(function (Orders $order) {
            return [
                $order->code,
                $order->label,
                $order->companie?->label ?? '-',
                Carbon::parse($order->validity_date)->format('d/m/Y'),
            ];
        })->all();

        return [
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'columns' => [
                __('general_content.code_trans_key'),
                __('general_content.label_trans_key'),
                __('general_content.companie_trans_key'),
                __('general_content.validity_date_trans_key'),
            ],
            'rows' => $rows,
        ];
    }

    private function buildLowStockReport(): array
    {
        $stockLocations = StockLocationProducts::with(['Product', 'StockLocation'])->get();

        $rows = $stockLocations->map(function (StockLocationProducts $stockLocation) {
            if ($stockLocation->mini_qty === null) {
                return null;
            }

            $currentStock = $stockLocation->getCurrentStockMove();

            if ($currentStock >= $stockLocation->mini_qty) {
                return null;
            }

            return [
                $stockLocation->Product?->code ?? '-',
                $stockLocation->Product?->label ?? '-',
                $stockLocation->StockLocation?->label ?? '-',
                $currentStock,
                $stockLocation->mini_qty,
            ];
        })->filter()->values()->all();

        return [
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'columns' => [
                __('general_content.code_trans_key'),
                __('general_content.label_trans_key'),
                __('general_content.stock_trans_key'),
                __('general_content.current_stock_trans_key'),
                __('general_content.qty_mini_trans_key'),
            ],
            'rows' => $rows,
        ];
    }
}
