<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Category;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ManagementController extends Controller
{
    public function dashboard(): View
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';

        $todayStart = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $weekStart = Carbon::now()->subDays(6)->startOfDay();

        $salesTodayCount = Schema::hasTable('sales')
            ? (int) Sale::query()->whereDate('created_at', $todayStart)->count()
            : 0;

        $revenueToday = $canViewFinancial && Schema::hasTable('sales')
            ? (float) (Sale::query()->whereDate('created_at', $todayStart)->sum('total') ?? 0)
            : 0.0;

        $openQuotes = Schema::hasTable('quotes')
            ? (int) DB::table('quotes')->where('status', 'aberto')->count()
            : 0;

        $overdueQuotes = Schema::hasTable('quotes')
            ? (int) DB::table('quotes')
                ->whereIn('status', ['aberto', 'aprovado'])
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '<', $todayStart)
                ->count()
            : 0;

        // Stock KPIs
        $criticalStockCount = Schema::hasTable('catalog_items')
            ? (int) CatalogItem::query()->where('is_active', true)->whereColumn('stock', '<=', 'stock_minimum')->count()
            : 0;

        $outOfStockCount = Schema::hasTable('catalog_items')
            ? (int) CatalogItem::query()->where('is_active', true)->where('stock', '<=', 0)->count()
            : 0;

        // Purchase Orders KPIs
        $pendingPurchaseOrders = Schema::hasTable('purchase_orders')
            ? (int) DB::table('purchase_orders')->whereIn('status', ['aberto', 'parcial'])->count()
            : 0;

        // Financial KPIs
        $overduePayables = 0;
        $totalOverduePayables = 0.0;
        $overdueReceivables = 0;
        $totalOverdueReceivables = 0.0;

        if ($canViewFinancial) {
            if (Schema::hasTable('payables')) {
                $overduePayables = (int) DB::table('payables')
                    ->where('status', 'aberto')
                    ->whereDate('due_date', '<', $todayStart)
                    ->count();

                $totalOverduePayables = (float) (DB::table('payables')
                    ->where('status', 'aberto')
                    ->whereDate('due_date', '<', $todayStart)
                    ->sum(DB::raw('balance')) ?? 0);
            }

            if (Schema::hasTable('receivables')) {
                $overdueReceivables = (int) DB::table('receivables')
                    ->where('status', 'aberto')
                    ->whereDate('due_date', '<', $todayStart)
                    ->count();

                $totalOverdueReceivables = (float) (DB::table('receivables')
                    ->where('status', 'aberto')
                    ->whereDate('due_date', '<', $todayStart)
                    ->sum(DB::raw('balance')) ?? 0);
            }
        }

        // Sales trend
        $weeklySales = Schema::hasTable('sales')
            ? DB::table('sales')
                ->selectRaw('DATE(created_at) as day, COUNT(*) as qty, SUM(total) as total')
                ->where('created_at', '>=', $weekStart)
                ->groupBy('day')
                ->orderBy('day')
                ->get()
            : collect();

        $topProductsMonth = Schema::hasTable('sale_items')
            ? DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->leftJoin('catalog_items', 'catalog_items.id', '=', 'sale_items.catalog_item_id')
                ->selectRaw('COALESCE(catalog_items.name, sale_items.item_name) as item_name')
                ->selectRaw('SUM(sale_items.quantity) as qty')
                ->where('sales.created_at', '>=', $monthStart)
                ->groupBy('catalog_items.name', 'sale_items.item_name')
                ->orderByDesc('qty')
                ->limit(3)
                ->get()
            : collect();

        $recentSales = Schema::hasTable('sales')
            ? DB::table('sales')
                ->leftJoin('clients', 'clients.id', '=', 'sales.client_id')
                ->select('sales.id', 'sales.total', 'sales.status', 'sales.created_at', 'clients.name as client_name')
                ->orderByDesc('sales.created_at')
                ->limit(5)
                ->get()
            : collect();

        return view('dashboard.index', [
            'metrics' => [
                'sales_today' => $salesTodayCount,
                'revenue_today' => $revenueToday,
                'open_quotes' => $openQuotes,
                'overdue_quotes' => $overdueQuotes,
                'critical_stock' => $criticalStockCount,
                'out_of_stock' => $outOfStockCount,
                'pending_purchases' => $pendingPurchaseOrders,
                'overdue_payables' => $overduePayables,
                'total_overdue_payables' => $totalOverduePayables,
                'overdue_receivables' => $overdueReceivables,
                'total_overdue_receivables' => $totalOverdueReceivables,
            ],
            'weekly_sales' => $weeklySales,
            'top_products' => $topProductsMonth,
            'recent_sales' => $recentSales,
            'can_view_financial' => $canViewFinancial,
        ]);
    }

    public function reports(Request $request): View
    {
        return view('reports.index', $this->buildReportsData($request));
    }

    public function exportReportsPdf(Request $request): Response
    {
        $data = $this->buildReportsData($request);
        $pdf = Pdf::loadView('pdf.reports-overview', $data)->setPaper('a4');

        return $pdf->stream('relatorios-'.now()->format('Ymd-His').'.pdf');
    }

    public function profile(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], (string) $user->password)) {
                return back()->withErrors(['current_password' => 'Senha atual inválida.'])->withInput();
            }

            $user->password = $validated['new_password'];
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Perfil atualizado com sucesso!');
    }

    public function users(): View
    {
        if (!Schema::hasTable('users')) {
            return view('users.index', [
                'users' => collect(),
                'summary' => [
                    'total' => 0,
                    'active' => 0,
                    'inactive' => 0,
                    'stale' => 0,
                ],
            ]);
        }

        $users = User::query()->orderBy('name')->get();

        $staleSince = Carbon::now()->subDays(30);

        $summary = [
            'total' => (int) $users->count(),
            'active' => (int) $users->where('active', true)->count(),
            'inactive' => (int) $users->where('active', false)->count(),
            'stale' => (int) $users->filter(fn (User $item) => $item->updated_at !== null && $item->updated_at->lt($staleSince))->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'summary' => $summary,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportsData(Request $request): array
    {
        $profile = (string) ($request->user()?->profile ?? '');
        $canViewFinancial = $profile !== 'vendedor';

        if (!Schema::hasTable('sales')) {
            return [
                'metrics' => [
                    'revenue' => 0.0,
                    'sales_count' => 0,
                    'conversion_rate' => 0,
                    'low_stock_count' => 0,
                    'active_products' => 0,
                    'out_of_stock_count' => 0,
                    'categories_count' => 0,
                ],
                'top_products' => collect(),
                'status_summary' => collect(),
                'categories' => collect(),
                'report_mode' => $canViewFinancial ? 'management' : 'operational',
                'can_view_financial' => $canViewFinancial,
                'filters' => [
                    'period' => '30d',
                    'date_from' => null,
                    'date_to' => null,
                    'status' => '',
                    'category_id' => null,
                ],
            ];
        }

        $filters = [
            'period' => (string) $request->query('period', '30d'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'status' => (string) $request->query('status', ''),
            'category_id' => is_numeric($request->query('category_id')) ? (int) $request->query('category_id') : null,
        ];

        [$startDate, $endDate] = $this->resolvePeriodRange($filters['period'], $filters['date_from'], $filters['date_to']);

        $salesQuery = Sale::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when(in_array($filters['status'], ['pendente', 'confirmada'], true), fn ($query) => $query->where('status', $filters['status']))
            ->when(is_int($filters['category_id']), function ($query) use ($filters) {
                $query->whereHas('items.catalogItem', fn ($itemQuery) => $itemQuery->where('category_id', $filters['category_id']));
            });

        $salesCount = (int) (clone $salesQuery)->count();
        $revenue = (float) ((clone $salesQuery)->sum('total') ?? 0);

        $quoteCount = Schema::hasTable('quotes')
            ? (int) DB::table('quotes')->whereBetween('created_at', [$startDate, $endDate])->count()
            : 0;

        $convertedQuoteCount = (clone $salesQuery)->whereNotNull('quote_id')->count();

        $conversionRate = $quoteCount > 0
            ? (int) round(($convertedQuoteCount / $quoteCount) * 100)
            : 0;

        $lowStockCount = Schema::hasTable('catalog_items')
            ? (int) CatalogItem::query()->whereColumn('stock', '<=', 'stock_minimum')->count()
            : 0;

        $activeProducts = Schema::hasTable('catalog_items')
            ? (int) CatalogItem::query()->where('is_active', true)->count()
            : 0;

        $outOfStockCount = Schema::hasTable('catalog_items')
            ? (int) CatalogItem::query()->where('stock', '<=', 0)->count()
            : 0;

        $categoriesCount = Schema::hasTable('categories')
            ? (int) Category::query()->where('active', true)->count()
            : 0;

        $topProducts = $canViewFinancial
            ? $this->topProducts($startDate, $endDate, $filters['status'], $filters['category_id'])
            : $this->criticalStockProducts($filters['category_id']);

        $statusSummary = $canViewFinancial
            ? $this->statusSummary($startDate, $endDate, $filters['category_id'])
            : collect();

        $categories = Category::query()->where('active', true)->orderBy('name')->get();

        return [
            'metrics' => [
                'revenue' => $revenue,
                'sales_count' => $salesCount,
                'conversion_rate' => $conversionRate,
                'low_stock_count' => $lowStockCount,
                'active_products' => $activeProducts,
                'out_of_stock_count' => $outOfStockCount,
                'categories_count' => $categoriesCount,
            ],
            'top_products' => $topProducts,
            'status_summary' => $statusSummary,
            'categories' => $categories,
            'report_mode' => $canViewFinancial ? 'management' : 'operational',
            'can_view_financial' => $canViewFinancial,
            'filters' => $filters,
        ];
    }

    /**
     * @return array<int, Carbon>
     */
    private function resolvePeriodRange(string $period, mixed $dateFrom, mixed $dateTo): array
    {
        return match ($period) {
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
            'last_month' => [Carbon::now()->subMonthNoOverflow()->startOfMonth(), Carbon::now()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfDay()],
            'custom' => [
                is_string($dateFrom) && $dateFrom !== '' ? Carbon::parse($dateFrom)->startOfDay() : Carbon::now()->subDays(30)->startOfDay(),
                is_string($dateTo) && $dateTo !== '' ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfDay(),
            ],
            default => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
        };
    }

    private function topProducts(Carbon $startDate, Carbon $endDate, string $status = '', ?int $categoryId = null): Collection
    {
        if (!Schema::hasTable('sale_items')) {
            return collect();
        }

        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('catalog_items', 'catalog_items.id', '=', 'sale_items.catalog_item_id')
            ->selectRaw('COALESCE(catalog_items.name, sale_items.item_name) as item_name')
            ->selectRaw('SUM(sale_items.line_total) as total_value')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->when(in_array($status, ['pendente', 'confirmada'], true), fn ($query) => $query->where('sales.status', $status))
            ->when(is_int($categoryId), fn ($query) => $query->where('catalog_items.category_id', $categoryId))
            ->groupBy('catalog_items.name', 'sale_items.item_name')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();
    }

    private function statusSummary(Carbon $startDate, Carbon $endDate, ?int $categoryId = null): Collection
    {
        return Sale::query()
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total_value')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when(is_int($categoryId), function ($query) use ($categoryId) {
                $query->whereHas('items.catalogItem', fn ($itemQuery) => $itemQuery->where('category_id', $categoryId));
            })
            ->groupBy('status')
            ->orderByDesc('total_value')
            ->get();
    }

    private function criticalStockProducts(?int $categoryId = null): Collection
    {
        if (!Schema::hasTable('catalog_items')) {
            return collect();
        }

        return CatalogItem::query()
            ->select('catalog_items.id', 'catalog_items.name', 'catalog_items.stock', 'catalog_items.stock_minimum')
            ->when(is_int($categoryId), fn ($query) => $query->where('category_id', $categoryId))
            ->where('is_active', true)
            ->orderByRaw('(stock - stock_minimum) asc')
            ->orderBy('stock')
            ->limit(8)
            ->get();
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        Category::create($validated);

        return back()->with('success', 'Categoria criada com sucesso!');
    }

    public function updateCategory(Request $request, Category $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$categoria->id,
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $categoria->update($validated);

        return back()->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroyCategory(Category $categoria): RedirectResponse
    {
        $categoria->delete();

        return back()->with('success', 'Categoria removida com sucesso!');
    }
}
