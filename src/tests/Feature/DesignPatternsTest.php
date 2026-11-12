<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\QuoteService;
use App\Services\SaleFacade;
use App\States\Quote\InvalidQuoteTransition;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre os tres padroes de projeto aplicados:
 * Factory Method (itens do orcamento), State (situacao do orcamento) e Facade (vendas).
 */
class DesignPatternsTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- Factory Method

    public function test_glass_item_is_priced_by_square_meter(): void
    {
        $glass = $this->product('Vidro Incolor 8mm', 'm2', 200);
        $quote = $this->openQuote();

        $quote = app(QuoteService::class)->replaceItems($quote, [[
            'catalog_item_id' => $glass->id,
            'quantity' => 2,
            'width_mm' => 1000,
            'height_mm' => 500,
        ]]);

        $item = $quote->items->firstOrFail();
        // 2 pecas x 0,5 m2 x R$ 200,00
        $this->assertEquals(200.00, (float) $item->line_total);
        $this->assertEquals(1.0, (float) $item->metadata['area_m2']);
        $this->assertEquals(200.00, (float) $quote->total);
    }

    public function test_glass_item_requires_measures(): void
    {
        $glass = $this->product('Vidro Verde 6mm', 'm2', 180);
        $quote = $this->openQuote();

        $this->expectException(DomainException::class);

        app(QuoteService::class)->replaceItems($quote, [[
            'catalog_item_id' => $glass->id,
            'quantity' => 1,
        ]]);
    }

    public function test_profile_item_is_priced_by_whole_bar(): void
    {
        $profile = $this->product('Perfil Linha 25', 'barra', 150);
        $quote = $this->openQuote();

        $quote = app(QuoteService::class)->replaceItems($quote, [[
            'catalog_item_id' => $profile->id,
            'quantity' => 3,
        ]]);

        $item = $quote->items->firstOrFail();
        $this->assertEquals(450.00, (float) $item->line_total);
        $this->assertEquals(18.0, (float) $item->metadata['total_length_m']);
    }

    public function test_profile_item_rejects_fractional_bars(): void
    {
        $profile = $this->product('Perfil Linha 30', 'barra', 150);
        $quote = $this->openQuote();

        $this->expectException(DomainException::class);

        app(QuoteService::class)->replaceItems($quote, [[
            'catalog_item_id' => $profile->id,
            'quantity' => 2.5,
        ]]);
    }

    public function test_accessory_item_is_priced_by_unit_or_package(): void
    {
        $screws = $this->product('Parafuso 4,2 (pacote com 100)', 'pacote', 10);
        $handle = $this->product('Puxador', 'unidade', 35);
        $quote = $this->openQuote();

        $quote = app(QuoteService::class)->replaceItems($quote, [
            ['catalog_item_id' => $screws->id, 'quantity' => 4],
            ['catalog_item_id' => $handle->id, 'quantity' => 2],
        ]);

        $totals = $quote->items->pluck('line_total')->map(fn ($value) => (float) $value)->sort()->values()->all();
        $this->assertSame([40.0, 70.0], $totals);
        $this->assertEquals(110.00, (float) $quote->total);
    }

    // ---------------------------------------------------------------- State

    public function test_cancelled_quote_cannot_be_converted(): void
    {
        $admin = $this->user('admin');
        $quote = $this->openQuote();
        $quote->update(['status' => 'cancelado']);

        $this->postJson('/api/orcamentos/'.$quote->id.'/converter', [], $this->authHeader($admin))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Orcamento com status "cancelado" nao pode ser convertido em venda.');

        $this->assertSame(0, Sale::query()->where('quote_id', $quote->id)->count());
    }

    public function test_quote_past_validity_cannot_be_converted(): void
    {
        $admin = $this->user('admin');
        $quote = $this->openQuote();
        $quote->update(['valid_until' => now()->subDays(3)->toDateString()]);

        $this->postJson('/api/orcamentos/'.$quote->id.'/converter', [], $this->authHeader($admin))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Orcamento vencido. Atualize a validade antes de converter em venda.');
    }

    public function test_converted_quote_items_cannot_be_changed(): void
    {
        $admin = $this->user('admin');
        $quote = $this->openQuote();
        $headers = $this->authHeader($admin);

        $this->postJson('/api/orcamentos/'.$quote->id.'/converter', [], $headers)->assertStatus(201);

        $this->putJson('/api/orcamentos/'.$quote->id.'/itens', [
            'items' => [['item_name' => 'Item novo', 'quantity' => 1, 'unit_price' => 10]],
        ], $headers)->assertStatus(422);

        $this->assertSame(1, $quote->items()->count());
    }

    public function test_status_cannot_be_set_to_convertido_without_a_sale(): void
    {
        $admin = $this->user('admin');
        $quote = $this->openQuote();

        $this->putJson('/api/orcamentos/'.$quote->id, ['status' => 'convertido'], $this->authHeader($admin))
            ->assertStatus(422);

        $this->assertSame('aberto', $quote->fresh()->status);
    }

    public function test_quote_states_follow_allowed_transitions(): void
    {
        $quote = $this->openQuote();

        $quote->transitionTo('aprovado');
        $this->assertSame('aprovado', $quote->fresh()->status);

        $quote->transitionTo('aberto');
        $this->assertSame('aberto', $quote->fresh()->status);

        $quote->transitionTo('cancelado');
        $this->assertSame('cancelado', $quote->fresh()->status);

        $this->expectException(InvalidQuoteTransition::class);
        $quote->transitionTo('aberto');
    }

    // ---------------------------------------------------------------- Facade

    public function test_cancelling_confirmed_sale_restores_stock_and_cancels_receivables(): void
    {
        [$sale, $product] = $this->pendingSale(quantity: 3, stock: 10);
        $facade = app(SaleFacade::class);

        $facade->confirmarVenda($sale, null);
        $this->assertEquals(7.0, (float) $product->fresh()->stock);

        $cancelled = $facade->cancelarVenda($sale->fresh(), null, 'Cliente desistiu');

        $this->assertSame('cancelada', $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertSame('Cliente desistiu', $cancelled->cancellation_reason);
        $this->assertEquals(10.0, (float) $product->fresh()->stock);
        $this->assertTrue(
            StockMovement::query()->where('origin_type', 'venda_cancelada')->where('origin_id', $sale->id)->exists()
        );
        $this->assertSame(0, Receivable::query()->where('sale_id', $sale->id)->where('status', '!=', 'cancelado')->count());
    }

    public function test_sale_with_payment_cannot_be_cancelled(): void
    {
        [$sale, $product] = $this->pendingSale(quantity: 2, stock: 10);
        $facade = app(SaleFacade::class);
        $facade->confirmarVenda($sale, null);

        Receivable::query()->where('sale_id', $sale->id)->update([
            'amount_paid' => 50,
            'status' => 'parcial',
        ]);

        try {
            $facade->cancelarVenda($sale->fresh(), null);
            $this->fail('A venda com recebimento nao deveria ser cancelada.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('recebimento registrado', $exception->getMessage());
        }

        $this->assertSame('confirmada', $sale->fresh()->status);
        $this->assertEquals(8.0, (float) $product->fresh()->stock);
    }

    public function test_pending_sale_cancellation_does_not_move_stock(): void
    {
        [$sale, $product] = $this->pendingSale(quantity: 2, stock: 10);

        app(SaleFacade::class)->cancelarVenda($sale, null);

        $this->assertSame('cancelada', $sale->fresh()->status);
        $this->assertEquals(10.0, (float) $product->fresh()->stock);
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_cancelled_sale_cannot_be_confirmed(): void
    {
        [$sale] = $this->pendingSale(quantity: 1, stock: 10);
        $facade = app(SaleFacade::class);
        $facade->cancelarVenda($sale, null);

        $this->expectException(DomainException::class);
        $facade->confirmarVenda($sale->fresh(), null);
    }

    public function test_vendedor_cannot_cancel_sale_through_api(): void
    {
        [$sale] = $this->pendingSale(quantity: 1, stock: 10);

        $this->postJson('/api/vendas/'.$sale->id.'/cancelar', [], $this->authHeader($this->user('vendedor')))
            ->assertForbidden();

        $this->assertSame('pendente', $sale->fresh()->status);
    }

    public function test_admin_can_cancel_sale_through_api(): void
    {
        [$sale] = $this->pendingSale(quantity: 1, stock: 10);

        $this->postJson('/api/vendas/'.$sale->id.'/cancelar', ['reason' => 'Erro de digitacao'], $this->authHeader($this->user('admin')))
            ->assertOk()
            ->assertJsonPath('status', 'cancelada')
            ->assertJsonPath('cancellation_reason', 'Erro de digitacao');
    }

    // ---------------------------------------------------------------- Helpers

    private function product(string $name, string $saleUnit, float $price, float $stock = 100): CatalogItem
    {
        return CatalogItem::query()->create([
            'name' => $name,
            'item_type' => $saleUnit === 'pacote' || $saleUnit === 'unidade' ? 'acessorio' : 'produto',
            'sale_unit' => $saleUnit,
            'price' => $price,
            'stock' => $stock,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);
    }

    private function openQuote(): Quote
    {
        $client = Client::query()->create([
            'name' => 'Cliente Padroes',
            'phone' => '11911112222',
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'valid_until' => now()->addDays(10)->toDateString(),
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'item_name' => 'Item inicial',
            'item_type' => 'acessorio',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        return $quote;
    }

    /**
     * @return array{0: Sale, 1: CatalogItem}
     */
    private function pendingSale(float $quantity, float $stock): array
    {
        $client = Client::query()->create([
            'name' => 'Cliente Venda',
            'phone' => '11933334444',
        ]);

        $product = $this->product('Puxador inox', 'unidade', 50, $stock);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => $quantity * 50,
            'discount' => 0,
            'total' => $quantity * 50,
        ]);

        $sale->items()->create([
            'catalog_item_id' => $product->id,
            'item_name' => $product->name,
            'item_type' => $product->item_type,
            'quantity' => $quantity,
            'unit_price' => 50,
            'line_total' => $quantity * 50,
        ]);

        return [$sale, $product];
    }

    private function user(string $profile): User
    {
        return User::factory()->create([
            'profile' => $profile,
            'active' => true,
        ]);
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
