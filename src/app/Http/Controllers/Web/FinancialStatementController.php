<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialStatementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        if (!Schema::hasTable('cash_entries')) {
            return view('financial.dre', [
                'summary' => [
                    'gross_revenue' => 0,
                    'operational_expenses' => 0,
                    'net_profit' => 0,
                    'profit_margin' => 0,
                ],
                'incomeByOrigin' => collect(),
                'expensesByCategory' => collect(),
                'entries' => new LengthAwarePaginator([], 0, 20),
                'filters' => $filters,
                'setupRequired' => true,
            ]);
        }

        $baseQuery = $this->entryQuery($filters);

        $grossRevenue = (float) (clone $baseQuery)->where('type', 'entrada')->sum('amount');
        $operationalExpenses = (float) (clone $baseQuery)->where('type', 'saida')->sum('amount');
        $netProfit = $grossRevenue - $operationalExpenses;
        $profitMargin = $grossRevenue > 0 ? round(($netProfit / $grossRevenue) * 100, 2) : 0.0;

        $incomeByOrigin = (clone $baseQuery)
            ->where('type', 'entrada')
            ->selectRaw("COALESCE(NULLIF(origin_type, ''), 'outros') as origin")
            ->selectRaw('SUM(amount) as total')
            ->groupBy('origin')
            ->orderByDesc('total')
            ->get();

        $expensesByCategory = $this->expensesByCategory($filters);

        $entries = (clone $baseQuery)
            ->with('user')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('financial.dre', [
            'summary' => [
                'gross_revenue' => $grossRevenue,
                'operational_expenses' => $operationalExpenses,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
            ],
            'incomeByOrigin' => $incomeByOrigin,
            'expensesByCategory' => $expensesByCategory,
            'entries' => $entries,
            'filters' => $filters,
            'setupRequired' => false,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        if (!Schema::hasTable('cash_entries')) {
            return back()->withErrors(['dre' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $summary = $this->summary($filters);
        $incomeByOrigin = $this->incomeByOrigin($filters);
        $expensesByCategory = $this->expensesByCategory($filters);

        $filename = 'dre-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($summary, $incomeByOrigin, $expensesByCategory): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['DRE Simplificado', 'Valor'], ';');
            fputcsv($output, ['Receita Bruta', number_format((float) $summary['gross_revenue'], 2, '.', '')], ';');
            fputcsv($output, ['Despesas Operacionais', number_format((float) $summary['operational_expenses'], 2, '.', '')], ';');
            fputcsv($output, ['Lucro Liquido', number_format((float) $summary['net_profit'], 2, '.', '')], ';');
            fputcsv($output, ['Margem (%)', number_format((float) $summary['profit_margin'], 2, '.', '')], ';');

            fputcsv($output, [], ';');
            fputcsv($output, ['Receitas por Origem', 'Valor'], ';');
            foreach ($incomeByOrigin as $row) {
                fputcsv($output, [(string) $row->origin, number_format((float) $row->total, 2, '.', '')], ';');
            }

            fputcsv($output, [], ';');
            fputcsv($output, ['Despesas por Categoria', 'Valor'], ';');
            foreach ($expensesByCategory as $row) {
                fputcsv($output, [(string) $row->category, number_format((float) $row->total, 2, '.', '')], ';');
            }

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        if (!Schema::hasTable('cash_entries')) {
            return back()->withErrors(['dre' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $summary = $this->summary($filters);
        $incomeByOrigin = $this->incomeByOrigin($filters);
        $expensesByCategory = $this->expensesByCategory($filters);

        $pdf = Pdf::loadView('pdf.dre-report', [
            'summary' => $summary,
            'incomeByOrigin' => $incomeByOrigin,
            'expensesByCategory' => $expensesByCategory,
            'filters' => $filters,
        ])->setPaper('a4');

        return $pdf->stream('dre-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'period_from' => (string) $request->query('period_from', now()->startOfMonth()->toDateString()),
            'period_to' => (string) $request->query('period_to', now()->endOfMonth()->toDateString()),
            'search' => trim((string) $request->query('search', '')),
        ];
    }

    private function entryQuery(array $filters)
    {
        $periodFrom = $filters['period_from'] ?? now()->startOfMonth()->toDateString();
        $periodTo = $filters['period_to'] ?? now()->endOfMonth()->toDateString();
        $search = $filters['search'] ?? '';

        return CashEntry::query()
            ->whereDate('occurred_at', '>=', $periodFrom)
            ->whereDate('occurred_at', '<=', $periodTo)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            });
    }

    /**
     * @return array<string, float>
     */
    private function summary(array $filters): array
    {
        $baseQuery = $this->entryQuery($filters);

        $grossRevenue = (float) (clone $baseQuery)->where('type', 'entrada')->sum('amount');
        $operationalExpenses = (float) (clone $baseQuery)->where('type', 'saida')->sum('amount');
        $netProfit = $grossRevenue - $operationalExpenses;
        $profitMargin = $grossRevenue > 0 ? round(($netProfit / $grossRevenue) * 100, 2) : 0.0;

        return [
            'gross_revenue' => $grossRevenue,
            'operational_expenses' => $operationalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];
    }

    private function incomeByOrigin(array $filters): Collection
    {
        $baseQuery = $this->entryQuery($filters);

        return (clone $baseQuery)
            ->where('type', 'entrada')
            ->selectRaw("COALESCE(NULLIF(origin_type, ''), 'outros') as origin")
            ->selectRaw('SUM(amount) as total')
            ->groupBy('origin')
            ->orderByDesc('total')
            ->get();
    }

    private function expensesByCategory(array $filters): Collection
    {
        $periodFrom = $filters['period_from'] ?? now()->startOfMonth()->toDateString();
        $periodTo = $filters['period_to'] ?? now()->endOfMonth()->toDateString();
        $search = $filters['search'] ?? '';

        if (!Schema::hasTable('payables')) {
            return collect();
        }

        $payableCategories = DB::table('cash_entries')
            ->leftJoin('payables', function ($join) {
                $join
                    ->on('cash_entries.origin_id', '=', 'payables.id')
                    ->where('cash_entries.origin_type', '=', 'payable');
            })
            ->where('cash_entries.type', 'saida')
            ->whereDate('cash_entries.occurred_at', '>=', $periodFrom)
            ->whereDate('cash_entries.occurred_at', '<=', $periodTo)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('cash_entries.description', 'like', "%{$search}%")
                        ->orWhere('cash_entries.notes', 'like', "%{$search}%")
                        ->orWhere('payables.category', 'like', "%{$search}%");
                });
            })
            ->selectRaw("COALESCE(NULLIF(payables.category, ''), 'outros') as category")
            ->selectRaw('SUM(cash_entries.amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return collect($payableCategories);
    }
}
