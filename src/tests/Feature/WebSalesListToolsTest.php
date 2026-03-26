<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSalesListToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_index_respects_total_sorting(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Ordenacao Vendas',
            'phone' => '11999997777',
            'email' => 'cliente.vendas.ordenacao@example.com',
        ]);

        $saleA = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 900,
            'discount' => 0,
            'total' => 900,
        ]);

        $saleB = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 150,
            'discount' => 0,
            'total' => 150,
        ]);

        $saleC = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 420,
            'discount' => 0,
            'total' => 420,
        ]);

        $response = $this->actingAs($vendedor)->get(route('sales.index', [
            'sort_by' => 'total',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();

        $sales = $response->viewData('sales');
        $orderedIds = $sales->pluck('id')->values()->all();

        $this->assertSame([$saleB->id, $saleC->id, $saleA->id], $orderedIds);
    }

    public function test_sales_export_csv_respects_filters_and_ordering(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente CSV Vendas',
            'phone' => '11888886666',
            'email' => 'cliente.vendas.csv@example.com',
        ]);

        $pendingHigh = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
        ]);

        Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'confirmada',
            'subtotal' => 400,
            'discount' => 0,
            'total' => 400,
        ]);

        $pendingLow = Sale::query()->create([
            'client_id' => $client->id,
            'status' => 'pendente',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
        ]);

        $response = $this->actingAs($vendedor)->get(route('sales.exportCsv', [
            'status' => 'pendente',
            'sort_by' => 'total',
            'sort_dir' => 'asc',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Numero;Cliente;Status;Itens;Subtotal;Desconto;Total;"Criado em"', $csv);
        $this->assertStringContainsString((string) $pendingLow->id.';"Cliente CSV Vendas";pendente', $csv);
        $this->assertStringContainsString((string) $pendingHigh->id.';"Cliente CSV Vendas";pendente', $csv);
        $this->assertStringNotContainsString(';confirmada;', $csv);

        $firstPos = strpos($csv, (string) $pendingLow->id.';"Cliente CSV Vendas";pendente');
        $secondPos = strpos($csv, (string) $pendingHigh->id.';"Cliente CSV Vendas";pendente');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertTrue($firstPos < $secondPos);
    }
}
