<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_payables(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Payable::factory()->count(3)->create(['created_by_user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/contas-a-pagar');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_payable(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $response = $this->actingAs($admin)
            ->postJson('/api/contas-a-pagar', [
                'vendor_name' => 'Fornecedor Teste',
                'description' => 'Compra de produtos',
                'category' => 'materia-prima',
                'amount_total' => 1000.00,
                'due_date' => now()->addDays(30)->toDateString(),
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('payables', [
            'vendor_name' => 'Fornecedor Teste',
            'status' => 'aberto',
        ]);
    }

    public function test_admin_can_settle_payable(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $payable = Payable::factory()->create([
            'status' => 'aberto',
            'amount_total' => 1000.00,
            'amount_paid' => 0,
            'balance' => 1000.00,
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/contas-a-pagar/{$payable->id}/baixar", [
                'amount' => 500.00,
                'paid_at' => now()->toDateString(),
            ]);

        $response->assertOk();
        $payable->refresh();
        $this->assertEquals(500.00, (float) $payable->amount_paid);
        $this->assertEquals(500.00, (float) $payable->balance);
        $this->assertEquals('parcial', $payable->status);
    }

    public function test_admin_can_get_payable_summary(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Payable::factory()->create([
            'status' => 'aberto',
            'amount_total' => 1000.00,
            'balance' => 1000.00,
            'created_by_user_id' => $admin->id,
        ]);

        Payable::factory()->create([
            'status' => 'quitado',
            'amount_total' => 500.00,
            'amount_paid' => 500.00,
            'balance' => 0,
            'paid_at' => now(),
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/contas-a-pagar/resumo');

        $response->assertOk();
        $response->assertJsonPath('open_count', 1);
        $this->assertEquals(1000.0, (float) $response->json('open_balance'));
    }

    public function test_non_admin_cannot_access_payables(): void
    {
        $vendedor = User::factory()->create(['profile' => 'vendedor', 'active' => true]);

        $this->actingAs($vendedor)
            ->getJson('/api/contas-a-pagar')
            ->assertForbidden();
    }
}
