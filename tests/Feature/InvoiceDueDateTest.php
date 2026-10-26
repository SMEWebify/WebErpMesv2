<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingPaymentConditions;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDueDateTest extends TestCase
{
    use RefreshDatabase;

    private function condition(int $months, int $days, int $monthEnd, int $default = 0): AccountingPaymentConditions
    {
        return AccountingPaymentConditions::create([
            'code' => "C{$months}-{$days}-{$monthEnd}",
            'label' => 'Condition',
            'number_of_month' => $months,
            'number_of_day' => $days,
            'month_end' => $monthEnd,
            'default' => $default,
        ]);
    }

    public function test_net_days(): void
    {
        $due = $this->condition(0, 45, 2)->dueDateFrom(Carbon::parse('2026-10-26'));

        $this->assertSame('2026-12-10', $due->toDateString());
    }

    public function test_days_end_of_month(): void
    {
        $due = $this->condition(0, 30, 1)->dueDateFrom(Carbon::parse('2026-01-10'));

        $this->assertSame('2026-02-28', $due->toDateString());
    }

    public function test_months_do_not_overflow(): void
    {
        $due = $this->condition(1, 0, 2)->dueDateFrom(Carbon::parse('2026-01-31'));

        $this->assertSame('2026-02-28', $due->toDateString());
    }

    public function test_month_end_from_setup_wizard_zero_means_no(): void
    {
        $due = $this->condition(0, 30, 0)->dueDateFrom(Carbon::parse('2026-10-01'));

        $this->assertSame('2026-10-31', $due->toDateString());
    }

    public function test_order_condition_wins_over_default(): void
    {
        $this->condition(0, 60, 2, default: 1);
        $orderCondition = $this->condition(0, 10, 2);

        $due = app(InvoiceService::class)->dueDate($orderCondition->id, Carbon::parse('2026-10-26'));

        $this->assertSame('2026-11-05', $due->toDateString());
    }

    public function test_default_condition_when_order_has_none(): void
    {
        $this->condition(0, 60, 2, default: 1);

        $due = app(InvoiceService::class)->dueDate(null, Carbon::parse('2026-10-26'));

        $this->assertSame('2026-12-25', $due->toDateString());
    }

    public function test_thirty_days_without_any_condition(): void
    {
        $due = app(InvoiceService::class)->dueDate(null, Carbon::parse('2026-10-26'));

        $this->assertSame('2026-11-25', $due->toDateString());
    }
}
