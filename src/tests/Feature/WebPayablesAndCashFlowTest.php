<?php

namespace Tests\Feature;

use App\Models\CashEntry;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPayablesAndCashFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_settle_payable_with_cashflow_entry(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('payables.store'), [
                'vendor_name' => 'Fornecedor Alpha',
                'description' => 'Compra de insumos',
                'category' => 'materia-prima',
                'document_number' => 'NF-1234',
                'amount_total' => 500,
                'due_date' => now()->addDays(10)->toDateString(),
                'notes' => 'Pagamento em duas partes',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Conta a pagar criada com sucesso!');

        $payable = Payable::query()->firstOrFail();
        $this->assertSame('aberto', $payable->status);
        $this->assertEquals(500.0, (float) $payable->balance);

        $this->actingAs($admin)
            ->post(route('payables.settle', $payable), [
                'amount' => 200,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Pagamento registrado com sucesso!');

        $payable->refresh();
        $this->assertSame('parcial', $payable->status);
        $this->assertEquals(300.0, (float) $payable->balance);

        $this->assertDatabaseHas('cash_entries', [
            'type' => 'saida',
            'origin_type' => 'payable',
            'origin_id' => $payable->id,
            'amount' => 200.00,
        ]);

        $this->actingAs($admin)
            ->post(route('payables.settle', $payable), [
                'amount' => 300,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Pagamento registrado com sucesso!');

        $payable->refresh();
        $this->assertSame('quitado', $payable->status);
        $this->assertEquals(0.0, (float) $payable->balance);

        $this->assertSame(2, CashEntry::query()->where('origin_type', 'payable')->where('origin_id', $payable->id)->count());
    }

    public function test_admin_can_view_cashflow_index_and_csv_with_entries_from_receivable_and_payable(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $receivable = Receivable::query()->create([
            'status' => 'aberto',
            'amount_total' => 300,
            'amount_paid' => 0,
            'balance' => 300,
            'due_date' => now()->toDateString(),
        ]);

        $payable = Payable::query()->create([
            'vendor_name' => 'Fornecedor Beta',
            'description' => 'Servico terceirizado',
            'category' => 'servicos',
            'status' => 'aberto',
            'amount_total' => 150,
            'amount_paid' => 0,
            'balance' => 150,
            'due_date' => now()->toDateString(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('receivables.settle', $receivable), [
                'amount' => 300,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('payables.settle', $payable), [
                'amount' => 150,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('cashflow.index', ['period_from' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Fluxo de Caixa')
            ->assertSee('Baixa conta a receber')
            ->assertSee('Baixa conta a pagar');

        $response = $this->actingAs($admin)
            ->get(route('cashflow.exportCsv', ['period_from' => now()->subDay()->toDateString()]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Lancamento;Tipo;Origem;Descricao;Valor;Data;Usuario;Observacoes', $csv);
        $this->assertStringContainsString('entrada', $csv);
        $this->assertStringContainsString('saida', $csv);
    }

    public function test_estoquista_cannot_access_payables_and_cashflow(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $payable = Payable::query()->create([
            'vendor_name' => 'Fornecedor Restrito',
            'description' => 'Despesa',
            'category' => 'geral',
            'status' => 'aberto',
            'amount_total' => 120,
            'amount_paid' => 0,
            'balance' => 120,
        ]);

        $this->actingAs($estoquista)
            ->get(route('payables.index'))
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->post(route('payables.settle', $payable), [
                'amount' => 10,
            ])
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->get(route('cashflow.index'))
            ->assertForbidden();
    }
}
