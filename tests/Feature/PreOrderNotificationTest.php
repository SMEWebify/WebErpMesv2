<?php

namespace Tests\Feature;

use App\Notifications\PreOrderNotification;
use App\Services\PreOrderImportService;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PreOrderNotificationTest extends TestCase
{
    public function test_it_sends_pre_order_notification_only_to_opted_in_users(): void
    {
        Notification::fake();

        $originUser = User::factory()->create();
        $optedInUser = User::factory()->create(['pre_order_notification' => 1]);
        $optedOutUser = User::factory()->create(['pre_order_notification' => 0]);

        $this->actingAs($originUser);

        $csvPath = base_path('tests_tmp/preorders/preorder_notification.csv');
        @mkdir(dirname($csvPath), 0775, true);
        file_put_contents($csvPath, "reference,product,quantity,unit_price,total_price,source_pdf\nA1,Prod,1,10,10,source.pdf\n");

        $result = app(PreOrderImportService::class)->importCsvFile($csvPath);

        $this->assertTrue($result);
        Notification::assertSentTo($optedInUser, PreOrderNotification::class);
        Notification::assertNotSentTo($optedOutUser, PreOrderNotification::class);

        @unlink($csvPath);
        @rmdir(dirname($csvPath));
    }

    public function test_user_can_disable_pre_order_notifications_from_profile_settings(): void
    {
        $user = User::factory()->create(['pre_order_notification' => 1]);

        $response = $this->actingAs($user)->post(route('notifications.setting'), [
            'companies_notification' => 1,
            'users_notification' => 1,
            'quotes_notification' => 1,
            'orders_notification' => 1,
            'non_conformity_notification' => 1,
        ]);

        $response->assertRedirect(route('user.profile', ['id' => $user->id]) . '#History');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'pre_order_notification' => 0,
        ]);
    }
}
