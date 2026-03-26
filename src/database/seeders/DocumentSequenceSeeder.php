<?php

namespace Database\Seeders;

use App\Models\DocumentSequence;
use Illuminate\Database\Seeder;

class DocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            [
                'code' => 'PO_COMPRA',
                'description' => 'Pedidos de Compra',
                'prefix' => 'PC-',
                'pattern' => 'P%06d',
                'reset_frequency' => 'never',
            ],
            [
                'code' => 'VD_VENDA',
                'description' => 'Pedidos de Venda',
                'prefix' => 'V-',
                'pattern' => 'P%Y%06d',
                'reset_frequency' => 'annual',
            ],
            [
                'code' => 'QT_ORCAMENTO',
                'description' => 'Orçamentos / Cotações',
                'prefix' => 'Q-',
                'pattern' => 'P%06d',
                'reset_frequency' => 'never',
            ],
            [
                'code' => 'NFE_SAIDA',
                'description' => 'Notas Fiscais de Saída',
                'prefix' => 'NF-',
                'pattern' => 'P%Y%06d',
                'reset_frequency' => 'annual',
            ],
            [
                'code' => 'NFE_ENTRADA',
                'description' => 'Notas Fiscais de Entrada',
                'prefix' => 'NFE-',
                'pattern' => 'P%Y%06d',
                'reset_frequency' => 'annual',
            ],
        ];

        foreach ($sequences as $seq) {
            DocumentSequence::updateOrCreate(
                ['code' => $seq['code']],
                array_merge($seq, ['next_number' => 1, 'is_active' => true])
            );
        }
    }
}
