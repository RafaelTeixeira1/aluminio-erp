@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Bem-vindo, {{ Auth::user()->name }}!</h2>
    <p class="text-gray-600 mt-1">Resumo operacional em tempo real • {{ now()->format('d/m/Y H:i') }}</p>
</div>

<!-- KPI Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- Vendas Hoje -->
    <a href="{{ route('sales.index') }}" class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-6 hover:shadow-md transition">
        <p class="text-gray-600 text-sm font-medium">Vendas Hoje</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $metrics['sales_today'] }}</p>
        <p class="text-xs text-gray-500 mt-2">→ Ver todas as vendas</p>
    </a>

    @if($can_view_financial)
        <!-- Faturamento Hoje -->
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-6">
            <p class="text-gray-600 text-sm font-medium">Faturamento Hoje</p>
            <p class="text-3xl font-bold text-green-600 mt-2">R$ {{ number_format($metrics['revenue_today'], 2, ',', '.') }}</p>
        </div>
    @endif

    <!-- Orçamentos Abertos -->
    <a href="{{ route('quotes.index') }}" class="bg-white rounded-lg shadow-sm border-l-4 border-orange-500 p-6 hover:shadow-md transition">
        <p class="text-gray-600 text-sm font-medium">Orçamentos Abertos</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $metrics['open_quotes'] }}</p>
        @if($metrics['overdue_quotes'] > 0)
            <p class="text-xs text-red-600 mt-2 font-semibold">⚠️ {{ $metrics['overdue_quotes'] }} vencido(s)</p>
        @endif
    </a>

    <!-- Estoque Crítico -->
    <a href="{{ route('stock.index') }}" class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-6 hover:shadow-md transition">
        <p class="text-gray-600 text-sm font-medium">Estoque em Alerta</p>
        <p class="text-3xl font-bold text-red-600 mt-2">{{ $metrics['critical_stock'] + $metrics['out_of_stock'] }}</p>
        <p class="text-xs text-gray-500 mt-2">
            {{ $metrics['critical_stock'] }} crítico
            @if($metrics['out_of_stock'] > 0)
                • {{ $metrics['out_of_stock'] }} zerado
            @endif
        </p>
    </a>
</div>

<!-- Operacional & Financeiro Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Compras Pendentes -->
    @if ($metrics['pending_purchases'] > 0)
        <a href="{{ route('purchase-orders.index') }}" class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg border border-yellow-200 p-6 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-900 text-sm font-semibold">📋 Pedidos de Compra</p>
                    <p class="text-3xl font-bold text-yellow-700 mt-2">{{ $metrics['pending_purchases'] }} pendente(s)</p>
                    <p class="text-xs text-yellow-700 mt-2">→ Clique para receber itens</p>
                </div>
                <svg class="w-12 h-12 text-yellow-200" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 3v2H5v14h14V5h-4V3H9zm0 4h6v2H9V7zm0 4h6v2H9v-2zm0 4h6v2H9v-2z"/>
                </svg>
            </div>
        </a>
    @endif

    <!-- Contas Vencidas (Financeiro) -->
    @if($can_view_financial && ($metrics['overdue_payables'] > 0 || $metrics['overdue_receivables'] > 0))
        <div class="grid grid-cols-2 gap-4">
            @if($metrics['overdue_payables'] > 0)
                <a href="{{ route('payables.index') }}" class="bg-red-50 rounded-lg border-2 border-red-300 p-4 hover:shadow-md transition">
                    <p class="text-red-900 text-xs font-semibold uppercase">💸 A Pagar</p>
                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $metrics['overdue_payables'] }}</p>
                    <p class="text-sm text-red-700 font-semibold mt-1">R$ {{ number_format($metrics['total_overdue_payables'], 2, ',', '.') }}</p>
                </a>
            @endif

            @if($metrics['overdue_receivables'] > 0)
                <a href="{{ route('receivables.index') }}" class="bg-orange-50 rounded-lg border-2 border-orange-300 p-4 hover:shadow-md transition">
                    <p class="text-orange-900 text-xs font-semibold uppercase">📥 A Receber</p>
                    <p class="text-2xl font-bold text-orange-600 mt-2">{{ $metrics['overdue_receivables'] }}</p>
                    <p class="text-sm text-orange-700 font-semibold mt-1">R$ {{ number_format($metrics['total_overdue_receivables'], 2, ',', '.') }}</p>
                </a>
            @endif
        </div>
    @endif
</div>

<!-- Charts & Details Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Vendas - Últimos 7 dias -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Vendas - Últimos 7 dias</h3>
        <div class="space-y-3">
            @forelse($weekly_sales as $day)
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="text-sm font-medium text-gray-700 w-16">{{ \Carbon\Carbon::parse($day->day)->format('ddd') }}</span>
                        <div class="flex-1 h-6 bg-gray-100 rounded overflow-hidden">
                            @php
                                $maxQty = $weekly_sales->max('qty');
                                $widthPercent = $maxQty > 0 ? ($day->qty / $maxQty) * 100 : 0;
                            @endphp
                            <div class="h-full bg-blue-500" style="width: {{ $widthPercent }}%"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-sm text-gray-900 font-semibold">{{ $day->qty }}</span>
                        @if($can_view_financial)
                            <span class="text-xs text-green-600 ml-2">R$ {{ number_format((float) ($day->total ?? 0), 0, ',', '.') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4">Sem vendas registradas esta semana.</p>
            @endforelse
        </div>
    </div>

    <!-- Top Produtos -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">🏆 Top Produtos (Mês)</h3>
        <div class="space-y-4">
            @php
                $maxQty = (float) ($top_products->max('qty') ?? 0);
            @endphp
            @forelse($top_products as $product)
                @php
                    $width = $maxQty > 0 ? max(8, (int) round(((float) $product->qty / $maxQty) * 100)) : 8;
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $product->item_name }}</p>
                        <span class="text-xs font-bold text-gray-700 ml-2">{{ number_format((float) $product->qty, 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: {{ $width }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">Sem dados de produtos vendidos.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Atividades Recentes -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">📝 Atividades Recentes (Vendas)</h3>
        <a href="{{ route('sales.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ver todas →</a>
    </div>

    <div class="space-y-3">
        @forelse($recent_sales as $sale)
            <div class="flex items-center justify-between border border-gray-100 rounded-lg p-4 hover:bg-gray-50 transition">
                <div>
                    <p class="text-sm font-medium text-gray-900">#{{ $sale->id }} — {{ $sale->client_name ?? 'Cliente não informado' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($sale->created_at)->diffForHumans() }}</p>
                </div>
                <div class="text-right flex items-center gap-4">
                    @if($can_view_financial)
                        <p class="text-sm font-semibold text-gray-900">R$ {{ number_format((float) $sale->total, 2, ',', '.') }}</p>
                    @endif
                    <span class="text-xs px-3 py-1 rounded-full font-medium
                        {{ $sale->status === 'confirmada' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($sale->status) }}
                    </span>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 py-8 text-center">Nenhuma atividade recente encontrada.</p>
        @endforelse
    </div>
</div>

<!-- Help & Info -->
<div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
    <p class="text-sm text-blue-900"><strong>💡 Dica:</strong> Clique em qualquer card com fundo colorido para ir direto para a lista de itens relacionados.</p>
</div>
@endsection
