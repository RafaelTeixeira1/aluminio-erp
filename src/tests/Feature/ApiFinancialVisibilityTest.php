<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CatalogItem;
use App\Models\Category;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiFinancialVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_list_and_show_endpoints_hide_financial_fields(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        [$sale, $quote] = $this->createSaleAndQuote($vendedor);
        $headers = $this->authHeader($vendedor);

        $salesIndex = $this->getJson('/api/vendas', $headers);
        $salesIndex
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('data.0.total', null)
            ->assertJsonPath('data.0.subtotal', null)
            ->assertJsonPath('data.0.discount', null)
            ->assertJsonPath('data.0.items.0.unit_price', null)
            ->assertJsonPath('data.0.items.0.line_total', null);

        $salesShow = $this->getJson('/api/vendas/'.$sale->id, $headers);
        $salesShow
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('total', null)
            ->assertJsonPath('subtotal', null)
            ->assertJsonPath('discount', null)
            ->assertJsonPath('items.0.unit_price', null)
            ->assertJsonPath('items.0.line_total', null);

        $quotesIndex = $this->getJson('/api/orcamentos', $headers);
        $quotesIndex
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('data.0.total', null)
            ->assertJsonPath('data.0.subtotal', null)
            ->assertJsonPath('data.0.discount', null)
            ->assertJsonPath('data.0.items.0.unit_price', null)
            ->assertJsonPath('data.0.items.0.line_total', null);

        $quotesShow = $this->getJson('/api/orcamentos/'.$quote->id, $headers);
        $quotesShow
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('total', null)
            ->assertJsonPath('subtotal', null)
            ->assertJsonPath('discount', null)
            ->assertJsonPath('items.0.unit_price', null)
            ->assertJsonPath('items.0.line_total', null);
    }

    public function test_admin_still_receives_financial_fields(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        [$sale, $quote] = $this->createSaleAndQuote($admin);
        $headers = $this->authHeader($admin);

        $salesShow = $this->getJson('/api/vendas/'.$sale->id, $headers);
        $salesShow
            ->assertOk()
            ->assertJsonPath('can_view_financial', true);
        $this->assertNotNull($salesShow->json('total'));
        $this->assertNotNull($salesShow->json('items.0.unit_price'));

        $quotesShow = $this->getJson('/api/orcamentos/'.$quote->id, $headers);
        $quotesShow
            ->assertOk()
            ->assertJsonPath('can_view_financial', true);
        $this->assertNotNull($quotesShow->json('total'));
        $this->assertNotNull($quotesShow->json('items.0.unit_price'));
    }

    public function test_vendedor_product_endpoints_hide_price_field(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Perfis Financeiros',
            'active' => true,
        ]);

        $product = CatalogItem::query()->create([
            'name' => 'Produto API Preco',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 149.90,
            'stock' => 20,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        $headers = $this->authHeader($vendedor);

        $index = $this->getJson('/api/produtos', $headers);
        $index
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('data.0.price', null);

        $show = $this->getJson('/api/produtos/'.$product->id, $headers);
        $show
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('price', null);
    }

    public function test_vendedor_client_history_hides_sale_and_quote_totals(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Historico Financeiro',
            'phone' => '11955556666',
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 90,
            'discount' => 10,
            'total' => 80,
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 120,
            'discount' => 0,
            'total' => 120,
            'valid_until' => now()->addDays(3),
        ]);

        $response = $this->getJson('/api/clientes/'.$client->id.'/historico', $this->authHeader($vendedor));

        $response
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('sales.0.total', null)
            ->assertJsonPath('quotes.0.total', null);
    }

    public function test_vendedor_low_stock_report_hides_product_prices(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Categoria Estoque Baixo',
            'active' => true,
        ]);

        CatalogItem::query()->create([
            'name' => 'Item Critico',
            'category_id' => $category->id,
            'item_type' => 'produto',
            'price' => 77.7,
            'stock' => 1,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/relatorios/estoque-baixo', $this->authHeader($vendedor));

        $response
            ->assertOk()
            ->assertJsonPath('can_view_financial', false)
            ->assertJsonPath('items.0.price', null);
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

    /**
     * @return array{0: Sale, 1: Quote}
     */
    private function createSaleAndQuote(User $user): array
    {
        $client = Client::query()->create([
            'name' => 'Cliente API Financeiro',
            'phone' => '11999998888',
            'email' => 'financeiro.api@example.com',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'created_by_user_id' => $user->id,
            'subtotal' => 200,
            'discount' => 10,
            'total' => 190,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'item_name' => 'Perfil API Venda',
            'item_type' => 'produto',
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'created_by_user_id' => $user->id,
            'subtotal' => 120,
            'discount' => 0,
            'total' => 120,
            'valid_until' => now()->addDays(7),
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'item_name' => 'Perfil API Orcamento',
            'item_type' => 'produto',
            'quantity' => 3,
            'unit_price' => 40,
            'line_total' => 120,
        ]);

        return [$sale, $quote];
    }
}
