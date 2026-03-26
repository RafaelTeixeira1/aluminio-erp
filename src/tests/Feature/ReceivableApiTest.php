<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivableApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_receivables(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        Receivable::factory()->count(3)->create(['client_id' => $client->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/contas-a-receber');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_receivables_by_client(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        Receivable::factory()->create(['client_id' => $client1->id]);
        Receivable::factory()->create(['client_id' => $client2->id]);

        $response = $this->actingAs($admin)
            ->getJson("/api/contas-a-receber?client_id={$client1->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_settle_receivable(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $receivable = Receivable::factory()->create([
            'status' => 'aberto',
            'amount_total' => 1000.00,
            'amount_paid' => 0,
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/contas-a-receber/{$receivable->id}/receber", [
                'amount' => 1000.00,
                'settled_at' => now()->toDateString(),
            ]);

        $response->assertOk();
        $receivable->refresh();
        $this->assertEquals(1000.00, (float) $receivable->amount_paid);
        $this->assertEquals('quitado', $receivable->status);
    }

    public function test_admin_can_partially_settle_receivable(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $receivable = Receivable::factory()->create([
            'status' => 'aberto',
            'amount_total' => 1000.00,
            'amount_paid' => 0,
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/contas-a-receber/{$receivable->id}/receber", [
                'amount' => 600.00,
                'settled_at' => now()->toDateString(),
            ]);

        $response->assertOk();
        $receivable->refresh();
        $this->assertEquals(600.00, (float) $receivable->amount_paid);
        $this->assertEquals('parcial', $receivable->status);
    }

    public function test_admin_can_get_receivables_by_client(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        Receivable::factory()->count(2)->create(['client_id' => $client->id]);

        $response = $this->actingAs($admin)
            ->getJson("/api/clientes/{$client->id}/contas-a-receber");

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_admin_can_get_receivable_summary(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Receivable::factory()->create([
            'status' => 'aberto',
            'amount_total' => 1000.00,
            'balance' => 1000.00,
        ]);

        Receivable::factory()->create([
            'status' => 'quitado',
            'amount_total' => 500.00,
            'amount_paid' => 500.00,
            'balance' => 0,
            'settled_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/contas-a-receber/resumo');

        $response->assertOk();
        $response->assertJsonPath('open_count', 1);
        $this->assertEquals(1000.0, (float) $response->json('open_balance'));
    }
}
