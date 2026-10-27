<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Services\AI\Tools\ERPToolRegistry;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * L'assistant ERP ne voit que ce que l'utilisateur voit à l'écran : outils,
 * tables de `query` et sections du journal suivent les permissions de menu.
 */
class ERPToolPermissionsTest extends TestCase
{
    private function actingWith(array $permissions): ERPToolRegistry
    {
        $user = User::factory()->create();
        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user);

        return app(ERPToolRegistry::class);
    }

    private function toolNames(ERPToolRegistry $registry): array
    {
        return array_column($registry->definitions(), 'name');
    }

    public function test_a_user_without_menu_permissions_gets_no_tool(): void
    {
        $registry = $this->actingWith([]);

        $this->assertSame([], $this->toolNames($registry));
        $this->assertArrayHasKey('error', $registry->dispatch('search_invoices', []));
        $this->assertArrayHasKey('error', $registry->dispatch('query', ['table' => 'invoices']));
    }

    public function test_tools_and_tables_follow_menu_permissions(): void
    {
        $registry = $this->actingWith(['orders-menu']);

        $names = $this->toolNames($registry);
        $this->assertContains('search_orders', $names);
        $this->assertContains('get_daily_journal', $names);
        $this->assertContains('query', $names);
        $this->assertNotContains('search_invoices', $names);
        $this->assertNotContains('search_quotes', $names);
        $this->assertNotContains('check_stock', $names);

        $query = collect($registry->definitions())->firstWhere('name', 'query');
        $this->assertEqualsCanonicalizing(
            ['orders', 'order_lines', 'companies'],
            $query['input_schema']['properties']['table']['enum'],
        );
    }

    public function test_dispatch_refuses_a_tool_that_was_not_offered(): void
    {
        $registry = $this->actingWith(['orders-menu']);

        $this->assertSame(
            ['error' => "L'utilisateur n'a pas accès à ces données."],
            $registry->dispatch('search_invoices', ['unpaid_only' => true]),
        );
        $this->assertArrayHasKey('error', $registry->dispatch('query', [
            'table'     => 'invoices',
            'aggregate' => ['function' => 'count'],
        ]));
        $this->assertSame('aggregate', $registry->dispatch('query', [
            'table'     => 'orders',
            'aggregate' => ['function' => 'count'],
        ])['mode']);
    }

    public function test_daily_journal_omits_sections_the_user_cannot_see(): void
    {
        $registry = $this->actingWith(['deliverys-menu']);

        $journal = $registry->dispatch('get_daily_journal', []);

        $this->assertArrayHasKey('deliverys', $journal);
        $this->assertArrayNotHasKey('orders', $journal);
        $this->assertArrayNotHasKey('receipts', $journal);
    }
}
