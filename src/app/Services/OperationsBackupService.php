<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OperationsBackupService
{
    /**
     * @var array<int, string>
     */
    private const TABLES = [
        'users',
        'clients',
        'categories',
        'catalog_items',
        'quotes',
        'quote_items',
        'sales',
        'sale_items',
        'receivables',
        'payables',
        'cash_entries',
        'suppliers',
        'purchase_orders',
        'purchase_order_items',
        'stock_movements',
        'app_settings',
        'document_sequences',
        'document_sequence_logs',
        'audit_logs',
    ];

    /**
     * @return array<string, mixed>
     */
    public function createBackup(?string $label = null): array
    {
        $timestamp = now()->format('Ymd-His');
        $safeLabel = $label !== null && trim($label) !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '-', trim($label)) : null;
        $filename = $safeLabel !== null && $safeLabel !== ''
            ? "backup-{$safeLabel}-{$timestamp}.json"
            : "backup-{$timestamp}.json";

        $path = 'backups/'.$filename;
        $tables = [];

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            $tables[$table] = $rows;
        }

        $tablesJson = json_encode($tables, JSON_UNESCAPED_UNICODE);
        $checksum = hash('sha256', (string) $tablesJson);

        $payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'app_env' => (string) config('app.env'),
                'database' => (string) config('database.default'),
                'tables_count' => count($tables),
                'rows_total' => collect($tables)->sum(fn ($rows) => is_array($rows) ? count($rows) : 0),
                'checksum_sha256' => $checksum,
            ],
            'tables' => $tables,
        ];

        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'path' => $path,
            'meta' => $payload['meta'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function restoreBackup(string $path): array
    {
        if (!Storage::disk('local')->exists($path)) {
            throw new RuntimeException('Arquivo de backup nao encontrado: '.$path);
        }

        $raw = (string) Storage::disk('local')->get($path);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded) || !isset($decoded['tables']) || !is_array($decoded['tables'])) {
            throw new RuntimeException('Formato de backup invalido.');
        }

        $this->assertBackupIntegrity($decoded);

        $tables = $decoded['tables'];

        DB::beginTransaction();
        try {
            $this->disableForeignKeyChecks();

            foreach ($tables as $table => $rows) {
                if (!is_string($table) || !Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->delete();

                if (is_array($rows) && $rows !== []) {
                    foreach (array_chunk($rows, 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }

            $this->enableForeignKeyChecks();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->enableForeignKeyChecks();
            throw $e;
        }

        return [
            'restored_from' => $path,
            'tables_restored' => count($tables),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function listBackups(): array
    {
        return collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($path) => str_ends_with($path, '.json'))
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyBackup(string $path): array
    {
        if (!Storage::disk('local')->exists($path)) {
            throw new RuntimeException('Arquivo de backup nao encontrado: '.$path);
        }

        $raw = (string) Storage::disk('local')->get($path);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Backup invalido: JSON malformado.');
        }

        $this->assertBackupIntegrity($decoded);

        $tables = (array) ($decoded['tables'] ?? []);
        $rowsTotal = collect($tables)->sum(fn ($rows) => is_array($rows) ? count($rows) : 0);

        return [
            'valid' => true,
            'path' => $path,
            'tables_count' => count($tables),
            'rows_total' => $rowsTotal,
            'checksum_sha256' => (string) ($decoded['meta']['checksum_sha256'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function assertBackupIntegrity(array $decoded): void
    {
        if (!isset($decoded['meta']) || !is_array($decoded['meta'])) {
            throw new RuntimeException('Backup invalido: bloco meta ausente.');
        }

        if (!isset($decoded['tables']) || !is_array($decoded['tables'])) {
            throw new RuntimeException('Backup invalido: bloco tables ausente.');
        }

        foreach ($decoded['tables'] as $table => $rows) {
            if (!is_string($table) || !is_array($rows)) {
                throw new RuntimeException('Backup invalido: tabela ou linhas em formato invalido.');
            }
        }

        $expectedChecksum = (string) ($decoded['meta']['checksum_sha256'] ?? '');
        if ($expectedChecksum === '') {
            throw new RuntimeException('Backup invalido: checksum ausente.');
        }

        $actualChecksum = hash('sha256', json_encode($decoded['tables'], JSON_UNESCAPED_UNICODE) ?: '');
        if (!hash_equals($expectedChecksum, $actualChecksum)) {
            throw new RuntimeException('Backup invalido: checksum divergente.');
        }
    }

    private function disableForeignKeyChecks(): void
    {
        $driver = (string) DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    private function enableForeignKeyChecks(): void
    {
        $driver = (string) DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
