<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_cannot_access_admin_user_management_pages(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)
            ->get('/usuarios')
            ->assertForbidden()
            ->assertSeeText('Acesso negado');
    }

    public function test_vendedor_has_operational_access_but_not_financial_access(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)
            ->get('/produtos')
            ->assertOk();

        $this->actingAs($vendedor)
            ->get('/estoque')
            ->assertOk();

        $this->actingAs($vendedor)
            ->get('/financeiro/receber')
            ->assertForbidden()
            ->assertSeeText('Acesso negado');
    }

    public function test_vendedor_reports_hide_financial_metrics(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)
            ->get('/relatorios')
            ->assertOk()
            ->assertDontSeeText('Receita no Período')
            ->assertSeeText('Painel operacional focado em estoque e produtos');
    }

    public function test_vendedor_dashboard_hides_financial_cards_and_values(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSeeText('Faturamento Hoje');
    }

    public function test_estoquista_can_access_stock_page(): void
    {
        $estoquista = User::factory()->create([
            'profile' => 'estoquista',
            'active' => true,
        ]);

        $this->actingAs($estoquista)
            ->get('/estoque')
            ->assertOk();
    }

    public function test_operador_cannot_access_stock_page(): void
    {
        $operador = User::factory()->create([
            'profile' => 'operador',
            'active' => true,
        ]);

        $this->actingAs($operador)
            ->get('/estoque')
            ->assertForbidden()
            ->assertSeeText('Acesso negado');
    }
}
