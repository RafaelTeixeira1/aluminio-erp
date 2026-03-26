<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Relatorio de Contas a Pagar</h1>
    <p class="meta">
        Gerado em {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['status'])) | Status: {{ $filters['status'] }} @endif
        @if(!empty($filters['category'])) | Categoria: {{ $filters['category'] }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Titulo</th>
                <th>Fornecedor</th>
                <th>Descricao</th>
                <th>Status</th>
                <th>Vencimento</th>
                <th class="text-right">Total</th>
                <th class="text-right">Pago</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payables as $payable)
                @php
                    $isOverdue = in_array($payable->status, ['aberto', 'parcial'], true)
                        && $payable->due_date !== null
                        && $payable->due_date->lt(now()->startOfDay());
                    $statusLabel = $isOverdue ? 'vencido' : $payable->status;
                @endphp
                <tr>
                    <td>#{{ $payable->id }}</td>
                    <td>{{ $payable->vendor_name }}</td>
                    <td>{{ $payable->description }}</td>
                    <td>{{ ucfirst((string) $statusLabel) }}</td>
                    <td>{{ $payable->due_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float) $payable->amount_total, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) $payable->amount_paid, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) $payable->balance, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
