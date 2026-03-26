<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DesignSketch;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDesignSketchesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_can_create_and_update_sketch(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Desenho',
            'phone' => '11997773344',
            'email' => 'cliente.desenho@example.com',
        ]);

        $quote = Quote::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $vendedor->id,
            'status' => 'aberto',
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
        ]);

        $canvasJson = json_encode([
            'width' => 1200,
            'height' => 650,
            'shapes' => [
                ['type' => 'rect', 'start' => ['x' => 100, 'y' => 100], 'end' => ['x' => 500, 'y' => 400], 'color' => '#111111', 'lineWidth' => 2],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($vendedor)
            ->post(route('designSketches.store'), [
                'title' => 'Janela sala principal',
                'quote_id' => $quote->id,
                'width_mm' => 1200,
                'height_mm' => 1000,
                'canvas_json' => $canvasJson,
                'preview_png' => 'data:image/png;base64,abc123',
                'notes' => 'Janela de correr 2 folhas',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Desenho salvo com sucesso!');

        $sketch = DesignSketch::query()->firstOrFail();
        $this->assertSame('Janela sala principal', $sketch->title);
        $this->assertSame($quote->id, $sketch->quote_id);

        $this->actingAs($vendedor)
            ->put(route('designSketches.update', $sketch), [
                'title' => 'Janela sala principal revisada',
                'quote_id' => $quote->id,
                'width_mm' => 1300,
                'height_mm' => 1000,
                'canvas_json' => $canvasJson,
                'preview_png' => 'data:image/png;base64,xyz987',
                'notes' => 'Revisado com trilho reforcado',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Desenho atualizado com sucesso!');

        $sketch->refresh();
        $this->assertSame('Janela sala principal revisada', $sketch->title);
        $this->assertEquals(1300.0, (float) $sketch->width_mm);
        $this->assertSame('Revisado com trilho reforcado', $sketch->notes);

        $this->actingAs($vendedor)
            ->get(route('designSketches.index', ['search' => 'revisada']))
            ->assertOk()
            ->assertSee('Desenhos de Esquadrias')
            ->assertSee('Janela sala principal revisada');
    }

    public function test_estoquista_cannot_access_sketches(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $this->actingAs($estoquista)
            ->get(route('designSketches.index'))
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->get(route('designSketches.create'))
            ->assertForbidden();
    }
}
