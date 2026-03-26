<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Services\OperationsBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_backup_command_generates_file(): void
    {
        Storage::fake('local');

        $this->artisan('ops:backup --label=cmdtest')
            ->expectsOutputToContain('Backup criado com sucesso.')
            ->assertExitCode(0);

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);
    }

    public function test_ops_restore_requires_force_flag(): void
    {
        Storage::fake('local');

        $this->artisan('ops:restore backups/inexistente.json')
            ->expectsOutputToContain('Use --force para confirmar restauracao.')
            ->assertExitCode(1);
    }

    public function test_ops_restore_reloads_data_from_backup(): void
    {
        Category::query()->create(['name' => 'Categoria Original', 'active' => true]);

        $backupFile = app(OperationsBackupService::class)->createBackup('restoretest')['path'];

        Category::query()->delete();
        $this->assertDatabaseCount('categories', 0);

        $this->artisan('ops:restore '.$backupFile.' --force')->assertExitCode(0);

        $this->assertDatabaseHas('categories', ['name' => 'Categoria Original']);
    }

    public function test_ops_backup_verify_validates_file(): void
    {
        $this->artisan('ops:backup --label=verifycmd')->assertExitCode(0);

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);

        $this->artisan('ops:backup:verify '.$files[0])
            ->expectsOutputToContain('Backup valido.')
            ->assertExitCode(0);
    }

    public function test_ops_preflight_returns_failure_when_no_backups(): void
    {
        Storage::disk('local')->deleteDirectory('backups');

        $this->artisan('ops:preflight')
            ->expectsOutputToContain('Preflight: OK')
            ->assertExitCode(0);
    }
}
