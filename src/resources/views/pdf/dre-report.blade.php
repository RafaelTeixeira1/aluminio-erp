<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .meta { margin-bottom: 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>DRE Simplificado</h1>
    <p class="meta">
        Periodo: {{ \Carbon\Carbon::parse($filters['period_from'])->format('d/m/Y') }} ate {{ \Carbon\Carbon::parse($filters['period_to'])->format('d/m/Y') }}
        @if(!empty($filters['search'])) | Busca: {{ $filters['search'] }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Indicador</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Receita Bruta</td>
                <td class="text-right">{{ number_format((float) $summary['gross_revenue'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Despesas Operacionais</td>
                <td class="text-right">{{ number_format((float) $summary['operational_expenses'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lucro Liquido</td>
                <td class="text-right">{{ number_format((float) $summary['net_profit'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Margem Liquida (%)</td>
                <td class="text-right">{{ number_format((float) $summary['profit_margin'], 2, ',', '.') }}%</td>
            </tr>
        </tbody>
    </table>

    <h2>Receitas por origem</h2>
    <table>
        <thead>
            <tr>
                <th>Origem</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incomeByOrigin as $row)
                <tr>
                    <td>{{ ucfirst((string) $row->origin) }}</td>
                    <td class="text-right">{{ number_format((float) $row->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Sem receitas no periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Despesas por categoria</h2>
    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expensesByCategory as $row)
                <tr>
                    <td>{{ ucfirst((string) $row->category) }}</td>
                    <td class="text-right">{{ number_format((float) $row->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Sem despesas no periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
