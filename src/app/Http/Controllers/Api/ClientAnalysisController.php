<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAnalysisController extends Controller
{
    public function analysis(Request $request, Client $cliente): JsonResponse
    {
        $startDate = Carbon::parse((string) $request->query('start_date', now()->subMonths(12)->toDateString()));
        $endDate = Carbon::parse((string) $request->query('end_date', now()->toDateString()));

        // Vendas
        $sales = Sale::query()
            ->where('client_id', $cliente->id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $totalSalesAmount = (float) $sales->sum('total');
        $totalSalesCount = $sales->count();
        $averageSaleValue = $totalSalesCount > 0 ? $totalSalesAmount / $totalSalesCount : 0;

        // Orçamentos
        $quotes = Quote::query()
            ->where('client_id', $cliente->id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $quoteCount = $quotes->count();
        $convertedQuotes = $quotes->filter(fn ($q) => in_array($q->status, ['convertida', 'convertido'], true))->count();
        $conversionRate = $quoteCount > 0 ? ($convertedQuotes / $quoteCount) * 100 : 0;

        // Receivables
        $receivablesOpen = Receivable::query()
            ->where('client_id', $cliente->id)
            ->whereIn('status', ['aberto', 'parcial'])
            ->get();

        $openBalance = (float) $receivablesOpen->sum('balance');
        $openCount = $receivablesOpen->count();

        $receivablesOverdue = $receivablesOpen->filter(function ($r) {
            return now()->isAfter($r->due_date);
        })->count();

        $receivablesSettled = Receivable::query()
            ->where('client_id', $cliente->id)
            ->where('status', 'quitado')
            ->whereBetween('settled_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $settledThisPeriod = (float) $receivablesSettled->sum('amount_paid');

        // Margem de lucro (estimada a partir de vendas)
        $costAnalysis = $this->calculateCostAnalysis($sales);

        // Produtos mais vendidos
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('catalog_items', 'catalog_items.id', '=', 'sale_items.catalog_item_id')
            ->where('sales.client_id', $cliente->id)
            ->whereBetween('sales.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('COALESCE(catalog_items.name, sale_items.item_name) as item_name')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.line_total) as total_value')
            ->groupBy('sale_items.catalog_item_id', 'item_name')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();

        return response()->json([
            'client' => $cliente->toArray(),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'sales' => [
                'total_amount' => $totalSalesAmount,
                'total_count' => $totalSalesCount,
                'average_value' => $averageSaleValue,
                'items' => $sales->map(fn ($s) => [
                    'id' => $s->id,
                    'total' => (float) $s->total,
                    'created_at' => $s->created_at->toDateString(),
                ])->values(),
            ],
            'quotes' => [
                'total_count' => $quoteCount,
                'converted_count' => $convertedQuotes,
                'conversion_rate' => round($conversionRate, 2),
            ],
            'receivables' => [
                'open_count' => $openCount,
                'open_balance' => $openBalance,
                'overdue_count' => $receivablesOverdue,
                'settled_this_period' => $settledThisPeriod,
            ],
            'cost_analysis' => $costAnalysis,
            'top_products' => $topProducts->values(),
        ]);
    }

    public function timeline(Request $request, Client $cliente): JsonResponse
    {
        $limit = min((int) $request->integer('limit', 30), 100);

        $sales = Sale::query()
            ->where('client_id', $cliente->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'total', 'created_at'])
            ->map(fn ($s) => [
                'type' => 'venda',
                'id' => $s->id,
                'amount' => (float) $s->total,
                'date' => $s->created_at->toDateString(),
                'timestamp' => $s->created_at,
            ]);

        $quotes = Quote::query()
            ->where('client_id', $cliente->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'total', 'created_at', 'status'])
            ->map(fn ($q) => [
                'type' => 'orcamento',
                'id' => $q->id,
                'amount' => (float) $q->total,
                'date' => $q->created_at->toDateString(),
                'status' => $q->status,
                'timestamp' => $q->created_at,
            ]);

        $receivables = Receivable::query()
            ->where('client_id', $cliente->id)
            ->orderByDesc('due_date')
            ->limit($limit)
            ->get(['id', 'amount_total', 'due_date', 'status'])
            ->map(fn ($r) => [
                'type' => 'recebivelPendente',
                'id' => $r->id,
                'amount' => (float) $r->amount_total,
                'date' => $r->due_date?->toDateString(),
                'status' => $r->status,
                'timestamp' => $r->due_date,
            ]);

        $all = $sales->concat($quotes)->concat($receivables)
            ->sortByDesc('timestamp')
            ->values();

        return response()->json($all);
    }

    public function revenue(Request $request, Client $cliente): JsonResponse
    {
        $monthsBack = min((int) $request->integer('months_back', 12), 60);
        $startDate = now()->subMonths($monthsBack)->startOfMonth();
        $endDate = now()->endOfMonth();

        $sales = Sale::query()
            ->where('client_id', $cliente->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $revenue = $sales
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m'))
            ->map(function ($group, $month) {
                return [
                    'month' => $month,
                    'sales_count' => $group->count(),
                    'total_amount' => (float) $group->sum('total'),
                ];
            })
            ->sortBy('month')
            ->values();

        return response()->json($revenue);
    }

    private function calculateCostAnalysis(mixed $sales): array
    {
        $totalRevenue = (float) $sales->sum('total');
        if ($totalRevenue === 0) {
            return [
                'estimated_revenue' => 0,
                'estimated_cost' => 0,
                'estimated_margin' => 0,
                'estimated_margin_percentage' => 0,
            ];
        }

        // Estimativa simplificada: 60% da receita é custo (pode ser ajustado conforme negócio)
        $estimatedCost = $totalRevenue * 0.60;
        $estimatedMargin = $totalRevenue - $estimatedCost;
        $marginPercentage = ($estimatedMargin / $totalRevenue) * 100;

        return [
            'estimated_revenue' => $totalRevenue,
            'estimated_cost' => $estimatedCost,
            'estimated_margin' => $estimatedMargin,
            'estimated_margin_percentage' => round($marginPercentage, 2),
        ];
    }
}
