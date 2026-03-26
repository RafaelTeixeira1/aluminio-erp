<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CatalogItem;
use App\Models\Category;
use App\Models\Payable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_supplier_purchase_and_receive_into_stock_with_payable(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $headers = $this->authHeader($admin);

        $supplierResponse = $this->postJson('/api/fornecedores', [
            'name' => 'Fornecedor Alpha',
            'document' => '12.345.678/0001-99',
            'phone' => '1133334444',
        ], $headers);

        $supplierResponse->assertStatus(201);
        $supplierId = (int) $supplierResponse->json('id');

        $category = Category::query()->create([
            'name' => 'Perfis Compra',
            'active' => true,
        ]);

        $product = CatalogItem::query()->create([
            'name' => 'Perfil 60x30',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 100,
            'stock' => 10,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        $purchaseResponse = $this->postJson('/api/compras', [
            'supplier_id' => $supplierId,
            'payment_due_date' => now()->addDays(10)->toDateString(),
            'items' => [
                [
                    'catalog_item_id' => $product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 55,
                ],
            ],
        ], $headers);

        $purchaseResponse
            ->assertStatus(201)
            ->assertJsonPath('status', 'aberto');

        $this->assertEquals(275.0, (float) $purchaseResponse->json('total'));

        $purchaseId = (int) $purchaseResponse->json('id');
        $itemId = (int) $purchaseResponse->json('items.0.id');

        $receiveResponse = $this->postJson('/api/compras/'.$purchaseId.'/itens/'.$itemId.'/receber', [
            'quantity' => 5,
            'notes' => 'Recebimento total',
        ], $headers);

        $receiveResponse
            ->assertOk()
            ->assertJsonPath('status', 'recebido');

        $this->assertEquals(5.0, (float) $receiveResponse->json('items.0.quantity_received'));

        $product->refresh();
        $this->assertEquals(15.0, (float) $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'catalog_item_id' => $product->id,
            'movement_type' => 'entrada',
            'origin_type' => 'compra',
            'origin_id' => $purchaseId,
        ]);

        $payable = Payable::query()->where('purchase_order_id', $purchaseId)->first();
        $this->assertNotNull($payable);
        $this->assertEquals(275.0, (float) $payable->amount_total);
        $this->assertSame($supplierId, $payable->supplier_id);
    }

    public function test_cannot_receive_more_than_pending_quantity(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $headers = $this->authHeader($admin);

        $supplierId = (int) $this->postJson('/api/fornecedores', [
            'name' => 'Fornecedor Beta',
        ], $headers)->json('id');

        $category = Category::query()->create([
            'name' => 'Categoria Beta',
            'active' => true,
        ]);

        $product = CatalogItem::query()->create([
            'name' => 'Barra 6m',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 80,
            'stock' => 0,
            'stock_minimum' => 0,
            'is_active' => true,
        ]);

        $purchase = $this->postJson('/api/compras', [
            'supplier_id' => $supplierId,
            'items' => [
                [
                    'catalog_item_id' => $product->id,
                    'quantity_ordered' => 2,
                    'unit_cost' => 30,
                ],
            ],
        ], $headers);

        $purchaseId = (int) $purchase->json('id');
        $itemId = (int) $purchase->json('items.0.id');

        $this->postJson('/api/compras/'.$purchaseId.'/itens/'.$itemId.'/receber', [
            'quantity' => 3,
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Quantidade recebida maior que o saldo pendente do item.');
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
