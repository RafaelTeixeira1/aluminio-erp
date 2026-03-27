<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Category;
use App\Models\DocumentSequence;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPurchaseOrdersFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_cancel_purchase_order_via_web(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        DocumentSequence::query()->create([
            'code' => 'PO_COMPRA',
            'description' => 'Pedidos de Compra',
            'prefix' => 'PC-',
            'pattern' => 'P%06d',
            'next_number' => 1,
            'reset_frequency' => 'never',
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->create([
            'name' => 'Fornecedor Ordem',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Categoria Compra Web',
            'active' => true,
        ]);

        $catalogItem = CatalogItem::query()->create([
            'name' => 'Perfil Web 80x40',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 90,
            'stock' => 5,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $storeResponse = $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'delivery_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Pedido de compra de teste web',
            'items' => [
                [
                    'catalog_item_id' => $catalogItem->id,
                    'quantity' => 3,
                    'unit_cost' => 45,
                ],
            ],
        ]);

        $storeResponse
            ->assertRedirect(route('purchase-orders.index'))
            ->assertSessionHas('success', 'Pedido de compra criado com sucesso!');

        $purchaseOrder = PurchaseOrder::query()->with('items')->latest('id')->firstOrFail();

        $this->assertSame('aberto', (string) $purchaseOrder->status);
        $this->assertNotNull($purchaseOrder->order_number);
        $this->assertCount(1, $purchaseOrder->items);

        $this->actingAs($admin)
            ->get(route('purchase-orders.index', ['search' => $purchaseOrder->order_number]))
            ->assertOk()
            ->assertSeeText((string) $purchaseOrder->order_number);

        $cancelResponse = $this->actingAs($admin)->patch(route('purchase-orders.cancel', $purchaseOrder));

        $cancelResponse
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder))
            ->assertSessionHas('success', 'Pedido cancelado com sucesso!');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'cancelado',
        ]);
    }

    public function test_vendedor_cannot_access_purchase_orders_web_pages(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)
            ->get(route('purchase-orders.index'))
            ->assertForbidden()
            ->assertSeeText('Acesso negado');
    }
}
