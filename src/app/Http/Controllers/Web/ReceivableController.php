<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Receivable;
use App\Services\ReceivableService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceivableController extends Controller
{
    public function __construct(private readonly ReceivableService $receivableService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        if (!Schema::hasTable('receivables')) {
            return view('receivables.index', [
                'receivables' => new LengthAwarePaginator([], 0, 20),
                'summary' => [
                    'open_count' => 0,
                    'overdue_count' => 0,
                    'open_balance' => 0,
                    'settled_today' => 0,
                ],
                'filters' => $filters,
                'setupRequired' => true,
            ]);
        }

        $receivables = $this->receivableQuery($filters)
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'open_count' => (int) Receivable::query()->whereIn('status', ['aberto', 'parcial'])->count(),
            'overdue_count' => (int) Receivable::query()
                ->whereIn('status', ['aberto', 'parcial'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'open_balance' => (float) (Receivable::query()->whereIn('status', ['aberto', 'parcial'])->sum('balance') ?? 0),
            'settled_today' => (float) (Receivable::query()->whereDate('updated_at', now()->toDateString())->sum('amount_paid') ?? 0),
        ];

        return view('receivables.index', [
            'receivables' => $receivables,
            'summary' => $summary,
            'filters' => $filters,
            'setupRequired' => false,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        if (!Schema::hasTable('receivables')) {
            return back()->withErrors(['receivable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $filename = 'contas-a-receber-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($filters): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['Titulo', 'Venda', 'Parcela', 'Cliente', 'Status', 'Vencimento', 'Total', 'Pago', 'Saldo', 'Observacoes'], ';');

            $this->receivableQuery($filters)
                ->chunk(500, function ($rows) use ($output): void {
                    foreach ($rows as $receivable) {
                        $isOverdue = in_array($receivable->status, ['aberto', 'parcial'], true)
                            && $receivable->due_date !== null
                            && $receivable->due_date->lt(now()->startOfDay());
                        $statusLabel = $isOverdue ? 'vencido' : $receivable->status;

                        fputcsv($output, [
                            (string) $receivable->id,
                            (string) ($receivable->sale_id ?? ''),
                            ((string) $receivable->installment_number).'/'.((string) $receivable->installment_count),
                            $receivable->client?->name ?? 'Sem cliente',
                            $statusLabel,
                            $receivable->due_date?->format('d/m/Y') ?? '',
                            number_format((float) $receivable->amount_total, 2, '.', ''),
                            number_format((float) $receivable->amount_paid, 2, '.', ''),
                            number_format((float) $receivable->balance, 2, '.', ''),
                            $receivable->notes ?? '',
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
        if (!Schema::hasTable('receivables')) {
            return back()->withErrors(['receivable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $receivables = $this->receivableQuery($filters)->get();

        $pdf = Pdf::loadView('pdf.receivables-report', [
            'receivables' => $receivables,
            'filters' => $filters,
        ])->setPaper('a4');

        return $pdf->stream('contas-a-receber-'.now()->format('Ymd-His').'.pdf');
    }

    public function update(Request $request, Receivable $receivable): RedirectResponse
    {
        if (!Schema::hasTable('receivables')) {
            return back()->withErrors(['receivable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $receivable->update([
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Titulo atualizado com sucesso!');
    }

    public function split(Request $request, Receivable $receivable): RedirectResponse
    {
        if (!Schema::hasTable('receivables')) {
            return back()->withErrors(['receivable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'installments' => ['required', 'integer', 'min:2', 'max:24'],
            'first_due_date' => ['required', 'date'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        try {
            $this->receivableService->splitIntoInstallments(
                receivable: $receivable,
                installments: (int) $data['installments'],
                firstDueDate: now()->parse((string) $data['first_due_date']),
                intervalDays: (int) $data['interval_days'],
                userId: $request->user()?->id,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['receivable' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Titulo parcelado com sucesso!');
    }

    public function settle(Request $request, Receivable $receivable): RedirectResponse
    {
        if (!Schema::hasTable('receivables')) {
            return back()->withErrors(['receivable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->receivableService->settle(
                receivable: $receivable,
                amount: (float) $data['amount'],
                userId: $request->user()?->id,
                paidAt: !empty($data['paid_at']) ? now()->parse((string) $data['paid_at']) : now(),
                notes: $data['notes'] ?? null,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['receivable' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Baixa registrada com sucesso!');
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'due_from' => (string) $request->query('due_from', ''),
            'due_to' => (string) $request->query('due_to', ''),
            'min_balance' => is_numeric($request->query('min_balance')) ? (string) $request->query('min_balance') : '',
            'max_balance' => is_numeric($request->query('max_balance')) ? (string) $request->query('max_balance') : '',
        ];
    }

    private function receivableQuery(array $filters): Builder
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $dueFrom = $filters['due_from'] ?? '';
        $dueTo = $filters['due_to'] ?? '';
        $minBalance = $filters['min_balance'] ?? '';
        $maxBalance = $filters['max_balance'] ?? '';

        return Receivable::query()
            ->with(['client', 'sale'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->whereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('sale', fn ($saleQuery) => $saleQuery->where('id', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'vencido', function ($query) {
                $query
                    ->whereIn('status', ['aberto', 'parcial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString());
            })
            ->when(in_array($status, ['aberto', 'parcial', 'quitado', 'cancelado'], true), fn ($query) => $query->where('status', $status))
            ->when($dueFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dueFrom))
            ->when($dueTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dueTo))
            ->when(is_numeric($minBalance), fn ($query) => $query->where('balance', '>=', (float) $minBalance))
            ->when(is_numeric($maxBalance), fn ($query) => $query->where('balance', '<=', (float) $maxBalance))
            ->orderByRaw("CASE WHEN status = 'quitado' THEN 2 ELSE 1 END")
            ->orderBy('due_date')
            ->orderByDesc('id');
    }
}