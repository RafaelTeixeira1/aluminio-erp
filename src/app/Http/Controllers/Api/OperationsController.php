<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Sale;
use App\Services\OperationsBackupService;
use App\Services\OperationsPreflightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OperationsController extends Controller
{
    public function __construct(
        private readonly OperationsBackupService $backupService,
        private readonly OperationsPreflightService $preflightService,
    )
    {
    }

    public function readiness(Request $request): JsonResponse
    {
        $databaseReady = true;
        $storageReady = true;
        $requiredTablesReady = true;

        try {
            DB::select('select 1 as ping');
        } catch (\Throwable $e) {
            $databaseReady = false;
        }

        try {
            $probePath = 'health/readiness-'.uniqid().'.txt';
            Storage::disk('local')->put($probePath, 'ok');
            Storage::disk('local')->delete($probePath);
        } catch (\Throwable $e) {
            $storageReady = false;
        }

        foreach (['users', 'sales', 'quotes'] as $table) {
            if (!Schema::hasTable($table)) {
                $requiredTablesReady = false;
                break;
            }
        }

        $status = ($databaseReady && $storageReady && $requiredTablesReady) ? 'ready' : 'not_ready';

        return response()->json([
            'status' => $status,
            'checked_at' => now()->toIso8601String(),
            'request_id' => $request->attributes->get('request_id'),
            'requirements' => [
                'database' => $databaseReady,
                'storage' => $storageReady,
                'required_tables' => $requiredTablesReady,
            ],
        ], $status === 'ready' ? 200 : 503);
    }

    public function health(Request $request): JsonResponse
    {
        $databaseStatus = 'up';
        $storageStatus = 'up';
        $cacheStatus = 'up';
        $errors = [];

        try {
            DB::select('select 1 as ping');
        } catch (\Throwable $e) {
            $databaseStatus = 'down';
            $errors[] = 'database';
        }

        try {
            $probePath = 'health/probe-'.uniqid().'.txt';
            Storage::disk('local')->put($probePath, 'ok');
            Storage::disk('local')->delete($probePath);
        } catch (\Throwable $e) {
            $storageStatus = 'down';
            $errors[] = 'storage';
        }

        try {
            $cacheKey = 'health:'.uniqid();
            Cache::put($cacheKey, 'ok', 60);
            $ok = Cache::get($cacheKey) === 'ok';
            Cache::forget($cacheKey);
            if (!$ok) {
                $cacheStatus = 'down';
                $errors[] = 'cache';
            }
        } catch (\Throwable $e) {
            $cacheStatus = 'down';
            $errors[] = 'cache';
        }

        $status = $errors === [] ? 'up' : 'degraded';

        return response()->json([
            'status' => $status,
            'checked_at' => now()->toIso8601String(),
            'request_id' => $request->attributes->get('request_id'),
            'services' => [
                'database' => $databaseStatus,
                'storage' => $storageStatus,
                'cache' => $cacheStatus,
            ],
        ]);
    }

    public function metrics(): JsonResponse
    {
        $metrics = [
            'sales_total' => Schema::hasTable('sales') ? (int) Sale::query()->count() : 0,
            'receivables_open' => Schema::hasTable('receivables')
                ? (float) (Receivable::query()->whereIn('status', ['aberto', 'parcial'])->sum('balance') ?? 0)
                : 0,
            'payables_open' => Schema::hasTable('payables')
                ? (float) (Payable::query()->whereIn('status', ['aberto', 'parcial'])->sum('balance') ?? 0)
                : 0,
            'db_size_bytes' => $this->databaseSizeBytes(),
        ];

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'metrics' => $metrics,
        ]);
    }

    public function backup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:60'],
        ]);

        $result = $this->backupService->createBackup($data['label'] ?? null);

        return response()->json($result, 201);
    }

    public function verifyBackup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->backupService->verifyBackup((string) $data['path']);

        return response()->json($result);
    }

    public function backups(): JsonResponse
    {
        return response()->json([
            'items' => $this->backupService->listBackups(),
        ]);
    }

    public function preflight(): JsonResponse
    {
        $result = $this->preflightService->run();

        return response()->json($result, $result['status'] === 'ok' ? 200 : 503);
    }

    private function databaseSizeBytes(): int
    {
        $databaseDefault = (string) config('database.default');
        if ($databaseDefault !== 'sqlite') {
            return 0;
        }

        $sqlitePath = (string) config('database.connections.sqlite.database');
        if ($sqlitePath === ':memory:' || $sqlitePath === '') {
            return 0;
        }

        return is_file($sqlitePath) ? (int) filesize($sqlitePath) : 0;
    }
}
