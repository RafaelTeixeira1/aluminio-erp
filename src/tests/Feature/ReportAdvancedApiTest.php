<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CatalogItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAdvancedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_dre(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Sale::factory()->count(2)->create(['total' => 1000.00, 'discount' => 100.00, 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/relatorios/dre?start_date='.now()->toDateString().'&end_date='.now()->toDateString());

        $response->assertOk();
        $this->assertEquals(2000.0, (float) $response->json('revenue.gross'));
        $this->assertEquals(200.0, (float) $response->json('revenue.discount'));
        $this->assertEquals(1800.0, (float) $response->json('revenue.net'));
    }

    public function test_dre_calculates_margins(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Sale::factory()->create(['total' => 1000.00, 'discount' => 0, 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/relatorios/dre?start_date='.now()->toDateString().'&end_date='.now()->toDateString());

        $response->assertOk();
        // Test:  60% COGS = 600, 40% gross profit
        $this->assertEquals(40.0, (float) $response->json('costs.gross_margin_pct'));
    }

    public function test_admin_can_get_margin_by_category(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $category = Category::query()->create(['name' => 'Categoria Teste', 'active' => true]);
        $item = CatalogItem::query()->create([
            'name' => 'Item Teste',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 100,
            'stock' => 10,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $sale = Sale::factory()->create(['total' => 1000.00, 'subtotal' => 1000.00, 'created_at' => now()]);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'catalog_item_id' => $item->id,
            'item_name' => $item->name,
            'item_type' => $item->item_type,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/relatorios/margem-por-categoria?start_date='.now()->toDateString().'&end_date='.now()->toDateString());

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json()));
    }

    public function test_admin_can_get_profit_by_period(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Sale::factory()->count(2)->create(['total' => 1000.00, 'discount' => 50.00, 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/relatorios/lucro-por-periodo?months_back=3');

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json()));
    }

    public function test_profit_by_period_includes_all_metrics(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Sale::factory()->create(['total' => 1000.00, 'discount' => 100.00, 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->getJson('/api/relatorios/lucro-por-periodo?months_back=1');

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('month', $data[0]);
        $this->assertArrayHasKey('sales_count', $data[0]);
        $this->assertArrayHasKey('revenue', $data[0]);
        $this->assertArrayHasKey('profit', $data[0]);
    }

    public function test_non_admin_cannot_access_dre(): void
    {
        $vendedor = User::factory()->create(['profile' => 'vendedor', 'active' => true]);

        $this->actingAs($vendedor)
            ->getJson('/api/relatorios/dre?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_margin_by_category(): void
    {
        $vendedor = User::factory()->create(['profile' => 'vendedor', 'active' => true]);

        $this->actingAs($vendedor)
            ->getJson('/api/relatorios/margem-por-categoria?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_profit_by_period(): void
    {
        $vendedor = User::factory()->create(['profile' => 'vendedor', 'active' => true]);

        $this->actingAs($vendedor)
            ->getJson('/api/relatorios/lucro-por-periodo')
            ->assertForbidden();
    }
}
