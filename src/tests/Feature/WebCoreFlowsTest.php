<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\CatalogItemImage;
use App\Models\Category;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebCoreFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_can_create_update_and_delete_client_via_web(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $createResponse = $this->actingAs($vendedor)->post(route('clients.store'), [
            'name' => 'Cliente Fluxo',
            'phone' => '(11) 99999-0000',
            'document' => '12345678900',
            'email' => 'cliente.fluxo@example.com',
            'address' => 'Rua A, 100',
        ]);

        $createResponse
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', 'Cliente criado com sucesso!');

        $client = Client::query()->where('email', 'cliente.fluxo@example.com')->firstOrFail();

        $updateResponse = $this->actingAs($vendedor)->put(route('clients.update', $client), [
            'name' => 'Cliente Fluxo Atualizado',
            'phone' => '(11) 98888-0000',
            'document' => '12345678900',
            'email' => 'cliente.fluxo@example.com',
            'address' => 'Rua B, 200',
        ]);

        $updateResponse
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', 'Cliente atualizado com sucesso!');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Fluxo Atualizado',
            'phone' => '(11) 98888-0000',
        ]);

        $deleteResponse = $this->actingAs($vendedor)->delete(route('clients.destroy', $client));

        $deleteResponse
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', 'Cliente removido com sucesso!');

        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_admin_can_create_product_with_image_via_web(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Perfis',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'SU 225',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 120.50,
            'stock' => 100,
            'stock_minimum' => 10,
            'is_active' => 1,
            'image' => UploadedFile::fake()->create('su-225.jpg', 20, 'image/jpeg'),
        ]);

        $response
            ->assertRedirect(route('products.crud'))
            ->assertSessionHas('success', 'Produto criado com sucesso!');

        $product = CatalogItem::query()->where('name', 'SU 225')->firstOrFail();

        $this->assertNotNull($product->image_path);
        $this->assertStringStartsWith('storage/products/', (string) $product->image_path);

        $storedPath = str_replace('storage/', '', (string) $product->image_path);
        $this->assertTrue(Storage::disk('public')->exists($storedPath));
    }

    public function test_admin_can_register_product_gallery_images_with_kind_and_primary(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Acessorios',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Roldana A1',
            'category_id' => $category->id,
            'item_type' => 'acessorio',
            'price' => 35.20,
            'stock' => 50,
            'stock_minimum' => 5,
            'is_active' => 1,
            'gallery_kind' => 'roldana',
            'gallery_images' => [
                UploadedFile::fake()->create('roldana-1.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('roldana-2.jpg', 22, 'image/jpeg'),
            ],
        ]);

        $response->assertRedirect(route('products.crud'));

        $product = CatalogItem::query()->where('name', 'Roldana A1')->firstOrFail();

        $images = CatalogItemImage::query()
            ->where('catalog_item_id', $product->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $images);
        $this->assertSame('roldana', $images[0]->image_kind);
        $this->assertTrue((bool) $images[0]->is_primary);

        $this->assertTrue(Storage::disk('public')->exists(str_replace('storage/', '', (string) $images[0]->image_path)));
        $this->assertTrue(Storage::disk('public')->exists(str_replace('storage/', '', (string) $images[1]->image_path)));

        $this->assertSame($images[0]->image_path, $product->fresh()->image_path);
    }

    public function test_vendedor_can_create_quick_quote_without_client_and_uses_catalog_data(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Esquadrias',
            'active' => true,
        ]);

        $product = CatalogItem::query()->create([
            'name' => 'Perfil 40x20',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 75.90,
            'stock' => 200,
            'stock_minimum' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($vendedor)->post(route('quotes.store'), [
            'client_id' => null,
            'status' => 'aberto',
            'discount' => 0,
            'items' => [
                [
                    'catalog_item_id' => $product->id,
                    'item_name' => 'Tentativa Manual',
                    'item_type' => 'acessorio',
                    'quantity' => 2,
                    'unit_price' => 1.23,
                ],
            ],
        ]);

        $response
            ->assertRedirect(route('quotes.index'))
            ->assertSessionHas('success', 'Orcamento criado com sucesso!');

        $quote = Quote::query()->with('items')->latest('id')->firstOrFail();

        $this->assertNull($quote->client_id);
        $this->assertCount(1, $quote->items);

        $item = $quote->items->firstOrFail();
        $this->assertSame($product->id, $item->catalog_item_id);
        $this->assertSame($product->name, $item->item_name);
        $this->assertSame($product->item_type, $item->item_type);
        $this->assertEquals((float) $product->price, (float) $item->unit_price);
        $this->assertEquals(151.80, round((float) $item->line_total, 2));
    }
}
