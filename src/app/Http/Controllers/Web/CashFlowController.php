<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashEntry;
use App\Models\Payable;
use App\Models\Receivable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashFlowController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        if (!Schema::hasTable('cash_entries')) {
            return view('financial.cashflow', [
                'entries' => new LengthAwarePaginator([], 0, 30),
                'summary' => [
                    'inflow' => 0,
                    'outflow' => 0,
                    'net' => 0,
                    'receivables_open' => 0,
                    'payables_open' => 0,
                    'projected_net' => 0,
                ],
                'filters' => $filters,
                'setupRequired' => true,
            ]);
        }

        $entries = $this->entryQuery($filters)
            ->paginate(30)
            ->withQueryString();

        $summaryQuery = $this->entryQuery($filters);
        $inflow = (float) (clone $summaryQuery)->where('type', 'entrada')->sum('amount');
        $outflow = (float) (clone $summaryQuery)->where('type', 'saida')->sum('amount');

        $receivablesOpen = Schema::hasTable('receivables')
            ? (float) (Receivable::query()->whereIn('status', ['aberto', 'parcial'])->sum('balance') ?? 0)
            : 0;

        $payablesOpen = Schema::hasTable('payables')
            ? (float) (Payable::query()->whereIn('status', ['aberto', 'parcial'])->sum('balance') ?? 0)
            : 0;

        $summary = [
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net' => $inflow - $outflow,
            'receivables_open' => $receivablesOpen,
            'payables_open' => $payablesOpen,
            'projected_net' => $receivablesOpen - $payablesOpen,
        ];

        return view('financial.cashflow', [
            'entries' => $entries,
            'summary' => $summary,
            'filters' => $filters,
            'setupRequired' => false,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        if (!Schema::hasTable('cash_entries')) {
            return back()->withErrors(['cashflow' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $filename = 'fluxo-de-caixa-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($filters): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['Lancamento', 'Tipo', 'Origem', 'Descricao', 'Valor', 'Data', 'Usuario', 'Observacoes'], ';');

            $this->entryQuery($filters)
                ->chunk(500, function ($rows) use ($output): void {
                    foreach ($rows as $entry) {
                        fputcsv($output, [
                            (string) $entry->id,
                            $entry->type,
                            trim((string) $entry->origin_type).' #'.(string) ($entry->origin_id ?? ''),
                            $entry->description,
                            number_format((float) $entry->amount, 2, '.', ''),
                            $entry->occurred_at?->format('d/m/Y H:i') ?? '',
                            $entry->user?->name ?? '-',
                            $entry->notes ?? '',
                        ], ';');
                    }
                });

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        if (!Schema::hasTable('cash_entries')) {
            return back()->withErrors(['cashflow' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $entries = $this->entryQuery($filters)->get();

        $pdf = Pdf::loadView('pdf.cashflow-report', [
            'entries' => $entries,
            'filters' => $filters,
        ])->setPaper('a4');

        return $pdf->stream('fluxo-de-caixa-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'type' => (string) $request->query('type', ''),
            'origin_type' => trim((string) $request->query('origin_type', '')),
            'period_from' => (string) $request->query('period_from', ''),
            'period_to' => (string) $request->query('period_to', ''),
            'search' => trim((string) $request->query('search', '')),
        ];
    }

    private function entryQuery(array $filters): Builder
    {
        $type = $filters['type'] ?? '';
        $originType = $filters['origin_type'] ?? '';
        $periodFrom = $filters['period_from'] ?? '';
        $periodTo = $filters['period_to'] ?? '';
        $search = $filters['search'] ?? '';

        return CashEntry::query()
            ->with('user')
            ->when(in_array($type, ['entrada', 'saida'], true), fn ($query) => $query->where('type', $type))
            ->when($originType !== '', fn ($query) => $query->where('origin_type', 'like', "%{$originType}%"))
            ->when($periodFrom !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $periodFrom))
            ->when($periodTo !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $periodTo))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }
}
