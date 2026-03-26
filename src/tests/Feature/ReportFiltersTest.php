<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CatalogItem;
use App\Models\Category;
use App\Models\Client;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_by_period_accepts_client_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $clientA = Client::query()->create([
            'name' => 'Cliente A',
            'phone' => '11911110001',
        ]);

        $clientB = Client::query()->create([
            'name' => 'Cliente B',
            'phone' => '11911110002',
        ]);

        $saleA = Sale::query()->create([
            'client_id' => $clientA->id,
            'status' => 'confirmada',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        Sale::query()->create([
            'client_id' => $clientB->id,
            'status' => 'confirmada',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        $start = now()->subDays(2)->toDateString();
        $end = now()->toDateString();

        $response = $this->getJson(
            "/api/relatorios/vendas?start_date={$start}&end_date={$end}&client_id={$clientA->id}",
            $this->authHeader($user)
        );

        $response
            ->assertOk()
            ->assertJsonPath('total_sales', 1)
            ->assertJsonPath('total_amount', 100)
            ->assertJsonPath('items.0.id', $saleA->id)
            ->assertJsonPath('items.0.client.id', $clientA->id);
    }

    public function test_best_selling_products_accepts_category_and_item_type_filters(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Relatorio',
            'phone' => '11922220000',
        ]);

        $categoryTarget = Category::query()->create([
            'name' => 'Categoria Alvo',
            'active' => true,
        ]);

        $categoryOther = Category::query()->create([
            'name' => 'Categoria Outra',
            'active' => true,
        ]);

        $itemTarget = CatalogItem::query()->create([
            'name' => 'Produto Alvo',
            'category_id' => $categoryTarget->id,
            'item_type' => 'produto',
            'price' => 50,
            'stock' => 10,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $itemOther = CatalogItem::query()->create([
            'name' => 'Acessorio Outro',
            'category_id' => $categoryOther->id,
            'item_type' => 'acessorio',
            'price' => 20,
            'stock' => 8,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 140,
            'discount' => 0,
            'total' => 140,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'catalog_item_id' => $itemTarget->id,
            'item_name' => $itemTarget->name,
            'item_type' => $itemTarget->item_type,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'catalog_item_id' => $itemOther->id,
            'item_name' => $itemOther->name,
            'item_type' => $itemOther->item_type,
            'quantity' => 2,
            'unit_price' => 20,
            'line_total' => 40,
        ]);

        $response = $this->getJson(
            "/api/relatorios/produtos-mais-vendidos?category_id={$categoryTarget->id}&item_type=produto",
            $this->authHeader($user)
        );

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.catalog_item_id', $itemTarget->id)
            ->assertJsonPath('0.item_name', $itemTarget->name);
    }

    public function test_revenue_accepts_item_type_filter(): void
    {
        $user = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Receita',
            'phone' => '11933330000',
        ]);

        $category = Category::query()->create([
            'name' => 'Categoria Receita',
            'active' => true,
        ]);

        $productItem = CatalogItem::query()->create([
            'name' => 'Produto Receita',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 30,
            'stock' => 5,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $accessoryItem = CatalogItem::query()->create([
            'name' => 'Acessorio Receita',
            'category_id' => $category->id,
            'item_type' => 'acessorio',
            'price' => 40,
            'stock' => 5,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $saleProduct = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 60,
            'discount' => 0,
            'total' => 60,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        SaleItem::query()->create([
            'sale_id' => $saleProduct->id,
            'catalog_item_id' => $productItem->id,
            'item_name' => $productItem->name,
            'item_type' => $productItem->item_type,
            'quantity' => 2,
            'unit_price' => 30,
            'line_total' => 60,
        ]);

        $saleAccessory = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 80,
            'discount' => 0,
            'total' => 80,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        SaleItem::query()->create([
            'sale_id' => $saleAccessory->id,
            'catalog_item_id' => $accessoryItem->id,
            'item_name' => $accessoryItem->name,
            'item_type' => $accessoryItem->item_type,
            'quantity' => 2,
            'unit_price' => 40,
            'line_total' => 80,
        ]);

        $start = now()->subDays(2)->toDateString();
        $end = now()->toDateString();

        $response = $this->getJson(
            "/api/relatorios/faturamento?start_date={$start}&end_date={$end}&item_type=produto",
            $this->authHeader($user)
        );

        $response
            ->assertOk()
            ->assertJsonPath('sales_count', 1)
            ->assertJsonPath('gross_total', 60)
            ->assertJsonPath('daily.0.total', 60);
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
