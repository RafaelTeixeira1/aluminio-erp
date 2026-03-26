<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebQuoteListToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotes_index_respects_total_sorting(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Ordenacao',
            'phone' => '11999998888',
            'email' => 'cliente.ordenacao@example.com',
        ]);

        $quoteA = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 500,
            'discount' => 0,
            'total' => 500,
        ]);

        $quoteB = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 120,
            'discount' => 0,
            'total' => 120,
        ]);

        $quoteC = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
        ]);

        $response = $this->actingAs($vendedor)->get(route('quotes.index', [
            'sort_by' => 'total',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();

        $quotes = $response->viewData('quotes');
        $orderedIds = $quotes->pluck('id')->values()->all();

        $this->assertSame([$quoteB->id, $quoteC->id, $quoteA->id], $orderedIds);
    }

    public function test_export_csv_respects_filters_and_ordering(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente CSV',
            'phone' => '11888887777',
            'email' => 'cliente.csv@example.com',
        ]);

        $openHigh = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 800,
            'discount' => 0,
            'total' => 800,
        ]);

        Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'convertido',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
        ]);

        $openLow = Quote::query()->create([
            'client_id' => $client->id,
            'status' => 'aberto',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($vendedor)->get(route('quotes.exportCsv', [
            'status' => 'aberto',
            'sort_by' => 'total',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Numero;Cliente;Status;Validade;Subtotal;Desconto;Total;"Criado em"', $csv);
        $this->assertStringContainsString((string) $openLow->id.';"Cliente CSV";aberto', $csv);
        $this->assertStringContainsString((string) $openHigh->id.';"Cliente CSV";aberto', $csv);
        $this->assertStringNotContainsString(';convertido;', $csv);

        $firstPos = strpos($csv, (string) $openLow->id.';"Cliente CSV";aberto');
        $secondPos = strpos($csv, (string) $openHigh->id.';"Cliente CSV";aberto');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertTrue($firstPos < $secondPos);
    }
}
