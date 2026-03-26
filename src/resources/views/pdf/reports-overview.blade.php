<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .muted { color: #6b7280; margin-bottom: 10px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid td, .grid th { border: 1px solid #e5e7eb; padding: 6px; }
        .grid th { background: #f9fafb; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Relatórios Gerenciais</h1>
    <p class="muted">Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @if(($can_view_financial ?? false) === true)
    <table class="grid">
        <tr>
            <th>Receita</th>
            <th>Vendas</th>
            <th>Conversão</th>
            <th>Estoque crítico</th>
        </tr>
        <tr>
            <td>R$ {{ number_format((float) ($metrics['revenue'] ?? 0), 2, ',', '.') }}</td>
            <td>{{ (int) ($metrics['sales_count'] ?? 0) }}</td>
            <td>{{ (int) ($metrics['conversion_rate'] ?? 0) }}%</td>
            <td>{{ (int) ($metrics['low_stock_count'] ?? 0) }}</td>
        </tr>
    </table>
    @else
    <table class="grid">
        <tr>
            <th>Produtos ativos</th>
            <th>Estoque crítico</th>
            <th>Sem estoque</th>
            <th>Categorias ativas</th>
        </tr>
        <tr>
            <td>{{ (int) ($metrics['active_products'] ?? 0) }}</td>
            <td>{{ (int) ($metrics['low_stock_count'] ?? 0) }}</td>
            <td>{{ (int) ($metrics['out_of_stock_count'] ?? 0) }}</td>
            <td>{{ (int) ($metrics['categories_count'] ?? 0) }}</td>
        </tr>
    </table>
    @endif

    <h3>{{ ($can_view_financial ?? false) ? 'Top Produtos' : 'Produtos para Reposição' }}</h3>
    <table class="grid">
        <tr>
            <th>Produto</th>
            @if(($can_view_financial ?? false) === true)
                <th class="right">Total</th>
            @else
                <th class="right">Estoque</th>
                <th class="right">Mínimo</th>
            @endif
        </tr>
        @forelse(($top_products ?? collect()) as $product)
            <tr>
                <td>{{ ($can_view_financial ?? false) ? $product->item_name : $product->name }}</td>
                @if(($can_view_financial ?? false) === true)
                    <td class="right">R$ {{ number_format((float) $product->total_value, 2, ',', '.') }}</td>
                @else
                    <td class="right">{{ number_format((float) $product->stock, 3, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $product->stock_minimum, 3, ',', '.') }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ ($can_view_financial ?? false) ? 2 : 3 }}">Sem dados.</td></tr>
        @endforelse
    </table>

    @if(($can_view_financial ?? false) === true)
    <h3>Resumo por Status</h3>
    <table class="grid">
        <tr>
            <th>Status</th>
            <th class="right">Quantidade</th>
            <th class="right">Total</th>
        </tr>
        @forelse(($status_summary ?? collect()) as $status)
            <tr>
                <td>{{ ucfirst((string) $status->status) }}</td>
                <td class="right">{{ (int) $status->count }}</td>
                <td class="right">R$ {{ number_format((float) $status->total_value, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Sem dados.</td></tr>
        @endforelse
    </table>
    @else
    <p class="muted">Relatório operacional sem indicadores financeiros para o perfil de vendedor.</p>
    @endif
</body>
</html>
