<?php

use App\Services\QuoteService;
use App\Services\OperationsBackupService;
use App\Services\OperationsPreflightService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quotes:expire', function (QuoteService $quoteService) {
    $affected = $quoteService->expireOverdueQuotes();

    $this->info("Orcamentos expirados: {$affected}");
})->purpose('Atualiza orcamentos vencidos para status expirado');

Schedule::command('quotes:expire')->dailyAt('00:10');

Artisan::command('ops:backup {--label=}', function (OperationsBackupService $service) {
    $result = $service->createBackup($this->option('label'));

    $this->info('Backup criado com sucesso.');
    $this->line('Arquivo: '.$result['path']);
})->purpose('Gera backup operacional em JSON no storage local');

Artisan::command('ops:restore {file} {--force}', function (OperationsBackupService $service) {
    if (!$this->option('force')) {
        $this->error('Use --force para confirmar restauracao.');

        return self::FAILURE;
    }

    $file = (string) $this->argument('file');
    $result = $service->restoreBackup($file);

    $this->info('Restore concluido.');
    $this->line('Arquivo: '.$result['restored_from']);
    $this->line('Tabelas restauradas: '.(string) $result['tables_restored']);

    return self::SUCCESS;
})->purpose('Restaura backup operacional JSON para o banco atual');

Artisan::command('ops:backup:verify {file}', function (OperationsBackupService $service) {
    $file = (string) $this->argument('file');
    $result = $service->verifyBackup($file);

    $this->info('Backup valido.');
    $this->line('Arquivo: '.$result['path']);
    $this->line('Checksum: '.$result['checksum_sha256']);

    return self::SUCCESS;
})->purpose('Valida integridade (checksum e formato) de um backup JSON');

Artisan::command('ops:preflight', function (OperationsPreflightService $service) {
    $result = $service->run();

    $this->line('Preflight: '.strtoupper((string) $result['status']));
    foreach ((array) $result['checks'] as $check) {
        $name = (string) ($check['name'] ?? 'unknown');
        $status = strtoupper((string) ($check['status'] ?? 'fail'));
        $message = (string) ($check['message'] ?? '');
        $this->line("[{$status}] {$name} - {$message}");
    }

    return $result['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
})->purpose('Executa checklist de prontidao para deploy em producao');
