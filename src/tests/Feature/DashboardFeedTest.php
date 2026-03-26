<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_feed_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard/feed');

        $response
            ->assertStatus(401)
            ->assertJsonPath('message', 'Token nao informado.');
    }

    public function test_dashboard_feed_returns_unified_timeline(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
            'name' => 'Operador Feed',
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Feed',
            'phone' => '11900001111',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = CatalogItem::query()->create([
            'name' => 'Item Feed',
            'item_type' => 'produto',
            'price' => 20,
            'stock' => 10,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        $movement = StockMovement::query()->create([
            'catalog_item_id' => $item->id,
            'user_id' => $user->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 2,
            'stock_before' => 8,
            'stock_after' => 10,
            'created_at' => now(),
        ]);

        DB::table('sales')->where('id', $sale->id)->update(['created_at' => now()->subMinutes(3)]);
        DB::table('quotes')->where('id', $quote->id)->update(['created_at' => now()->subMinutes(2)]);
        DB::table('stock_movements')->where('id', $movement->id)->update(['created_at' => now()->subMinute()]);

        $response = $this->getJson('/api/dashboard/feed?limit=10', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'items')
            ->assertJsonPath('items.0.activity_type', 'stock_movement')
            ->assertJsonPath('items.1.activity_type', 'quote')
            ->assertJsonPath('items.2.activity_type', 'sale');
    }

    public function test_dashboard_feed_accepts_filters_search_and_source(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
            'name' => 'Usuario Busca Feed',
        ]);

        $clientMatch = Client::query()->create([
            'name' => 'Cliente Alvo Feed',
            'phone' => '11910000000',
        ]);

        $clientOther = Client::query()->create([
            'name' => 'Cliente Outro Feed',
            'phone' => '11920000000',
        ]);

        $saleMatch = Sale::query()->create([
            'client_id' => $clientMatch->id,
            'status' => 'confirmada',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        Sale::query()->create([
            'client_id' => $clientOther->id,
            'status' => 'pendente',
            'subtotal' => 90,
            'discount' => 0,
            'total' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/dashboard/feed?source=sales&sale_status=confirmada&search=Alvo',
            $this->authHeader($user)
        );

        $response
            ->assertOk()
            ->assertJsonPath('filters.source', 'sales')
            ->assertJsonPath('filters.sale_status', 'confirmada')
            ->assertJsonPath('filters.search', 'Alvo')
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $saleMatch->id)
            ->assertJsonPath('items.0.activity_type', 'sale');
    }

    public function test_dashboard_feed_supports_pagination(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Paginacao Feed',
            'phone' => '11930000000',
        ]);

        $sale1 = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 10,
            'discount' => 0,
            'total' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sale2 = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 20,
            'discount' => 0,
            'total' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sale3 = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 30,
            'discount' => 0,
            'total' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales')->where('id', $sale1->id)->update(['created_at' => now()->subMinutes(3)]);
        DB::table('sales')->where('id', $sale2->id)->update(['created_at' => now()->subMinutes(2)]);
        DB::table('sales')->where('id', $sale3->id)->update(['created_at' => now()->subMinute()]);

        $response = $this->getJson('/api/dashboard/feed?source=sales&limit=2&page=2', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonPath('limit', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('has_more', false)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $sale1->id);
    }

    public function test_vendedor_feed_hides_financial_amounts(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
            'name' => 'Vendedor Feed',
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Feed Vendedor',
            'phone' => '11944445555',
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 180,
            'discount' => 0,
            'total' => 180,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 120,
            'discount' => 0,
            'total' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/dashboard/feed?source=all&limit=10', $this->authHeader($vendedor));

        $response
            ->assertOk()
            ->assertJsonPath('can_view_financial', false);

        $items = collect($response->json('items'));

        $saleItem = $items->firstWhere('activity_type', 'sale');
        $quoteItem = $items->firstWhere('activity_type', 'quote');

        $this->assertNotNull($saleItem);
        $this->assertNotNull($quoteItem);
        $this->assertNull($saleItem['amount'] ?? null);
        $this->assertNull($quoteItem['amount'] ?? null);
    }

    /**
     * @return array{Authorization: string}
     */
    private function authHeader(User $user): array
    {
        $issued = ApiToken::issueForUser($user, 'tests');

        return [
            'Authorization' => 'Bearer '.$issued['plainTextToken'],
        ];
    }
}
