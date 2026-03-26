<?php

namespace Tests\Feature;

use App\Mail\QuoteEmail;
use App\Models\Client;
use App\Models\DesignSketch;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebQuoteActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_can_send_quote_email(): void
    {
        Mail::fake();
        Storage::fake('local');

        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $quote = $this->createQuote();

        $response = $this->actingAs($vendedor)->post(route('quotes.sendEmail', $quote), [
            'email' => 'destinatario@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Orçamento enviado por email com sucesso!');

        Mail::assertSent(QuoteEmail::class, function (QuoteEmail $mail) {
            return $mail->hasTo('destinatario@example.com');
        });

        $this->assertTrue(Storage::disk('local')->exists('temp/quote-'.$quote->id.'.pdf'));
    }

    public function test_vendedor_can_duplicate_quote_with_items_and_design_sketches(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $quote = $this->createQuote();
        DesignSketch::query()->create([
            'quote_id' => $quote->id,
            'created_by_user_id' => $vendedor->id,
            'title' => 'Esquadra frontal',
            'width_mm' => 600,
            'height_mm' => 1200,
            'canvas_json' => '{"type":"legacy"}',
            'preview_png' => 'data:image/png;base64,abc123',
            'notes' => 'Desenho base para corte',
        ]);

        $response = $this->actingAs($vendedor)->post(route('quotes.duplicate', $quote));

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Orcamento duplicado com sucesso!');

        $newQuote = Quote::query()->latest('id')->firstOrFail();
        $this->assertNotSame($quote->id, $newQuote->id);

        $newQuote->load(['items', 'designSketches']);

        $this->assertSame('aberto', $newQuote->status);
        $this->assertSame($quote->client_id, $newQuote->client_id);
        $this->assertCount(1, $newQuote->items);
        $this->assertCount(1, $newQuote->designSketches);
        $this->assertSame('Esquadra frontal', $newQuote->designSketches->firstOrFail()->title);

        $newItem = $newQuote->items->firstOrFail();
        $originalItem = $quote->items()->firstOrFail();

        $this->assertSame($originalItem->item_name, $newItem->item_name);
        $this->assertEquals((float) $originalItem->quantity, (float) $newItem->quantity);
        $this->assertEquals((float) $originalItem->unit_price, (float) $newItem->unit_price);
        $this->assertEquals((float) $originalItem->line_total, (float) $newItem->line_total);
    }

    public function test_vendedor_can_convert_quote_to_sale_and_marks_quote_as_convertido(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $quote = $this->createQuote();

        $response = $this->actingAs($vendedor)->post(route('quotes.convert', $quote));

        $response
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('success', 'Orcamento convertido em venda!');

        $quote->refresh();
        $this->assertSame('convertido', $quote->status);

        $sale = Sale::query()
            ->where('quote_id', $quote->id)
            ->with('items')
            ->firstOrFail();

        $this->assertSame($quote->client_id, $sale->client_id);
        $this->assertSame($vendedor->id, $sale->created_by_user_id);
        $this->assertCount(1, $sale->items);
        $this->assertEquals((float) $quote->total, (float) $sale->total);

        $saleItem = $sale->items->firstOrFail();
        $quoteItem = $quote->items()->firstOrFail();

        $this->assertSame($quoteItem->item_name, $saleItem->item_name);
        $this->assertEquals((float) $quoteItem->quantity, (float) $saleItem->quantity);
        $this->assertEquals((float) $quoteItem->unit_price, (float) $saleItem->unit_price);
    }

    public function test_vendedor_cannot_convert_same_quote_twice(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $quote = $this->createQuote();

        $this->actingAs($vendedor)
            ->post(route('quotes.convert', $quote))
            ->assertRedirect(route('sales.index'));

        $this->actingAs($vendedor)
            ->post(route('quotes.convert', $quote))
            ->assertSessionHasErrors('quote');

        $this->assertSame(1, Sale::query()->where('quote_id', $quote->id)->count());
    }

    public function test_estoquista_cannot_send_email_duplicate_or_convert_quote(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $quote = $this->createQuote();

        $this->actingAs($estoquista)
            ->post(route('quotes.sendEmail', $quote), ['email' => 'destinatario@example.com'])
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->post(route('quotes.duplicate', $quote))
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->post(route('quotes.convert', $quote))
            ->assertForbidden();
    }

    private function createQuote(): Quote
    {
        $client = Client::query()->create([
            'name' => 'Cliente Teste Acoes',
            'phone' => '11999990000',
            'email' => 'cliente.acoes@example.com',
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 300,
            'discount' => 20,
            'total' => 280,
            'valid_until' => now()->addDays(7),
            'notes' => 'Observacao de teste',
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'item_name' => 'Perfil 40x20',
            'item_type' => 'perfil',
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
            'metadata' => ['bnf' => 'A1'],
        ]);

        return $quote;
    }
}