<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsPreflightCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_preflight_command_runs_and_prints_status(): void
    {
        Storage::disk('local')->put('backups/backup-test.json', json_encode([
            'meta' => ['generated_at' => now()->toIso8601String()],
            'tables' => [],
        ]));

        $this->artisan('ops:preflight')
            ->expectsOutputToContain('Preflight')
            ->assertExitCode(0);
    }

    public function test_admin_can_access_readiness_and_preflight_endpoints(): void
    {
        Storage::disk('local')->put('backups/backup-test.json', json_encode([
            'meta' => ['generated_at' => now()->toIso8601String()],
            'tables' => [],
        ]));

        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/operacao/readiness')
            ->assertOk()
            ->assertJsonStructure(['status', 'request_id', 'requirements']);

        $this->actingAs($admin)
            ->getJson('/api/operacao/preflight')
            ->assertOk()
            ->assertJsonStructure(['status', 'checked_at', 'checks']);
    }

    public function test_backup_verify_endpoint_returns_ok_for_valid_backup(): void
    {
        $admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $createResponse = $this->actingAs($admin)->postJson('/api/operacao/backup', [
            'label' => 'verify',
        ])->assertCreated();

        $path = (string) $createResponse->json('path');

        $this->actingAs($admin)
            ->postJson('/api/operacao/backup/verificar', ['path' => $path])
            ->assertOk()
            ->assertJsonPath('valid', true);
    }
}
