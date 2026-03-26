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
        <h1 class="title">Relatorio de Orcamentos</h1>
        <div class="meta">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
        <div class="meta">
            Filtros: status {{ $filters['status'] !== '' ? $filters['status'] : 'todos' }},
            cliente {{ $filters['search'] !== '' ? $filters['search'] : 'todos' }},
            ordenado por {{ $filters['sort_by'] ?? 'created_at' }} ({{ $filters['sort_dir'] ?? 'desc' }})
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Status</th>
                <th>Validade</th>
                <th class="right">Subtotal</th>
                <th class="right">Desconto</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotes as $quote)
                <tr>
                    <td>{{ $quote->id }}</td>
                    <td>{{ $quote->client?->name ?? 'Orcamento rapido' }}</td>
                    <td>{{ ucfirst($quote->status) }}</td>
                    <td>{{ $quote->valid_until?->format('d/m/Y') ?? '-' }}</td>
                    <td class="right">{{ number_format((float) $quote->subtotal, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $quote->discount, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $quote->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Nenhum orçamento encontrado para os filtros selecionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
