<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_client_analysis(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        Sale::factory()->count(2)->create(['client_id' => $client->id, 'total' => 1000.00]);
        Quote::factory()->count(3)->create(['client_id' => $client->id, 'total' => 500.00]);

        Receivable::factory()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'amount_total' => 1000.00,
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/clientes/{$client->id}/analise");

        $response->assertOk();
        $response->assertJsonPath('sales.total_count', 2);
        $response->assertJsonPath('quotes.total_count', 3);
        $this->assertEquals(1000.0, (float) $response->json('receivables.open_balance'));
    }

    public function test_admin_can_get_client_timeline(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        Sale::factory()->create(['client_id' => $client->id, 'total' => 1000.00]);
        Quote::factory()->create(['client_id' => $client->id, 'total' => 500.00]);
        Receivable::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($admin)
            ->getJson("/api/clientes/{$client->id}/timeline");

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_admin_can_get_client_revenue_by_period(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        Sale::factory()->count(2)->create(['client_id' => $client->id, 'created_at' => now(), 'total' => 1000.00]);

        $response = $this->actingAs($admin)
            ->getJson("/api/clientes/{$client->id}/faturamento?months_back=3");

        $response->assertOk();
    }

    public function test_analysis_includes_top_products(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        $sale = Sale::factory()->create(['client_id' => $client->id, 'total' => 1000.00]);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'quantity' => 10,
            'line_total' => 500.00,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/clientes/{$client->id}/analise");

        $response->assertOk();
        $response->assertJsonCount(1, 'top_products');
    }

    public function test_cost_analysis_calculates_margin(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::factory()->create();

        Sale::factory()->create(['client_id' => $client->id, 'total' => 1000.00]);

        $response = $this->actingAs($admin)
            ->getJson("/api/clientes/{$client->id}/analise");

        $response->assertOk();
        $this->assertEquals(40.0, (float) $response->json('cost_analysis.estimated_margin_percentage'));
    }

    public function test_non_admin_cannot_access_client_analysis(): void
    {
        $vendedor = User::factory()->create(['profile' => 'vendedor', 'active' => true]);
        $client = Client::factory()->create();

        $this->actingAs($vendedor)
            ->getJson("/api/clientes/{$client->id}/analise")
            ->assertForbidden();
    }
}
