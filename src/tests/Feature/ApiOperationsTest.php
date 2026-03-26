<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_health_and_request_id_header(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/operacao/health');

        $response
            ->assertOk()
            ->assertJsonStructure(['status', 'checked_at', 'request_id', 'services'])
            ->assertHeader('X-Request-Id');
    }

    public function test_admin_can_get_readiness(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/operacao/readiness')
            ->assertOk()
            ->assertJsonStructure(['status', 'checked_at', 'request_id', 'requirements']);
    }

    public function test_admin_can_get_preflight(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/operacao/backup', ['label' => 'preflight'])
            ->assertCreated();

        $this->actingAs($admin)
            ->getJson('/api/operacao/preflight')
            ->assertOk()
            ->assertJsonStructure(['status', 'checked_at', 'checks']);
    }

    public function test_admin_can_get_metrics(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/operacao/metrics');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'metrics' => ['sales_total', 'receivables_open', 'payables_open', 'db_size_bytes'],
            ]);
    }

    public function test_admin_can_create_and_list_backup(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $createResponse = $this->actingAs($admin)->postJson('/api/operacao/backup', [
            'label' => 'teste',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonStructure(['path', 'meta']);

        $path = (string) $createResponse->json('path');

        $listResponse = $this->actingAs($admin)->getJson('/api/operacao/backups');
        $listResponse->assertOk();
        $this->assertContains($path, $listResponse->json('items'));

        $this->actingAs($admin)
            ->postJson('/api/operacao/backup/verificar', ['path' => $path])
            ->assertOk()
            ->assertJsonPath('valid', true);
    }

    public function test_non_admin_cannot_access_operations_endpoints(): void
    {
        $vendedor = User::factory()->create([
            'profile' => 'vendedor',
            'active' => true,
        ]);

        $this->actingAs($vendedor)->getJson('/api/operacao/health')->assertForbidden();
        $this->actingAs($vendedor)->getJson('/api/operacao/readiness')->assertForbidden();
        $this->actingAs($vendedor)->getJson('/api/operacao/preflight')->assertForbidden();
        $this->actingAs($vendedor)->getJson('/api/operacao/metrics')->assertForbidden();
        $this->actingAs($vendedor)->postJson('/api/operacao/backup')->assertForbidden();
        $this->actingAs($vendedor)->postJson('/api/operacao/backup/verificar', ['path' => 'x'])->assertForbidden();
        $this->actingAs($vendedor)->getJson('/api/operacao/backups')->assertForbidden();
    }
}
