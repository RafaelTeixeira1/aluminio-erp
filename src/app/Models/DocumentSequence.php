<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'prefix',
        'next_number',
        'pattern',
        'reset_frequency',
        'last_reset_at',
        'year_length',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'last_reset_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentSequenceLog::class);
    }

    /**
     * Gera o próximo número da sequência
     */
    public function generateNext(): string
    {
        $this->ensureResetIfNeeded();

        $number = $this->next_number;
        $this->increment('next_number');

        return $this->formatNumber($number);
    }

    /**
     * Formata um número usando o padrão definido
     */
    public function formatNumber(int $number): string
    {
        $pattern = $this->pattern;

        // Substitui placeholders
        $result = str_replace('P', $this->prefix, $pattern);
        $result = str_replace('%Y', date('Y'), $result);
        $result = str_replace('%y', date('y'), $result);
        $result = str_replace('%m', date('m'), $result);
        $result = str_replace('%d', date('d'), $result);

        // Formata número com padding zero conforme pattern (ex: %06d -> 000001)
        if (preg_match('/%(\d+)d/', $result, $matches)) {
            $padding = (int) $matches[1];
            $result = preg_replace_callback(
                '/%\d+d/',
                fn() => str_pad($number, $padding, '0', STR_PAD_LEFT),
                $result
            );
        }

        return $result;
    }

    /**
     * Verifica se precisa resetar a sequência baseado na frequência
     */
    private function ensureResetIfNeeded(): void
    {
        if ($this->reset_frequency === 'never') {
            return;
        }

        $now = now()->startOfDay();
        $lastReset = $this->last_reset_at?->startOfDay();

        $shouldReset = false;

        if ($this->reset_frequency === 'annual') {
            $shouldReset = !$lastReset || $lastReset->diffInDays($now) >= 365;
        } elseif ($this->reset_frequency === 'monthly') {
            $shouldReset = !$lastReset || $lastReset->diffInDays($now) >= 30;
        }

        if ($shouldReset) {
            $this->update([
                'next_number' => 1,
                'last_reset_at' => now(),
            ]);
        }
    }
}
