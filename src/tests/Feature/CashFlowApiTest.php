<?php

namespace Tests\Feature;

use App\Models\CashEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_cash_entries(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        CashEntry::factory()->count(3)->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/fluxo-caixa');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_cash_entries_by_type(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        CashEntry::factory()->count(2)->create(['type' => 'entrada', 'user_id' => $admin->id]);
        CashEntry::factory()->count(2)->create(['type' => 'saida', 'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/fluxo-caixa?type=entrada');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_cash_entry(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $response = $this->actingAs($admin)
            ->postJson('/api/fluxo-caixa', [
                'type' => 'entrada',
                'description' => 'Venda direta',
                'amount' => 500.00,
                'occurred_at' => now()->toDateString(),
                'origin_type' => 'manual',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('cash_entries', [
            'type' => 'entrada',
            'description' => 'Venda direta',
        ]);
    }

    public function test_admin_can_get_cash_flow_summary(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        CashEntry::factory()->create(['type' => 'entrada', 'amount' => 1000.00, 'user_id' => $admin->id, 'occurred_at' => now()]);
        CashEntry::factory()->create(['type' => 'saida', 'amount' => 300.00, 'user_id' => $admin->id, 'occurred_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/fluxo-caixa/resumo');

        $response->assertOk();
        $this->assertEquals(1000.0, (float) $response->json('actual.inflow'));
        $this->assertEquals(300.0, (float) $response->json('actual.outflow'));
        $this->assertEquals(700.0, (float) $response->json('actual.net'));
    }

    public function test_admin_can_get_cash_entries_by_type(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        CashEntry::factory()->count(2)->create(['type' => 'entrada', 'amount' => 500.00, 'user_id' => $admin->id, 'occurred_at' => now()]);
        CashEntry::factory()->create(['type' => 'saida', 'amount' => 200.00, 'user_id' => $admin->id, 'occurred_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/fluxo-caixa/por-tipo');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_admin_can_delete_cash_entry(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $entry = CashEntry::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/fluxo-caixa/{$entry->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('cash_entries', ['id' => $entry->id]);
    }

    public function test_non_admin_cannot_access_cash_flow(): void
    {
        $vendedor = User::factory()->create(['profile' => 'vendedor', 'active' => true]);

        $this->actingAs($vendedor)
            ->getJson('/api/fluxo-caixa')
            ->assertForbidden();
    }
}
