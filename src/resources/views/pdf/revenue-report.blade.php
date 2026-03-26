<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        h2 { font-size: 14px; margin-top: 18px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .summary { width: 320px; margin-top: 10px; }
        .summary td { border: none; padding: 4px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Relatorio de Faturamento</h1>
    <p class="muted">Periodo: {{ $period['start_date'] }} ate {{ $period['end_date'] }}</p>

    <table class="summary">
        <tr>
            <td>Total de vendas</td>
            <td class="right">{{ $sales_count }}</td>
        </tr>
        <tr>
            <td>Faturamento bruto</td>
            <td class="right">R$ {{ number_format((float)$gross_total, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Descontos</td>
            <td class="right">R$ {{ number_format((float)$total_discount, 2, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Faturamento Diario</h2>
    <table>
        <thead>
            <tr>
                <th>Dia</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($daily as $row)
                <tr>
                    <td>{{ $row->day }}</td>
                    <td>R$ {{ number_format((float)$row->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Sem dados para o periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
