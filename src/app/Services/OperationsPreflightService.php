<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OperationsPreflightService
{
    public function __construct(private readonly OperationsBackupService $backupService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $checks = [];

        $checks[] = $this->checkAppKey();
        $checks[] = $this->checkDatabase();
        $checks[] = $this->checkStorage();
        $checks[] = $this->checkCache();
        $checks[] = $this->checkMigrations();
        $checks[] = $this->checkBackups();

        $failed = collect($checks)->contains(function ($check) {
            return ($check['level'] ?? 'critical') === 'critical' && ($check['status'] ?? '') !== 'ok';
        });

        return [
            'status' => $failed ? 'fail' : 'ok',
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
    * @return array<string, string>
     */
    private function checkAppKey(): array
    {
        $key = (string) config('app.key', '');

        return [
            'name' => 'app_key',
            'level' => 'critical',
            'status' => $key !== '' ? 'ok' : 'fail',
            'message' => $key !== '' ? 'APP_KEY configurada.' : 'APP_KEY ausente.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function checkDatabase(): array
    {
        try {
            DB::select('select 1 as ping');

            return [
                'name' => 'database',
                'level' => 'critical',
                'status' => 'ok',
                'message' => 'Conexao com banco ok.',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'database',
                'level' => 'critical',
                'status' => 'fail',
                'message' => 'Falha de conexao com banco.',
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function checkStorage(): array
    {
        try {
            $path = 'health/preflight-'.uniqid().'.txt';
            Storage::disk('local')->put($path, 'ok');
            Storage::disk('local')->delete($path);

            return [
                'name' => 'storage',
                'level' => 'critical',
                'status' => 'ok',
                'message' => 'Storage local gravavel.',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'storage',
                'level' => 'critical',
                'status' => 'fail',
                'message' => 'Storage local indisponivel.',
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function checkCache(): array
    {
        try {
            $key = 'preflight:'.uniqid();
            Cache::put($key, 'ok', 60);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return [
                'name' => 'cache',
                'level' => 'critical',
                'status' => $ok ? 'ok' : 'fail',
                'message' => $ok ? 'Cache funcional.' : 'Cache sem persistencia de leitura.',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'cache',
                'level' => 'critical',
                'status' => 'fail',
                'message' => 'Cache indisponivel.',
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function checkMigrations(): array
    {
        try {
            if (!Schema::hasTable('migrations')) {
                return [
                    'name' => 'migrations',
                    'level' => 'critical',
                    'status' => 'fail',
                    'message' => 'Tabela de migracoes ausente.',
                ];
            }

            $hasRan = DB::table('migrations')->count() > 0;

            return [
                'name' => 'migrations',
                'level' => 'critical',
                'status' => $hasRan ? 'ok' : 'fail',
                'message' => $hasRan ? 'Historico de migracoes encontrado.' : 'Sem historico de migracoes aplicado.',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'migrations',
                'level' => 'critical',
                'status' => 'fail',
                'message' => 'Nao foi possivel validar migracoes.',
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function checkBackups(): array
    {
        $items = $this->backupService->listBackups();

        return [
            'name' => 'backups',
            'level' => 'warning',
            'status' => $items !== [] ? 'ok' : 'fail',
            'message' => $items !== [] ? 'Backup(s) encontrado(s).' : 'Nenhum backup encontrado recentemente.',
        ];
    }
}
