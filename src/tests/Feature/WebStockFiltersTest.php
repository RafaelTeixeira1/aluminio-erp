<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebStockFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_page_can_filter_movements_by_type_and_item(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $itemA = CatalogItem::query()->create([
            'name' => 'Perfil A',
            'item_type' => 'perfil',
            'price' => 10,
            'stock' => 20,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);

        $itemB = CatalogItem::query()->create([
            'name' => 'Perfil B',
            'item_type' => 'perfil',
            'price' => 11,
            'stock' => 30,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);

        StockMovement::query()->create([
            'catalog_item_id' => $itemA->id,
            'user_id' => $admin->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 10,
            'stock_before' => 10,
            'stock_after' => 20,
            'notes' => 'Entrada A',
            'created_at' => now()->subDay(),
        ]);

        StockMovement::query()->create([
            'catalog_item_id' => $itemB->id,
            'user_id' => $admin->id,
            'movement_type' => 'saida',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 2,
            'stock_before' => 32,
            'stock_after' => 30,
            'notes' => 'Saida B',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/estoque?movement_type=entrada&movement_item_id=' . $itemA->id);

        $response->assertOk();
        $response->assertSee('Entrada A');
        $response->assertDontSee('Saida B');
    }

    public function test_stock_csv_export_respects_filters(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $itemA = CatalogItem::query()->create([
            'name' => 'Perfil C',
            'item_type' => 'perfil',
            'price' => 20,
            'stock' => 50,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);

        $itemB = CatalogItem::query()->create([
            'name' => 'Perfil D',
            'item_type' => 'perfil',
            'price' => 22,
            'stock' => 40,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);

        StockMovement::query()->create([
            'catalog_item_id' => $itemA->id,
            'user_id' => $admin->id,
            'movement_type' => 'entrada',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 3,
            'stock_before' => 47,
            'stock_after' => 50,
            'notes' => 'CSV Entrada C',
            'created_at' => now(),
        ]);

        StockMovement::query()->create([
            'catalog_item_id' => $itemB->id,
            'user_id' => $admin->id,
            'movement_type' => 'saida',
            'origin_type' => 'manual',
            'origin_id' => null,
            'quantity' => 1,
            'stock_before' => 41,
            'stock_after' => 40,
            'notes' => 'CSV Saida D',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/estoque/historico.csv?movement_type=entrada&movement_item_id='.$itemA->id);

        $response->assertOk();
        $response->assertStreamed();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('CSV Entrada C', $csv);
        $this->assertStringNotContainsString('CSV Saida D', $csv);
    }

    public function test_operador_cannot_export_stock_csv(): void
    {
        $operador = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $this->actingAs($operador)
            ->get('/estoque/historico.csv')
            ->assertForbidden();
    }
}
