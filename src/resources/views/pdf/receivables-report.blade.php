<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }
        .header {
            margin-bottom: 14px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .meta {
            margin-top: 4px;
            color: #6b7280;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #f9fafb;
            text-transform: uppercase;
            font-size: 9px;
            color: #4b5563;
        }
        .right {
            text-align: right;
        }
        .empty {
            text-align: center;
            color: #6b7280;
            padding: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Relatorio de Contas a Receber</h1>
        <div class="meta">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
        <div class="meta">
            Filtros: status {{ $filters['status'] !== '' ? $filters['status'] : 'todos' }},
            cliente/venda {{ $filters['search'] !== '' ? $filters['search'] : 'todos' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Titulo</th>
                <th>Venda</th>
                <th>Parcela</th>
                <th>Cliente</th>
                <th>Status</th>
                <th>Vencimento</th>
                <th class="right">Total</th>
                <th class="right">Pago</th>
                <th class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receivables as $receivable)
                @php
                    $isOverdue = in_array($receivable->status, ['aberto', 'parcial'], true)
                        && $receivable->due_date !== null
                        && $receivable->due_date->lt(now()->startOfDay());
                    $statusLabel = $isOverdue ? 'vencido' : $receivable->status;
                @endphp
                <tr>
                    <td>#{{ $receivable->id }}</td>
                    <td>#{{ $receivable->sale_id ?? '-' }}</td>
                    <td>{{ $receivable->installment_number }}/{{ $receivable->installment_count }}</td>
                    <td>{{ $receivable->client?->name ?? 'Sem cliente' }}</td>
                    <td>{{ ucfirst((string) $statusLabel) }}</td>
                    <td>{{ $receivable->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="right">{{ number_format((float) $receivable->amount_total, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $receivable->amount_paid, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $receivable->balance, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">Nenhum titulo encontrado para os filtros selecionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
