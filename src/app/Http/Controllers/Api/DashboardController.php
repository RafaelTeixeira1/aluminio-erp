<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';
        $today = Carbon::today();

        $salesToday = Sale::query()
            ->whereDate('created_at', $today)
            ->count();

        $confirmedSalesToday = Sale::query()
            ->where('status', 'confirmada')
            ->whereDate('confirmed_at', $today)
            ->count();

        $revenueToday = $canViewFinancial
            ? Sale::query()
                ->where('status', 'confirmada')
                ->whereDate('confirmed_at', $today)
                ->sum('total')
            : 0;

        $openQuotes = Quote::query()
            ->whereIn('status', ['aberto', 'aprovado'])
            ->count();

        $overdueQuotes = Quote::query()
            ->whereIn('status', ['aberto', 'aprovado'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today)
            ->count();

        $lowStockItems = CatalogItem::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_minimum')
            ->count();

        $activeProducts = CatalogItem::query()
            ->where('is_active', true)
            ->count();

        return response()->json([
            'date' => $today->toDateString(),
            'can_view_financial' => $canViewFinancial,
            'sales_today_count' => (int) $salesToday,
            'confirmed_sales_today_count' => (int) $confirmedSalesToday,
            'revenue_today' => (float) $revenueToday,
            'open_quotes_count' => (int) $openQuotes,
            'overdue_quotes_count' => (int) $overdueQuotes,
            'low_stock_items_count' => (int) $lowStockItems,
            'active_products_count' => (int) $activeProducts,
        ]);
    }

    public function activities(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';

        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
            'page' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'in:all,sales,quotes,stock_movements'],
            'sort' => ['nullable', 'in:created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'search' => ['nullable', 'string', 'max:100'],
            'sale_status' => ['nullable', 'in:pendente,confirmada,cancelada'],
            'quote_status' => ['nullable', 'in:aberto,aprovado,convertido,cancelado,expirado'],
            'movement_type' => ['nullable', 'in:entrada,saida,ajuste'],
        ]);

        $limit = min(max((int) $request->integer('limit', 10), 1), 30);
        $page = max((int) ($data['page'] ?? 1), 1);
        $offset = ($page - 1) * $limit;
        $type = (string) ($data['type'] ?? 'all');
        $sort = (string) ($data['sort'] ?? 'created_at');
        $direction = (string) ($data['direction'] ?? 'desc');
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null;
        $fromDate = $from?->toDateString();
        $toDate = $to?->toDateString();
        $search = isset($data['search']) ? trim((string) $data['search']) : null;
        $saleStatus = isset($data['sale_status']) ? (string) $data['sale_status'] : null;
        $quoteStatus = isset($data['quote_status']) ? (string) $data['quote_status'] : null;
        $movementType = isset($data['movement_type']) ? (string) $data['movement_type'] : null;

        $recentSales = collect();
        $salesTotal = 0;

        if ($type === 'all' || $type === 'sales') {
            $salesQuery = Sale::query()
                ->with(['client:id,name'])
                ->when($search !== null && $search !== '', function ($query) use ($search) {
                    $query->whereHas('client', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
                })
                ->when($saleStatus !== null, fn ($query) => $query->where('status', $saleStatus))
                ->when($fromDate !== null, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
                ->when($toDate !== null, fn ($query) => $query->whereDate('created_at', '<=', $toDate));

            $salesTotal = (clone $salesQuery)->count();

            $recentSales = $salesQuery
                ->orderBy($sort, $direction)
                ->orderBy('id', $direction)
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function (Sale $sale) use ($canViewFinancial): array {
                    return [
                        'id' => $sale->id,
                        'status' => $sale->status,
                        'client_name' => $sale->client?->name,
                        'total' => $canViewFinancial ? (float) $sale->total : null,
                        'created_at' => $sale->created_at?->toIso8601String(),
                        'confirmed_at' => $sale->confirmed_at?->toIso8601String(),
                    ];
                })
                ->values();
        }

        $recentQuotes = collect();
        $quotesTotal = 0;

        if ($type === 'all' || $type === 'quotes') {
            $quotesQuery = Quote::query()
                ->with(['client:id,name'])
                ->when($search !== null && $search !== '', function ($query) use ($search) {
                    $query->whereHas('client', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
                })
                ->when($quoteStatus !== null, fn ($query) => $query->where('status', $quoteStatus))
                ->when($fromDate !== null, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
                ->when($toDate !== null, fn ($query) => $query->whereDate('created_at', '<=', $toDate));

            $quotesTotal = (clone $quotesQuery)->count();

            $recentQuotes = $quotesQuery
                ->orderBy($sort, $direction)
                ->orderBy('id', $direction)
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function (Quote $quote) use ($canViewFinancial): array {
                    return [
                        'id' => $quote->id,
                        'status' => $quote->status,
                        'client_name' => $quote->client?->name,
                        'total' => $canViewFinancial ? (float) $quote->total : null,
                        'valid_until' => $quote->valid_until?->toDateString(),
                        'created_at' => $quote->created_at?->toIso8601String(),
                    ];
                })
                ->values();
        }

        $recentStockMovements = collect();
        $stockMovementsTotal = 0;

        if ($type === 'all' || $type === 'stock_movements') {
            $stockMovementsQuery = StockMovement::query()
                ->with(['catalogItem:id,name,item_type', 'user:id,name'])
                ->when($search !== null && $search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested
                            ->whereHas('catalogItem', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
                    });
                })
                ->when($movementType !== null, fn ($query) => $query->where('movement_type', $movementType))
                ->when($fromDate !== null, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
                ->when($toDate !== null, fn ($query) => $query->whereDate('created_at', '<=', $toDate));

            $stockMovementsTotal = (clone $stockMovementsQuery)->count();

            $recentStockMovements = $stockMovementsQuery
                ->orderBy($sort, $direction)
                ->orderBy('id', $direction)
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function (StockMovement $movement): array {
                    return [
                        'id' => $movement->id,
                        'movement_type' => $movement->movement_type,
                        'origin_type' => $movement->origin_type,
                        'origin_id' => $movement->origin_id,
                        'item_name' => $movement->catalogItem?->name,
                        'item_type' => $movement->catalogItem?->item_type,
                        'user_name' => $movement->user?->name,
                        'quantity' => (float) $movement->quantity,
                        'stock_before' => (float) $movement->stock_before,
                        'stock_after' => (float) $movement->stock_after,
                        'created_at' => $movement->created_at?->toIso8601String(),
                    ];
                })
                ->values();
        }

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'can_view_financial' => $canViewFinancial,
            'limit' => $limit,
            'page' => $page,
            'filters' => [
                'type' => $type,
                'sort' => $sort,
                'direction' => $direction,
                'search' => $search,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
                'sale_status' => $saleStatus,
                'quote_status' => $quoteStatus,
                'movement_type' => $movementType,
            ],
            'pagination' => [
                'sales' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $salesTotal,
                    'has_more' => ($offset + $recentSales->count()) < $salesTotal,
                ],
                'quotes' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $quotesTotal,
                    'has_more' => ($offset + $recentQuotes->count()) < $quotesTotal,
                ],
                'stock_movements' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $stockMovementsTotal,
                    'has_more' => ($offset + $recentStockMovements->count()) < $stockMovementsTotal,
                ],
            ],
            'sales' => $recentSales,
            'quotes' => $recentQuotes,
            'stock_movements' => $recentStockMovements,
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';

        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'source' => ['nullable', 'in:all,sales,quotes,stock_movements'],
            'sort' => ['nullable', 'in:created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'search' => ['nullable', 'string', 'max:100'],
            'sale_status' => ['nullable', 'in:pendente,confirmada,cancelada'],
            'quote_status' => ['nullable', 'in:aberto,aprovado,convertido,cancelado,expirado'],
            'movement_type' => ['nullable', 'in:entrada,saida,ajuste'],
        ]);

        $limit = min(max((int) ($data['limit'] ?? 20), 1), 50);
        $page = max((int) ($data['page'] ?? 1), 1);
        $offset = ($page - 1) * $limit;
        $source = (string) ($data['source'] ?? 'all');
        $sort = (string) ($data['sort'] ?? 'created_at');
        $direction = (string) ($data['direction'] ?? 'desc');
        $search = isset($data['search']) ? trim((string) $data['search']) : null;
        $fromDate = isset($data['from']) ? Carbon::parse($data['from'])->toDateString() : null;
        $toDate = isset($data['to']) ? Carbon::parse($data['to'])->toDateString() : null;
        $saleStatus = isset($data['sale_status']) ? (string) $data['sale_status'] : null;
        $quoteStatus = isset($data['quote_status']) ? (string) $data['quote_status'] : null;
        $movementType = isset($data['movement_type']) ? (string) $data['movement_type'] : null;

        $queries = [];

        if ($source === 'all' || $source === 'sales') {
            $queries[] = DB::table('sales')
                ->leftJoin('clients', 'clients.id', '=', 'sales.client_id')
                ->selectRaw('sales.id as source_id')
                ->selectRaw("'sale' as activity_type")
                ->selectRaw('sales.created_at as created_at')
                ->selectRaw('sales.status as status')
                ->selectRaw('clients.name as client_name')
                ->selectRaw('NULL as item_name')
                ->selectRaw('NULL as user_name')
                ->selectRaw('sales.total as amount')
                ->selectRaw('NULL as quantity')
                ->selectRaw('NULL as movement_type')
                ->when($saleStatus !== null, fn ($query) => $query->where('sales.status', $saleStatus))
                ->when($fromDate !== null, fn ($query) => $query->whereDate('sales.created_at', '>=', $fromDate))
                ->when($toDate !== null, fn ($query) => $query->whereDate('sales.created_at', '<=', $toDate))
                ->when($search !== null && $search !== '', fn ($query) => $query->where('clients.name', 'like', '%'.$search.'%'));
        }

        if ($source === 'all' || $source === 'quotes') {
            $queries[] = DB::table('quotes')
                ->leftJoin('clients', 'clients.id', '=', 'quotes.client_id')
                ->selectRaw('quotes.id as source_id')
                ->selectRaw("'quote' as activity_type")
                ->selectRaw('quotes.created_at as created_at')
                ->selectRaw('quotes.status as status')
                ->selectRaw('clients.name as client_name')
                ->selectRaw('NULL as item_name')
                ->selectRaw('NULL as user_name')
                ->selectRaw('quotes.total as amount')
                ->selectRaw('NULL as quantity')
                ->selectRaw('NULL as movement_type')
                ->when($quoteStatus !== null, fn ($query) => $query->where('quotes.status', $quoteStatus))
                ->when($fromDate !== null, fn ($query) => $query->whereDate('quotes.created_at', '>=', $fromDate))
                ->when($toDate !== null, fn ($query) => $query->whereDate('quotes.created_at', '<=', $toDate))
                ->when($search !== null && $search !== '', fn ($query) => $query->where('clients.name', 'like', '%'.$search.'%'));
        }

        if ($source === 'all' || $source === 'stock_movements') {
            $queries[] = DB::table('stock_movements')
                ->leftJoin('catalog_items', 'catalog_items.id', '=', 'stock_movements.catalog_item_id')
                ->leftJoin('users', 'users.id', '=', 'stock_movements.user_id')
                ->selectRaw('stock_movements.id as source_id')
                ->selectRaw("'stock_movement' as activity_type")
                ->selectRaw('stock_movements.created_at as created_at')
                ->selectRaw('NULL as status')
                ->selectRaw('NULL as client_name')
                ->selectRaw('catalog_items.name as item_name')
                ->selectRaw('users.name as user_name')
                ->selectRaw('NULL as amount')
                ->selectRaw('stock_movements.quantity as quantity')
                ->selectRaw('stock_movements.movement_type as movement_type')
                ->when($movementType !== null, fn ($query) => $query->where('stock_movements.movement_type', $movementType))
                ->when($fromDate !== null, fn ($query) => $query->whereDate('stock_movements.created_at', '>=', $fromDate))
                ->when($toDate !== null, fn ($query) => $query->whereDate('stock_movements.created_at', '<=', $toDate))
                ->when($search !== null && $search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested
                            ->where('catalog_items.name', 'like', '%'.$search.'%')
                            ->orWhere('users.name', 'like', '%'.$search.'%');
                    });
                });
        }

        if ($queries === []) {
            return response()->json([
                'generated_at' => now()->toIso8601String(),
                'limit' => $limit,
                'page' => $page,
                'total' => 0,
                'has_more' => false,
                'filters' => [
                    'source' => $source,
                    'sort' => $sort,
                    'direction' => $direction,
                    'search' => $search,
                    'from' => $fromDate,
                    'to' => $toDate,
                    'sale_status' => $saleStatus,
                    'quote_status' => $quoteStatus,
                    'movement_type' => $movementType,
                ],
                'items' => [],
            ]);
        }

        $feedQuery = array_shift($queries);
        foreach ($queries as $query) {
            $feedQuery->unionAll($query);
        }

        $feedSubquery = DB::query()->fromSub($feedQuery, 'feed');
        $total = (clone $feedSubquery)->count();

        $items = (clone $feedSubquery)
            ->orderBy($sort, $direction)
            ->orderBy('source_id', $direction)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($canViewFinancial): array {
                return [
                    'id' => (int) $item->source_id,
                    'activity_type' => (string) $item->activity_type,
                    'created_at' => (string) $item->created_at,
                    'status' => $item->status,
                    'client_name' => $item->client_name,
                    'item_name' => $item->item_name,
                    'user_name' => $item->user_name,
                    'amount' => $canViewFinancial && $item->amount !== null ? (float) $item->amount : null,
                    'quantity' => $item->quantity !== null ? (float) $item->quantity : null,
                    'movement_type' => $item->movement_type,
                ];
            })
            ->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'can_view_financial' => $canViewFinancial,
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'has_more' => ($offset + $items->count()) < $total,
            'filters' => [
                'source' => $source,
                'sort' => $sort,
                'direction' => $direction,
                'search' => $search,
                'from' => $fromDate,
                'to' => $toDate,
                'sale_status' => $saleStatus,
                'quote_status' => $quoteStatus,
                'movement_type' => $movementType,
            ],
            'items' => $items,
        ]);
    }

    public function kpis(): JsonResponse
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';
        $today = Carbon::today();
        $week = 7;

        // Mercadoria/Estoque KPIs
        $criticalStockItems = CatalogItem::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_minimum')
            ->count();

        $lowStockItems = CatalogItem::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<', 'stock_minimum')
            ->count();

        $outOfStockItems = CatalogItem::query()
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        // Compras KPIs
        $pendingPurchaseOrders = DB::table('purchase_orders')
            ->whereIn('status', ['aberto', 'parcial'])
            ->count();

        $totalPurchasesOpen = $canViewFinancial
            ? DB::table('purchase_orders')
                ->whereIn('status', ['aberto', 'parcial'])
                ->sum('total')
            : 0;

        // Vendas KPIs - comparação de período
        $thisWeekSalesCount = Sale::query()
            ->where('created_at', '>=', Carbon::now()->subDays($week)->startOfDay())
            ->count();

        $lastWeekSalesCount = Sale::query()
            ->where('created_at', '>=', Carbon::now()->subDays($week * 2)->startOfDay())
            ->where('created_at', '<', Carbon::now()->subDays($week)->startOfDay())
            ->count();

        $thisWeekRevenue = $canViewFinancial
            ? Sale::query()
                ->where('status', 'confirmada')
                ->where('confirmed_at', '>=', Carbon::now()->subDays($week)->startOfDay())
                ->sum('total')
            : 0;

        $lastWeekRevenue = $canViewFinancial
            ? Sale::query()
                ->where('status', 'confirmada')
                ->where('confirmed_at', '>=', Carbon::now()->subDays($week * 2)->startOfDay())
                ->where('confirmed_at', '<', Carbon::now()->subDays($week)->startOfDay())
                ->sum('total')
            : 0;

        // Orçamentos KPIs
        $overdueQuotes = Quote::query()
            ->whereIn('status', ['aberto', 'aprovado'])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today)
            ->count();

        $convertedQuotesThisWeek = Quote::query()
            ->where('status', 'convertido')
            ->where('created_at', '>=', Carbon::now()->subDays($week)->startOfDay())
            ->count();

        // Financeiro KPIs (se pode ver)
        $overduePayables = 0;
        $totalPayablesOverdue = 0;
        $openPayables = 0;
        $totalPayablesOpen = 0;

        $overdueReceivables = 0;
        $totalReceivablesOverdue = 0;
        $openReceivables = 0;
        $totalReceivablesOpen = 0;

        if ($canViewFinancial && DB::getSchemaBuilder()->hasTable('payables')) {
            $overduePayables = DB::table('payables')
                ->where('status', 'aberto')
                ->whereDate('due_date', '<', $today)
                ->count();

            $totalPayablesOverdue = DB::table('payables')
                ->where('status', 'aberto')
                ->whereDate('due_date', '<', $today)
                ->sum(DB::raw('balance'));

            $openPayables = DB::table('payables')
                ->where('status', 'aberto')
                ->count();

            $totalPayablesOpen = DB::table('payables')
                ->where('status', 'aberto')
                ->sum(DB::raw('balance'));
        }

        if ($canViewFinancial && DB::getSchemaBuilder()->hasTable('receivables')) {
            $overdueReceivables = DB::table('receivables')
                ->where('status', 'aberto')
                ->whereDate('due_date', '<', $today)
                ->count();

            $totalReceivablesOverdue = DB::table('receivables')
                ->where('status', 'aberto')
                ->whereDate('due_date', '<', $today)
                ->sum(DB::raw('balance'));

            $openReceivables = DB::table('receivables')
                ->where('status', 'aberto')
                ->count();

            $totalReceivablesOpen = DB::table('receivables')
                ->where('status', 'aberto')
                ->sum(DB::raw('balance'));
        }

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'can_view_financial' => $canViewFinancial,
            'stock' => [
                'critical_count' => (int) $criticalStockItems,
                'low_count' => (int) $lowStockItems,
                'out_of_stock_count' => (int) $outOfStockItems,
                'total_alert' => (int) ($criticalStockItems + $lowStockItems + $outOfStockItems),
            ],
            'purchases' => [
                'pending_orders_count' => (int) $pendingPurchaseOrders,
                'total_pending_amount' => (float) ($totalPurchasesOpen ?? 0),
            ],
            'sales' => [
                'this_week_count' => (int) $thisWeekSalesCount,
                'last_week_count' => (int) $lastWeekSalesCount,
                'this_week_revenue' => (float) ($thisWeekRevenue ?? 0),
                'last_week_revenue' => (float) ($lastWeekRevenue ?? 0),
                'weekly_growth_percent' => $lastWeekSalesCount > 0
                    ? round((($thisWeekSalesCount - $lastWeekSalesCount) / $lastWeekSalesCount) * 100, 2)
                    : 0,
            ],
            'quotes' => [
                'overdue_count' => (int) $overdueQuotes,
                'converted_this_week' => (int) $convertedQuotesThisWeek,
            ],
            'financial' => $canViewFinancial ? [
                'payables' => [
                    'overdue_count' => (int) $overduePayables,
                    'overdue_total' => (float) ($totalPayablesOverdue ?? 0),
                    'open_count' => (int) $openPayables,
                    'open_total' => (float) ($totalPayablesOpen ?? 0),
                ],
                'receivables' => [
                    'overdue_count' => (int) $overdueReceivables,
                    'overdue_total' => (float) ($totalReceivablesOverdue ?? 0),
                    'open_count' => (int) $openReceivables,
                    'open_total' => (float) ($totalReceivablesOpen ?? 0),
                ],
                'cash_flow' => [
                    'to_receive' => (float) ($totalReceivablesOpen ?? 0),
                    'to_pay' => (float) ($totalPayablesOpen ?? 0),
                    'net_balance' => (float) (($totalReceivablesOpen ?? 0) - ($totalPayablesOpen ?? 0)),
                ],
            ] : null,
        ]);
    }
}
