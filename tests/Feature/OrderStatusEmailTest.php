<?php

namespace Tests\Feature;

use App\Jobs\SendOrderStatusEmail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\EmailLog;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderStatusEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(bool $optIn, int $type = 1, ?string $mail = 'client@example.com'): Orders
    {
        $company = Companies::factory()->create(['statu_customer' => 2, 'order_status_email' => $optIn]);
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id, 'mail' => $mail]);

        return Orders::factory()->create([
            'companies_id'          => $company->id,
            'companies_contacts_id' => $contact->id,
            'statu'                 => 1,
            'type'                  => $type,
        ]);
    }

    public function test_option_is_off_by_default(): void
    {
        $company = Companies::factory()->create();

        $this->assertFalse((bool) $company->fresh()->order_status_email);
    }

    public function test_no_email_when_customer_has_not_opted_in(): void
    {
        $order = $this->makeOrder(optIn: false);
        Queue::fake();

        $order->update(['statu' => 2]);

        Queue::assertNotPushed(SendOrderStatusEmail::class);
    }

    public function test_email_is_queued_on_each_step_when_opted_in(): void
    {
        $order = $this->makeOrder(optIn: true);
        Queue::fake();

        $order->update(['statu' => 2]);
        $order->update(['statu' => 3]);

        Queue::assertPushed(SendOrderStatusEmail::class, 2);
        Queue::assertPushed(SendOrderStatusEmail::class, fn ($job) => $job->orderId === $order->id && $job->status === 3);
    }

    public function test_no_email_when_order_is_stopped_or_canceled(): void
    {
        $order = $this->makeOrder(optIn: true);
        Queue::fake();

        $order->update(['statu' => 5]);
        $order->update(['statu' => 6]);

        Queue::assertNotPushed(SendOrderStatusEmail::class);
    }

    public function test_no_email_for_internal_orders(): void
    {
        $order = $this->makeOrder(optIn: true, type: 2);
        Queue::fake();

        $order->update(['statu' => 2]);

        Queue::assertNotPushed(SendOrderStatusEmail::class);
    }

    public function test_job_sends_mail_to_order_contact_and_logs_it(): void
    {
        $order = $this->makeOrder(optIn: true);
        Queue::fake();
        $order->update(['statu' => 2]);
        Mail::fake();

        (new SendOrderStatusEmail($order->id, 2, 'fr'))->handle();

        Mail::assertSent(OrderStatusChangedMail::class, fn ($mail) => $mail->hasTo('client@example.com'));
        $this->assertDatabaseHas('email_logs', [
            'emailable_type' => Orders::class,
            'emailable_id'   => $order->id,
            'to'             => 'client@example.com',
            'status'         => 'sent',
        ]);
    }

    public function test_job_skips_when_order_moved_on_since_dispatch(): void
    {
        $order = $this->makeOrder(optIn: true);
        Queue::fake();
        $order->update(['statu' => 3]);
        Mail::fake();

        (new SendOrderStatusEmail($order->id, 2, 'fr'))->handle();

        Mail::assertNothingSent();
        $this->assertSame(0, EmailLog::count());
    }

    public function test_job_skips_when_contact_has_no_email(): void
    {
        $order = $this->makeOrder(optIn: true, mail: null);
        Queue::fake();
        $order->update(['statu' => 2]);
        Mail::fake();

        (new SendOrderStatusEmail($order->id, 2, 'fr'))->handle();

        Mail::assertNothingSent();
    }

    public function test_mail_renders_in_requested_locale(): void
    {
        $order = $this->makeOrder(optIn: true);
        $order->statu = 2;

        $html = (new OrderStatusChangedMail($order, 2))->locale('fr')->render();

        $this->assertStringContainsString('est entrée en fabrication', $html);
        $this->assertStringContainsString($order->code, $html);
    }
}
