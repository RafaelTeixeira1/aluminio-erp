<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebReceivablesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_and_settle_receivable(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Financeiro',
            'phone' => '11999994444',
            'email' => 'financeiro.cliente@example.com',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $admin->id,
            'status' => 'pendente',
            'subtotal' => 500,
            'discount' => 50,
            'total' => 450,
        ]);

        $sale->items()->create([
            'item_name' => 'Item sem estoque',
            'item_type' => 'produto',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        $this->actingAs($admin)
            ->post(route('sales.confirm', $sale))
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('success', 'Venda confirmada com sucesso!');

        $receivable = Receivable::query()->where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame('aberto', $receivable->status);
        $this->assertEquals(450.0, (float) $receivable->balance);

        $this->actingAs($admin)
            ->post(route('receivables.settle', $receivable), [
                'amount' => 200,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Baixa registrada com sucesso!');

        $receivable->refresh();
        $this->assertSame('parcial', $receivable->status);
        $this->assertEquals(200.0, (float) $receivable->amount_paid);
        $this->assertEquals(250.0, (float) $receivable->balance);

        $this->actingAs($admin)
            ->post(route('receivables.settle', $receivable), [
                'amount' => 250,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Baixa registrada com sucesso!');

        $receivable->refresh();
        $this->assertSame('quitado', $receivable->status);
        $this->assertEquals(450.0, (float) $receivable->amount_paid);
        $this->assertEquals(0.0, (float) $receivable->balance);
        $this->assertNotNull($receivable->settled_at);
    }

    public function test_estoquista_cannot_access_financial_receivables(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $receivable = Receivable::query()->create([
            'status' => 'aberto',
            'amount_total' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($estoquista)
            ->get(route('receivables.index'))
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->post(route('receivables.settle', $receivable), [
                'amount' => 50,
            ])
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->post(route('receivables.split', $receivable), [
                'installments' => 3,
                'first_due_date' => now()->toDateString(),
                'interval_days' => 30,
            ])
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->put(route('receivables.update', $receivable), [
                'due_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_due_date_and_notes_and_export_csv(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Receber Export',
            'phone' => '11998887766',
            'email' => 'cliente.receber.export@example.com',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $admin->id,
            'status' => 'confirmada',
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
            'confirmed_at' => now(),
        ]);

        $receivable = Receivable::query()->create([
            'sale_id' => $sale->id,
            'client_id' => $client->id,
            'status' => 'aberto',
            'amount_total' => 300,
            'amount_paid' => 0,
            'balance' => 300,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $newDueDate = now()->addDays(10)->toDateString();

        $this->actingAs($admin)
            ->put(route('receivables.update', $receivable), [
                'due_date' => $newDueDate,
                'notes' => 'Ajustado apos negociacao',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Titulo atualizado com sucesso!');

        $receivable->refresh();
        $this->assertSame($newDueDate, optional($receivable->due_date)->format('Y-m-d'));
        $this->assertSame('Ajustado apos negociacao', $receivable->notes);

        $response = $this->actingAs($admin)->get(route('receivables.exportCsv', [
            'status' => 'aberto',
            'search' => 'Receber Export',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Titulo;Venda;Parcela;Cliente;Status;Vencimento;Total;Pago;Saldo;Observacoes', $csv);
        $this->assertStringContainsString('1/1;"Cliente Receber Export";aberto;', $csv);
        $this->assertStringContainsString('Ajustado apos negociacao', $csv);
    }

    public function test_admin_can_split_receivable_into_installments(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Parcelado',
            'phone' => '11990001122',
            'email' => 'cliente.parcelado@example.com',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $admin->id,
            'status' => 'confirmada',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'confirmed_at' => now(),
        ]);

        $receivable = Receivable::query()->create([
            'sale_id' => $sale->id,
            'installment_number' => 1,
            'installment_count' => 1,
            'client_id' => $client->id,
            'status' => 'aberto',
            'amount_total' => 1000,
            'amount_paid' => 0,
            'balance' => 1000,
            'due_date' => now()->toDateString(),
        ]);

        $firstDueDate = now()->addDays(5)->toDateString();

        $this->actingAs($admin)
            ->post(route('receivables.split', $receivable), [
                'installments' => 3,
                'first_due_date' => $firstDueDate,
                'interval_days' => 30,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Titulo parcelado com sucesso!');

        $titles = Receivable::query()
            ->where('sale_id', $sale->id)
            ->orderBy('installment_number')
            ->get();

        $this->assertCount(3, $titles);
        $this->assertSame([1, 2, 3], $titles->pluck('installment_number')->all());
        $this->assertSame([3, 3, 3], $titles->pluck('installment_count')->all());

        $this->assertEquals(333.34, (float) $titles[0]->amount_total);
        $this->assertEquals(333.33, (float) $titles[1]->amount_total);
        $this->assertEquals(333.33, (float) $titles[2]->amount_total);

        $this->assertSame($firstDueDate, optional($titles[0]->due_date)->format('Y-m-d'));
        $this->assertSame(now()->parse($firstDueDate)->addDays(30)->format('Y-m-d'), optional($titles[1]->due_date)->format('Y-m-d'));
        $this->assertSame(now()->parse($firstDueDate)->addDays(60)->format('Y-m-d'), optional($titles[2]->due_date)->format('Y-m-d'));
    }

    public function test_vendedor_can_confirm_sale_already_with_installments(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Confirmacao Parcelada',
            'phone' => '11995554433',
            'email' => 'cliente.confirmacao.parcelada@example.com',
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'created_by_user_id' => $vendedor->id,
            'status' => 'pendente',
            'subtotal' => 900,
            'discount' => 0,
            'total' => 900,
        ]);

        $sale->items()->create([
            'item_name' => 'Item sem estoque',
            'item_type' => 'produto',
            'quantity' => 1,
            'unit_price' => 900,
            'line_total' => 900,
        ]);

        $firstDueDate = now()->addDays(2)->toDateString();

        $this->actingAs($vendedor)
            ->post(route('sales.confirm', $sale), [
                'installments' => 3,
                'first_due_date' => $firstDueDate,
                'interval_days' => 30,
            ])
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('success', 'Venda confirmada com sucesso!');

        $sale->refresh();
        $this->assertSame('confirmada', $sale->status);

        $titles = Receivable::query()
            ->where('sale_id', $sale->id)
            ->orderBy('installment_number')
            ->get();

        $this->assertCount(3, $titles);
        $this->assertSame([1, 2, 3], $titles->pluck('installment_number')->all());
        $this->assertSame([3, 3, 3], $titles->pluck('installment_count')->all());
        $this->assertEquals(900.0, (float) $titles->sum('amount_total'));
        $this->assertSame($firstDueDate, optional($titles[0]->due_date)->format('Y-m-d'));
    }
}
