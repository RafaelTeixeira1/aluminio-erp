<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DesignSketch;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebQuotePrintLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_can_open_quote_print_preview_with_logo(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Impressao',
            'phone' => '11999999999',
            'email' => 'cliente@teste.com',
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $vendedor->id,
            'status' => 'aberto',
            'subtotal' => 200,
            'discount' => 10,
            'total' => 190,
            'valid_until' => now()->addDays(10),
            'notes' => 'Entregar em 15 dias.',
        ]);

        QuoteItem::query()->create([
            'quote_id' => $quote->id,
            'item_name' => 'Perfil 20x20',
            'item_type' => 'perfil',
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        DesignSketch::query()->create([
            'quote_id' => $quote->id,
            'created_by_user_id' => $vendedor->id,
            'title' => 'Janela fachada',
            'width_mm' => 1200,
            'height_mm' => 1000,
            'canvas_json' => '{"objects":[]}',
            'preview_png' => 'data:image/png;base64,abc123',
            'notes' => 'Com travessa central',
        ]);

        $response = $this->actingAs($vendedor)->get(route('quotes.printPreview', $quote));

        $response->assertOk();
        $response->assertSeeText('ORCAMENTO');
        $response->assertSeeText('Cliente Impressao');
        $response->assertSeeText('Desenhos Integrados do Orcamento');
        $response->assertSeeText('Janela fachada');
        $content = $response->getContent() ?? '';
        $this->assertTrue(
            str_contains($content, 'data:image/png;base64')
            || str_contains($content, 'data:image/jpeg;base64')
            || str_contains($content, 'data:image/svg+xml;base64')
        );
    }

    public function test_estoquista_cannot_open_quote_print_preview(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Bloqueio',
            'phone' => '11888888888',
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
        ]);

        $this->actingAs($estoquista)
            ->get(route('quotes.printPreview', $quote))
            ->assertForbidden();
    }
}
