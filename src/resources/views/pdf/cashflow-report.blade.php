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
    <h1>Relatorio de Fluxo de Caixa</h1>
    <p class="meta">
        Gerado em {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['type'])) | Tipo: {{ $filters['type'] }} @endif
        @if(!empty($filters['period_from'])) | De: {{ \Carbon\Carbon::parse($filters['period_from'])->format('d/m/Y') }} @endif
        @if(!empty($filters['period_to'])) | Ate: {{ \Carbon\Carbon::parse($filters['period_to'])->format('d/m/Y') }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Lancamento</th>
                <th>Tipo</th>
                <th>Origem</th>
                <th>Descricao</th>
                <th>Data</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>#{{ $entry->id }}</td>
                    <td>{{ ucfirst((string) $entry->type) }}</td>
                    <td>{{ trim((string) $entry->origin_type) !== '' ? $entry->origin_type : '-' }} {{ $entry->origin_id ? '#'.$entry->origin_id : '' }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>{{ $entry->occurred_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
