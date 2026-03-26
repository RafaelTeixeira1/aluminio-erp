<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\QuoteService;
use App\Services\SaleService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_sale_decreases_stock_and_creates_movement(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'phone' => '11999999999',
        ]);

        $item = CatalogItem::query()->create([
            'name' => 'Vidro Incolor 8mm',
            'item_type' => 'produto',
            'price' => 100,
            'stock' => 10,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
        ]);

        $sale->items()->create([
            'catalog_item_id' => $item->id,
            'item_name' => $item->name,
            'item_type' => $item->item_type,
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
        ]);

        /** @var SaleService $service */
        $service = app(SaleService::class);
        $confirmed = $service->confirmSale($sale, null);

        $this->assertSame('confirmada', $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
        $this->assertEquals(7.0, (float) $item->fresh()->stock);

        $movement = StockMovement::query()->where('origin_type', 'venda')->where('origin_id', $sale->id)->first();
        $this->assertNotNull($movement);
        $this->assertSame('saida', $movement->movement_type);
        $this->assertEquals(3.0, (float) $movement->quantity);
        $this->assertEquals(10.0, (float) $movement->stock_before);
        $this->assertEquals(7.0, (float) $movement->stock_after);

        $receivable = Receivable::query()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($receivable);
        $this->assertSame('aberto', $receivable->status);
        $this->assertEquals(300.0, (float) $receivable->amount_total);
        $this->assertEquals(300.0, (float) $receivable->balance);
    }

    public function test_confirm_sale_with_insufficient_stock_throws_exception(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Estoque',
            'phone' => '11888888888',
        ]);

        $item = CatalogItem::query()->create([
            'name' => 'Vidro Fume 10mm',
            'item_type' => 'produto',
            'price' => 120,
            'stock' => 2,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 360,
            'discount' => 0,
            'total' => 360,
        ]);

        $sale->items()->create([
            'catalog_item_id' => $item->id,
            'item_name' => $item->name,
            'item_type' => $item->item_type,
            'quantity' => 3,
            'unit_price' => 120,
            'line_total' => 360,
        ]);

        /** @var SaleService $service */
        $service = app(SaleService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Estoque insuficiente. Operacao cancelada.');

        $service->confirmSale($sale, null);
    }

    public function test_expire_overdue_quotes_updates_only_allowed_statuses(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Orcamento',
            'phone' => '11777777777',
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aprovado',
            'valid_until' => now()->subDays(2)->toDateString(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'cancelado',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'valid_until' => now()->addDay()->toDateString(),
        ]);

        /** @var QuoteService $service */
        $service = app(QuoteService::class);
        $affected = $service->expireOverdueQuotes();

        $this->assertSame(2, $affected);
        $this->assertSame(2, Quote::query()->where('status', 'expirado')->count());
        $this->assertSame(1, Quote::query()->where('status', 'cancelado')->count());
        $this->assertSame(1, Quote::query()->where('status', 'aberto')->count());
    }

    public function test_quotes_expire_command_runs_successfully(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Console',
            'phone' => '11666666666',
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('quotes:expire')
            ->expectsOutput('Orcamentos expirados: 1')
            ->assertExitCode(0);
    }
}
