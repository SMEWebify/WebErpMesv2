<?php

namespace Tests\Feature;

use App\Mail\DocumentMail;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Le renvoi créait son log avec EmailLog::create([... 'emailable_type' ...]) :
 * clés hors $fillable, donc ignorées, et l'insertion échouait sur la colonne
 * polymorphe NOT NULL.
 */
class EmailLogResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_logs_the_new_attempt_on_the_same_document(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        Permission::findOrCreate('your-company-menu');
        $user = User::factory()->create();
        $user->givePermissionTo('your-company-menu');
        $this->actingAs($user);
        Mail::fake();

        $order = Orders::factory()->create();
        $log   = $order->emailLogs()->create([
            'to'      => 'client@example.test',
            'subject' => 'Commande',
            'message' => '<p>Bonjour</p>',
            'status'  => EmailLog::STATUS_FAILED,
        ]);

        $this->post(route('admin.email-logs.resend', $log))->assertRedirect();

        Mail::assertSent(DocumentMail::class);
        $this->assertSame(2, $order->emailLogs()->count());
        $this->assertDatabaseHas('email_logs', [
            'emailable_type' => Orders::class,
            'emailable_id'   => $order->id,
            'status'         => EmailLog::STATUS_SENT,
        ]);
    }
}
