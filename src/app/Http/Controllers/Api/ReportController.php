<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function salesByPeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'in:produto,acessorio'],
        ]);

        $salesQuery = Sale::query()
            ->whereBetween('created_at', [$data['start_date'].' 00:00:00', $data['end_date'].' 23:59:59'])
            ->with(['client:id,name']);

        $this->applyCommonSaleFilters($salesQuery, $data);

        $sales = $salesQuery
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'period' => $data,
            'total_sales' => $sales->count(),
            'total_amount' => (float) $sales->sum('total'),
            'items' => $sales,
        ]);
    }

    public function bestSellingProducts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'in:produto,acessorio'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($data['limit'] ?? 20);

        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('catalog_items', 'catalog_items.id', '=', 'sale_items.catalog_item_id')
            ->selectRaw('sale_items.catalog_item_id')
            ->selectRaw('COALESCE(catalog_items.name, sale_items.item_name) as item_name')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.line_total) as total_value')
            ->groupBy('sale_items.catalog_item_id', 'item_name')
            ->orderByDesc('total_quantity')
            ->limit($limit);

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $query->whereBetween('sales.created_at', [$data['start_date'].' 00:00:00', $data['end_date'].' 23:59:59']);
        }

        if (!empty($data['client_id'])) {
            $query->where('sales.client_id', (int) $data['client_id']);
        }

        if (!empty($data['category_id'])) {
            $query->where('catalog_items.category_id', (int) $data['category_id']);
        }

        if (!empty($data['item_type'])) {
            $itemType = (string) $data['item_type'];
            $query->where(function ($nested) use ($itemType) {
                $nested
                    ->where('catalog_items.item_type', $itemType)
                    ->orWhere(function ($fallback) use ($itemType) {
                        $fallback
                            ->whereNull('catalog_items.id')
                            ->where('sale_items.item_type', $itemType);
                    });
            });
        }

        return response()->json($query->get());
    }

    public function lowStockProducts(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'in:produto,acessorio'],
        ]);

        $items = CatalogItem::query()
            ->whereColumn('stock', '<=', 'stock_minimum')
            ->when(!empty($data['category_id']), fn ($query) => $query->where('category_id', (int) $data['category_id']))
            ->when(!empty($data['item_type']), fn ($query) => $query->where('item_type', (string) $data['item_type']))
            ->orderBy('name')
            ->get();

        if (!$canViewFinancial) {
            $items = $items->map(function (CatalogItem $item) {
                $payload = $item->toArray();
                if (array_key_exists('price', $payload)) {
                    $payload['price'] = null;
                }

                return $payload;
            })->values();
        }

        return response()->json([
            'can_view_financial' => $canViewFinancial,
            'filters' => [
                'category_id' => $data['category_id'] ?? null,
                'item_type' => $data['item_type'] ?? null,
            ],
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'in:produto,acessorio'],
        ]);

        $baseQuery = Sale::query()
            ->whereBetween('created_at', [$data['start_date'].' 00:00:00', $data['end_date'].' 23:59:59']);

        $this->applyCommonSaleFilters($baseQuery, $data);

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('SUM(total) as gross_total')
            ->selectRaw('SUM(discount) as total_discount')
            ->first();

        $daily = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($item) => [
                'day' => $item->day,
                'total' => (float) $item->total,
            ])
            ->values();

        return response()->json([
            'period' => $data,
            'sales_count' => (int) ($summary?->sales_count ?? 0),
            'gross_total' => (float) ($summary?->gross_total ?? 0),
            'total_discount' => (float) ($summary?->total_discount ?? 0),
            'daily' => $daily,
        ]);
    }

    public function revenuePdf(Request $request): Response
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['nullable', 'in:produto,acessorio'],
        ]);

        $baseQuery = Sale::query()
            ->whereBetween('created_at', [$data['start_date'].' 00:00:00', $data['end_date'].' 23:59:59']);

        $this->applyCommonSaleFilters($baseQuery, $data);

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('SUM(total) as gross_total')
            ->selectRaw('SUM(discount) as total_discount')
            ->first();

        $daily = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($item) => [
                'day' => $item->day,
                'total' => (float) $item->total,
            ])
            ->values();

        $pdf = Pdf::loadView('pdf.revenue-report', [
            'period' => $data,
            'sales_count' => (int) ($summary?->sales_count ?? 0),
            'gross_total' => (float) ($summary?->gross_total ?? 0),
            'total_discount' => (float) ($summary?->total_discount ?? 0),
            'daily' => $daily,
        ])->setPaper('a4');

        return $pdf->stream('faturamento-'.$data['start_date'].'-'.$data['end_date'].'.pdf');
    }

    private function applyCommonSaleFilters(Builder $query, array $data): void
    {
        if (!empty($data['client_id'])) {
            $query->where('client_id', (int) $data['client_id']);
        }

        if (!empty($data['category_id']) || !empty($data['item_type'])) {
            $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : null;
            $itemType = !empty($data['item_type']) ? (string) $data['item_type'] : null;

            $query->whereExists(function ($exists) use ($categoryId, $itemType) {
                $exists
                    ->selectRaw('1')
                    ->from('sale_items')
                    ->leftJoin('catalog_items', 'catalog_items.id', '=', 'sale_items.catalog_item_id')
                    ->whereColumn('sale_items.sale_id', 'sales.id')
                    ->when($categoryId !== null, fn ($q) => $q->where('catalog_items.category_id', $categoryId))
                    ->when($itemType !== null, function ($q) use ($itemType) {
                        $q->where(function ($nested) use ($itemType) {
                            $nested
                                ->where('catalog_items.item_type', $itemType)
                                ->orWhere(function ($fallback) use ($itemType) {
                                    $fallback
                                        ->whereNull('catalog_items.id')
                                        ->where('sale_items.item_type', $itemType);
                                });
                        });
                    });
            });
        }
    }

    public function dre(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $sales = Sale::query()
            ->whereBetween('created_at', [$data['start_date'].' 00:00:00', $data['end_date'].' 23:59:59'])
            ->get();

        $grossRevenue = (float) $sales->sum('total');
        $totalDiscount = (float) $sales->sum('discount');
        $netRevenue = $grossRevenue - $totalDiscount;

        // Estimativa simplificada de COGS: 60% da receita
        $estimatedCogs = $netRevenue * 0.60;
        $grossProfit = $netRevenue - $estimatedCogs;
        $grossMargin = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0;

        // Custos operacionais (simplificado)
        $estimatedOperatingExpenses = $netRevenue * 0.20;
        $operatingProfit = $grossProfit - $estimatedOperatingExpenses;
        $operatingMargin = $netRevenue > 0 ? ($operatingProfit / $netRevenue) * 100 : 0;

        $netMargin = $netRevenue > 0 ? ($operatingProfit / $netRevenue) * 100 : 0;

        return response()->json([
            'period' => $data,
            'revenue' => [
                'gross' => $grossRevenue,
                'discount' => $totalDiscount,
                'net' => $netRevenue,
            ],
            'costs' => [
                'cogs_estimated' => $estimatedCogs,
                'gross_profit' => $grossProfit,
                'gross_margin_pct' => round($grossMargin, 2),
            ],
            'expenses' => [
                'operating_estimated' => $estimatedOperatingExpenses,
                'operating_profit' => $operatingProfit,
                'operating_margin_pct' => round($operatingMargin, 2),
            ],
            'profit' => [
                'net' => $operatingProfit,
                'net_margin_pct' => round($netMargin, 2),
            ],
        ]);
    }

    public function marginByCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $margins = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('catalog_items', 'catalog_items.id', '=', 'sale_items.catalog_item_id')
            ->leftJoin('categories', 'categories.id', '=', 'catalog_items.category_id')
            ->whereBetween('sales.created_at', [$data['start_date'].' 00:00:00', $data['end_date'].' 23:59:59'])
            ->selectRaw('COALESCE(categories.name, "sem categoria") as category')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.line_total) as total_revenue')
            ->selectRaw('SUM(sale_items.line_total) * 0.60 as estimated_cost')
            ->selectRaw('SUM(sale_items.line_total) * 0.40 as estimated_margin')
            ->selectRaw('(SUM(sale_items.line_total) * 0.40 / SUM(sale_items.line_total)) * 100 as margin_pct')
            ->groupBy('category')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json($margins);
    }

    public function profitByPeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'months_back' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $monthsBack = (int) ($data['months_back'] ?? 12);
        $startDate = now()->subMonths($monthsBack)->startOfMonth();

        $sales = Sale::query()
            ->where('created_at', '>=', $startDate)
            ->get();

        $profits = $sales
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m'))
            ->map(function ($group, $month) {
                $gross = (float) $group->sum('total');
                $discount = (float) $group->sum('discount');
                $net = $gross - $discount;
                $estimatedCogs = $net * 0.60;
                $grossProfit = $net * 0.40;
                $operatingProfit = $net * 0.20;
                $margin = $net > 0 ? ($operatingProfit / $net) * 100 : 0;

                return [
                    'month' => $month,
                    'sales_count' => $group->count(),
                'revenue' => [
                    'gross' => $gross,
                    'discount' => $discount,
                    'net' => $net,
                ],
                'profit' => [
                    'estimated_cogs' => $estimatedCogs,
                    'gross_profit' => $grossProfit,
                    'operating_profit' => $operatingProfit,
                    'margin_pct' => round($margin, 2),
                ],
                ];
            })
            ->sortBy('month')
            ->values();

        return response()->json($profits);
    }
}
