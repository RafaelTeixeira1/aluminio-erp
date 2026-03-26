<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Payable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $user = User::factory()->create([
            'active' => true,
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'senha-incorreta',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'senha-incorreta',
        ])->assertStatus(429);
    }

    public function test_payable_flows_generate_audit_logs(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $createResponse = $this->actingAs($admin)
            ->postJson('/api/contas-a-pagar', [
                'vendor_name' => 'Fornecedor Auditoria',
                'description' => 'Compra para auditoria',
                'category' => 'geral',
                'amount_total' => 250,
            ]);

        $createResponse->assertCreated();
        $payableId = (int) $createResponse->json('id');

        $this->actingAs($admin)
            ->postJson('/api/contas-a-pagar/'.$payableId.'/baixar', [
                'amount' => 100,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payable.created',
            'entity_type' => Payable::class,
            'entity_id' => $payableId,
            'user_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payable.settled',
            'entity_type' => Payable::class,
            'entity_id' => $payableId,
            'user_id' => $admin->id,
        ]);
    }

    public function test_audit_endpoint_is_admin_only(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'manual.test',
            'entity_type' => 'TestEntity',
            'entity_id' => 1,
            'payload' => ['a' => 1],
            'metadata' => ['ip' => '127.0.0.1'],
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/auditoria')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'manual.test');

        $this->actingAs($vendedor)
            ->getJson('/api/auditoria')
            ->assertForbidden();
    }
}
