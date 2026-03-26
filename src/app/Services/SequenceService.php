<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\DocumentSequenceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * @var array<string, array{description: string, prefix: string, pattern: string, reset_frequency: string}>
     */
    private const DEFAULT_SEQUENCES = [
        'PO_COMPRA' => [
            'description' => 'Pedidos de Compra',
            'prefix' => 'PC-',
            'pattern' => 'P%06d',
            'reset_frequency' => 'never',
        ],
        'VD_VENDA' => [
            'description' => 'Vendas',
            'prefix' => 'VD-',
            'pattern' => 'P%06d',
            'reset_frequency' => 'never',
        ],
        'QT_ORCAMENTO' => [
            'description' => 'Orcamentos',
            'prefix' => 'ORC-',
            'pattern' => 'P%06d',
            'reset_frequency' => 'never',
        ],
    ];

    /**
     * Gera o próximo número para um tipo de documento
     * Automaticamente cria a sequência se não existir
     */
    public function generateNext(string $sequenceCode, ?string $documentType = null, ?int $documentId = null): string
    {
        return DB::transaction(function () use ($sequenceCode, $documentType, $documentId) {
            $defaults = self::DEFAULT_SEQUENCES[$sequenceCode] ?? [
                'description' => $sequenceCode,
                'prefix' => '',
                'pattern' => 'P-%06d',
                'reset_frequency' => 'never',
            ];

            DocumentSequence::query()->firstOrCreate(
                ['code' => $sequenceCode],
                [
                    'description' => $defaults['description'],
                    'prefix' => $defaults['prefix'],
                    'pattern' => $defaults['pattern'],
                    'reset_frequency' => $defaults['reset_frequency'],
                    'next_number' => 1,
                    'is_active' => true,
                ]
            );

            $sequence = DocumentSequence::query()
                ->where('code', $sequenceCode)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$sequence->is_active) {
                throw new \DomainException("Sequência {$sequenceCode} está inativa");
            }

            // Gera o número
            $generatedNumber = $sequence->generateNext();

            // Log da geração
            DocumentSequenceLog::create([
                'document_sequence_id' => $sequence->id,
                'generated_number' => $generatedNumber,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'generated_by_user_id' => Auth::id(),
                'generated_at' => now(),
            ]);

            return $generatedNumber;
        });
    }

    /**
     * Obtém a sequência ou cria uma com padrão padrão
     */
    public function getOrCreateSequence(
        string $code,
        string $description,
        string $prefix = '',
        string $pattern = 'P-%06d',
        string $resetFrequency = 'never'
    ): DocumentSequence {
        return DocumentSequence::firstOrCreate(
            ['code' => $code],
            [
                'description' => $description,
                'prefix' => $prefix,
                'pattern' => $pattern,
                'reset_frequency' => $resetFrequency,
                'next_number' => 1,
                'is_active' => true,
            ]
        );
    }

    /**
     * Atualiza configuração de uma sequência
     */
    public function updateSequence(
        string $code,
        ?string $description = null,
        ?string $prefix = null,
        ?string $pattern = null,
        ?string $resetFrequency = null
    ): DocumentSequence {
        $sequence = DocumentSequence::where('code', $code)->firstOrFail();

        $updates = [];
        if ($description !== null) $updates['description'] = $description;
        if ($prefix !== null) $updates['prefix'] = $prefix;
        if ($pattern !== null) $updates['pattern'] = $pattern;
        if ($resetFrequency !== null) $updates['reset_frequency'] = $resetFrequency;

        $sequence->update($updates);

        return $sequence;
    }

    /**
     * Ativa/desativa uma sequência
     */
    public function toggleSequence(string $code, bool $active): DocumentSequence
    {
        $sequence = DocumentSequence::where('code', $code)->firstOrFail();
        $sequence->update(['is_active' => $active]);

        return $sequence;
    }

    /**
     * Reset manual de uma sequência
     */
    public function resetSequence(string $code): DocumentSequence
    {
        $sequence = DocumentSequence::where('code', $code)->firstOrFail();
        $sequence->update([
            'next_number' => 1,
            'last_reset_at' => now(),
        ]);

        return $sequence;
    }

    /**
     * Obtém histórico de gerações de uma sequência
     */
    public function getHistory(string $code, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $sequence = DocumentSequence::where('code', $code)->firstOrFail();

        return DocumentSequenceLog::where('document_sequence_id', $sequence->id)
            ->with('generatedBy')
            ->orderByDesc('generated_at')
            ->limit($limit)
            ->get();
    }
}
