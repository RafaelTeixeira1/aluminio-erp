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

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard_summary(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Dashboard',
            'phone' => '11555555555',
        ]);

        CatalogItem::query()->create([
            'name' => 'Item Baixo',
            'item_type' => 'produto',
            'price' => 50,
            'stock' => 2,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        CatalogItem::query()->create([
            'name' => 'Item Normal',
            'item_type' => 'produto',
            'price' => 70,
            'stock' => 10,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aprovado',
            'valid_until' => now()->addDay()->toDateString(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'cancelado',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 120,
            'discount' => 0,
            'total' => 120,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 80,
            'discount' => 0,
            'total' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/dashboard/resumo', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('sales_today_count', 2)
            ->assertJsonPath('confirmed_sales_today_count', 1)
            ->assertJsonPath('revenue_today', 120)
            ->assertJsonPath('open_quotes_count', 2)
            ->assertJsonPath('overdue_quotes_count', 1)
            ->assertJsonPath('low_stock_items_count', 1)
            ->assertJsonPath('active_products_count', 2);
    }

    public function test_vendedor_summary_hides_financial_revenue(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Vendedor Dashboard',
            'phone' => '11511111111',
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 250,
            'discount' => 0,
            'total' => 250,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        $response = $this->getJson('/api/dashboard/resumo', $this->authHeader($vendedor));

        $response
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('revenue_today', 0);
    }

    public function test_dashboard_summary_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard/resumo');

        $response
            ->assertStatus(401)
            ->assertJsonPath('message', 'Token nao informado.');
    }

    public function test_authenticated_user_can_view_recent_dashboard_activities(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Timeline',
            'phone' => '11999999999',
        ]);

        $item = CatalogItem::query()->create([
            'name' => 'Item Timeline',
            'item_type' => 'produto',
            'price' => 20,
            'stock' => 10,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        $saleNew = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 150,
            'discount' => 10,
            'total' => 140,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
            'confirmed_at' => now()->subMinute(),
        ]);

        $saleOld = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 80,
            'discount' => 0,
            'total' => 80,
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ]);

        $quoteNew = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 110,
            'discount' => 0,
            'total' => 110,
            'valid_until' => now()->addDays(3)->toDateString(),
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        $quoteOld = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aprovado',
            'subtotal' => 90,
            'discount' => 0,
            'total' => 90,
            'valid_until' => now()->addDay()->toDateString(),
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);

        $movementNew = StockMovement::query()->create([
            'catalog_item_id' => $item->id,
            'user_id' => $user->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 2,
            'stock_before' => 8,
            'stock_after' => 10,
            'created_at' => now()->subMinutes(3),
        ]);

        $movementOld = StockMovement::query()->create([
            'catalog_item_id' => $item->id,
            'user_id' => $user->id,
            'movement_type' => 'saida',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 1,
            'stock_before' => 11,
            'stock_after' => 10,
            'created_at' => now()->subMinutes(25),
        ]);

        DB::table('sales')->where('id', $saleNew->id)->update([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
            'confirmed_at' => now()->subMinute(),
        ]);

        DB::table('sales')->where('id', $saleOld->id)->update([
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ]);

        DB::table('quotes')->where('id', $quoteNew->id)->update([
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        DB::table('quotes')->where('id', $quoteOld->id)->update([
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);

        DB::table('stock_movements')->where('id', $movementNew->id)->update([
            'created_at' => now()->subMinutes(3),
        ]);

        DB::table('stock_movements')->where('id', $movementOld->id)->update([
            'created_at' => now()->subMinutes(25),
        ]);

        $response = $this->getJson('/api/dashboard/atividades?limit=2', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('limit', 2)
            ->assertJsonCount(2, 'sales')
            ->assertJsonCount(2, 'quotes')
            ->assertJsonCount(2, 'stock_movements')
            ->assertJsonPath('sales.0.id', $saleNew->id)
            ->assertJsonPath('sales.1.id', $saleOld->id)
            ->assertJsonPath('quotes.0.id', $quoteNew->id)
            ->assertJsonPath('quotes.1.id', $quoteOld->id)
            ->assertJsonPath('stock_movements.0.id', $movementNew->id)
            ->assertJsonPath('stock_movements.1.id', $movementOld->id)
            ->assertJsonPath('sales.0.client_name', 'Cliente Timeline')
            ->assertJsonPath('stock_movements.0.item_name', 'Item Timeline')
            ->assertJsonPath('stock_movements.0.user_name', $user->name);
    }

    public function test_dashboard_activities_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard/atividades');

        $response
            ->assertStatus(401)
            ->assertJsonPath('message', 'Token nao informado.');
    }

    public function test_vendedor_activities_hide_financial_totals(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Atividades Vendedor',
            'phone' => '11921212121',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/dashboard/atividades?type=all&limit=5', $this->authHeader($vendedor));

        $response
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('sales.0.id', $sale->id)
            ->assertJsonPath('sales.0.total', null)
            ->assertJsonPath('quotes.0.id', $quote->id)
            ->assertJsonPath('quotes.0.total', null);
    }

    public function test_dashboard_activities_accepts_type_and_date_filters(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Filtro',
            'phone' => '11988887777',
        ]);

        $outOfRangeSale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        $inRangeSale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 90,
            'discount' => 0,
            'total' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales')
            ->where('id', $outOfRangeSale->id)
            ->update([
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
                'confirmed_at' => now()->subDays(5),
            ]);

        $from = now()->toDateString();
        $to = now()->toDateString();

        $response = $this->getJson(
            "/api/dashboard/atividades?type=sales&from={$from}&to={$to}",
            $this->authHeader($user)
        );

        $response
            ->assertOk()
            ->assertJsonPath('filters.type', 'sales')
            ->assertJsonPath('filters.from', $from)
            ->assertJsonPath('filters.to', $to)
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $inRangeSale->id)
            ->assertJsonCount(0, 'quotes')
            ->assertJsonCount(0, 'stock_movements');
    }

    public function test_dashboard_activities_rejects_invalid_type_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $response = $this->getJson('/api/dashboard/atividades?type=invalid', $this->authHeader($user));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_dashboard_activities_accepts_granular_status_filters(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Status',
            'phone' => '11977776666',
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleConfirmed = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 150,
            'discount' => 0,
            'total' => 150,
            'created_at' => now(),
            'updated_at' => now(),
            'confirmed_at' => now(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 40,
            'discount' => 0,
            'total' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quoteApproved = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aprovado',
            'subtotal' => 80,
            'discount' => 0,
            'total' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = CatalogItem::query()->create([
            'name' => 'Item Status',
            'item_type' => 'produto',
            'price' => 10,
            'stock' => 20,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        StockMovement::query()->create([
            'catalog_item_id' => $item->id,
            'user_id' => $user->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 2,
            'stock_before' => 18,
            'stock_after' => 20,
            'created_at' => now(),
        ]);

        $movementOutput = StockMovement::query()->create([
            'catalog_item_id' => $item->id,
            'user_id' => $user->id,
            'movement_type' => 'saida',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 1,
            'stock_before' => 21,
            'stock_after' => 20,
            'created_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/dashboard/atividades?sale_status=confirmada&quote_status=aprovado&movement_type=saida',
            $this->authHeader($user)
        );

        $response
            ->assertOk()
            ->assertJsonPath('filters.sale_status', 'confirmada')
            ->assertJsonPath('filters.quote_status', 'aprovado')
            ->assertJsonPath('filters.movement_type', 'saida')
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $saleConfirmed->id)
            ->assertJsonCount(1, 'quotes')
            ->assertJsonPath('quotes.0.id', $quoteApproved->id)
            ->assertJsonCount(1, 'stock_movements')
            ->assertJsonPath('stock_movements.0.id', $movementOutput->id);
    }

    public function test_dashboard_activities_rejects_invalid_movement_type_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $response = $this->getJson('/api/dashboard/atividades?movement_type=invalido', $this->authHeader($user));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['movement_type']);
    }

    public function test_dashboard_activities_supports_pagination_with_page_parameter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Paginacao',
            'phone' => '11966665555',
        ]);

        $saleOne = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 10,
            'discount' => 0,
            'total' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleTwo = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 20,
            'discount' => 0,
            'total' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleThree = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 30,
            'discount' => 0,
            'total' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales')->where('id', $saleOne->id)->update([
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);
        DB::table('sales')->where('id', $saleTwo->id)->update([
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        DB::table('sales')->where('id', $saleThree->id)->update([
            'created_at' => now()->subMinutes(1),
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->getJson('/api/dashboard/atividades?type=sales&limit=2&page=2', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('page', 2)
            ->assertJsonPath('pagination.sales.current_page', 2)
            ->assertJsonPath('pagination.sales.per_page', 2)
            ->assertJsonPath('pagination.sales.total', 3)
            ->assertJsonPath('pagination.sales.has_more', false)
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $saleOne->id)
            ->assertJsonCount(0, 'quotes')
            ->assertJsonCount(0, 'stock_movements');
    }

    public function test_dashboard_activities_rejects_invalid_page_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $response = $this->getJson('/api/dashboard/atividades?page=0', $this->authHeader($user));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }

    public function test_dashboard_activities_supports_sort_direction_controls(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Ordenacao',
            'phone' => '11955554444',
        ]);

        $saleOlder = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 12,
            'discount' => 0,
            'total' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleNewer = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 25,
            'discount' => 0,
            'total' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales')->where('id', $saleOlder->id)->update([
            'created_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ]);

        DB::table('sales')->where('id', $saleNewer->id)->update([
            'created_at' => now()->subMinutes(1),
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->getJson('/api/dashboard/atividades?type=sales&sort=created_at&direction=asc&limit=2', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('filters.sort', 'created_at')
            ->assertJsonPath('filters.direction', 'asc')
            ->assertJsonPath('sales.0.id', $saleOlder->id)
            ->assertJsonPath('sales.1.id', $saleNewer->id);
    }

    public function test_dashboard_activities_rejects_invalid_direction_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $response = $this->getJson('/api/dashboard/atividades?direction=up', $this->authHeader($user));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['direction']);
    }

    public function test_dashboard_activities_supports_search_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
            'name' => 'Usuario Busca',
        ]);

        $clientMatch = Client::query()->create([
            'name' => 'Cliente Alvo',
            'phone' => '11911112222',
        ]);

        $clientOther = Client::query()->create([
            'name' => 'Cliente Outro',
            'phone' => '11933334444',
        ]);

        $saleMatch = Sale::query()->create([
            'client_id' => $clientMatch->id,
            'status' => 'pendente',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sale::query()->create([
            'client_id' => $clientOther->id,
            'status' => 'pendente',
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quoteMatch = Quote::query()->create([
            'client_id' => $clientMatch->id,
            'status' => 'aberto',
            'subtotal' => 80,
            'discount' => 0,
            'total' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Quote::query()->create([
            'client_id' => $clientOther->id,
            'status' => 'aberto',
            'subtotal' => 40,
            'discount' => 0,
            'total' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemMatch = CatalogItem::query()->create([
            'name' => 'Item Alvo',
            'item_type' => 'produto',
            'price' => 10,
            'stock' => 12,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $itemOther = CatalogItem::query()->create([
            'name' => 'Item Outro',
            'item_type' => 'produto',
            'price' => 20,
            'stock' => 8,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $movementMatch = StockMovement::query()->create([
            'catalog_item_id' => $itemMatch->id,
            'user_id' => $user->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 2,
            'stock_before' => 10,
            'stock_after' => 12,
            'created_at' => now(),
        ]);

        StockMovement::query()->create([
            'catalog_item_id' => $itemOther->id,
            'user_id' => $user->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 1,
            'stock_before' => 7,
            'stock_after' => 8,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/dashboard/atividades?search=Alvo', $this->authHeader($user));

        $response
            ->assertOk()
            ->assertJsonPath('filters.search', 'Alvo')
            ->assertJsonCount(1, 'sales')
            ->assertJsonPath('sales.0.id', $saleMatch->id)
            ->assertJsonCount(1, 'quotes')
            ->assertJsonPath('quotes.0.id', $quoteMatch->id)
            ->assertJsonCount(1, 'stock_movements')
            ->assertJsonPath('stock_movements.0.id', $movementMatch->id);
    }

    public function test_dashboard_activities_rejects_too_long_search_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $tooLong = str_repeat('a', 101);
        $response = $this->getJson('/api/dashboard/atividades?search='.$tooLong, $this->authHeader($user));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search']);
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
