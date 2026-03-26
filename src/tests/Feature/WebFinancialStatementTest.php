<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebFinancialStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dre_and_export_csv(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $receivable = Receivable::query()->create([
            'status' => 'aberto',
            'amount_total' => 800,
            'amount_paid' => 0,
            'balance' => 800,
            'due_date' => now()->toDateString(),
        ]);

        $payable = Payable::query()->create([
            'vendor_name' => 'Fornecedor DRE',
            'description' => 'Compra para DRE',
            'category' => 'materia-prima',
            'status' => 'aberto',
            'amount_total' => 300,
            'amount_paid' => 0,
            'balance' => 300,
            'due_date' => now()->toDateString(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('receivables.settle', $receivable), [
                'amount' => 800,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('payables.settle', $payable), [
                'amount' => 300,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $periodFrom = now()->subDay()->toDateString();
        $periodTo = now()->addDay()->toDateString();

        $this->actingAs($admin)
            ->get(route('financialStatement.index', [
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
            ]))
            ->assertOk()
            ->assertSee('DRE Simplificado')
            ->assertSee('Receita Bruta')
            ->assertSee('Despesas Operacionais')
            ->assertSee('500,00')
            ->assertSee('Materia-prima');

        $response = $this->actingAs($admin)
            ->get(route('financialStatement.exportCsv', [
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('DRE Simplificado', $csv);
        $this->assertStringContainsString('Receita Bruta', $csv);
        $this->assertStringContainsString('Despesas Operacionais', $csv);
        $this->assertStringContainsString('Lucro Liquido', $csv);
        $this->assertStringContainsString('800.00', $csv);
        $this->assertStringContainsString('300.00', $csv);
        $this->assertStringContainsString('500.00', $csv);
        $this->assertStringContainsString('materia-prima', $csv);
    }

    public function test_estoquista_cannot_access_dre(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $this->actingAs($estoquista)
            ->get(route('financialStatement.index'))
            ->assertForbidden();

        $this->actingAs($estoquista)
            ->get(route('financialStatement.exportCsv'))
            ->assertForbidden();
    }
}
