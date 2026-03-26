<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashEntry;
use App\Models\Payable;
use App\Services\PayableService;
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

class PayableController extends Controller
{
    public function __construct(private readonly PayableService $payableService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        if (!Schema::hasTable('payables')) {
            return view('payables.index', [
                'payables' => new LengthAwarePaginator([], 0, 20),
                'summary' => [
                    'open_count' => 0,
                    'overdue_count' => 0,
                    'open_balance' => 0,
                    'paid_today' => 0,
                ],
                'filters' => $filters,
                'setupRequired' => true,
            ]);
        }

        $payables = $this->payableQuery($filters)
            ->paginate(20)
            ->withQueryString();

        $paidToday = 0.0;
        if (Schema::hasTable('cash_entries')) {
            $paidToday = (float) (CashEntry::query()
                ->where('type', 'saida')
                ->whereDate('occurred_at', now()->toDateString())
                ->sum('amount') ?? 0);
        }

        $summary = [
            'open_count' => (int) Payable::query()->whereIn('status', ['aberto', 'parcial'])->count(),
            'overdue_count' => (int) Payable::query()
                ->whereIn('status', ['aberto', 'parcial'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'open_balance' => (float) (Payable::query()->whereIn('status', ['aberto', 'parcial'])->sum('balance') ?? 0),
            'paid_today' => $paidToday,
        ];

        return view('payables.index', [
            'payables' => $payables,
            'summary' => $summary,
            'filters' => $filters,
            'setupRequired' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('payables')) {
            return back()->withErrors(['payable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:80'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'amount_total' => ['required', 'numeric', 'gt:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->payableService->create($data, $request->user()?->id);
        } catch (DomainException $e) {
            return back()->withErrors(['payable' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Conta a pagar criada com sucesso!');
    }

    public function update(Request $request, Payable $payable): RedirectResponse
    {
        if (!Schema::hasTable('payables')) {
            return back()->withErrors(['payable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:80'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payable->update([
            'vendor_name' => $data['vendor_name'],
            'description' => $data['description'],
            'category' => $data['category'] ?? 'geral',
            'document_number' => $data['document_number'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Conta a pagar atualizada com sucesso!');
    }

    public function settle(Request $request, Payable $payable): RedirectResponse
    {
        if (!Schema::hasTable('payables')) {
            return back()->withErrors(['payable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->payableService->settle(
                payable: $payable,
                amount: (float) $data['amount'],
                userId: $request->user()?->id,
                paidAt: !empty($data['paid_at']) ? now()->parse((string) $data['paid_at']) : now(),
                notes: $data['notes'] ?? null,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['payable' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Pagamento registrado com sucesso!');
    }

    public function cancel(Request $request, Payable $payable): RedirectResponse
    {
        if (!Schema::hasTable('payables')) {
            return back()->withErrors(['payable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->payableService->cancel($payable, $data['notes'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['payable' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Conta a pagar cancelada com sucesso!');
    }

    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        if (!Schema::hasTable('payables')) {
            return back()->withErrors(['payable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $filename = 'contas-a-pagar-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($filters): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['Titulo', 'Fornecedor', 'Descricao', 'Categoria', 'Status', 'Vencimento', 'Total', 'Pago', 'Saldo', 'Observacoes'], ';');

            $this->payableQuery($filters)
                ->chunk(500, function ($rows) use ($output): void {
                    foreach ($rows as $payable) {
                        $isOverdue = in_array($payable->status, ['aberto', 'parcial'], true)
                            && $payable->due_date !== null
                            && $payable->due_date->lt(now()->startOfDay());
                        $statusLabel = $isOverdue ? 'vencido' : $payable->status;

                        fputcsv($output, [
                            (string) $payable->id,
                            $payable->vendor_name,
                            $payable->description,
                            $payable->category,
                            $statusLabel,
                            $payable->due_date?->format('d/m/Y') ?? '',
                            number_format((float) $payable->amount_total, 2, '.', ''),
                            number_format((float) $payable->amount_paid, 2, '.', ''),
                            number_format((float) $payable->balance, 2, '.', ''),
                            $payable->notes ?? '',
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
        if (!Schema::hasTable('payables')) {
            return back()->withErrors(['payable' => 'Modulo financeiro ainda nao inicializado. Execute as migracoes.']);
        }

        $filters = $this->extractFilters($request);
        $payables = $this->payableQuery($filters)->get();

        $pdf = Pdf::loadView('pdf.payables-report', [
            'payables' => $payables,
            'filters' => $filters,
        ])->setPaper('a4');

        return $pdf->stream('contas-a-pagar-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'category' => trim((string) $request->query('category', '')),
            'due_from' => (string) $request->query('due_from', ''),
            'due_to' => (string) $request->query('due_to', ''),
            'min_balance' => is_numeric($request->query('min_balance')) ? (string) $request->query('min_balance') : '',
            'max_balance' => is_numeric($request->query('max_balance')) ? (string) $request->query('max_balance') : '',
        ];
    }

    private function payableQuery(array $filters): Builder
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $category = $filters['category'] ?? '';
        $dueFrom = $filters['due_from'] ?? '';
        $dueTo = $filters['due_to'] ?? '';
        $minBalance = $filters['min_balance'] ?? '';
        $maxBalance = $filters['max_balance'] ?? '';

        return Payable::query()
            ->with(['createdBy', 'settledBy'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->when($status === 'vencido', function ($query) {
                $query
                    ->whereIn('status', ['aberto', 'parcial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString());
            })
            ->when(in_array($status, ['aberto', 'parcial', 'quitado', 'cancelado'], true), fn ($query) => $query->where('status', $status))
            ->when($category !== '', fn ($query) => $query->where('category', 'like', "%{$category}%"))
            ->when($dueFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dueFrom))
            ->when($dueTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dueTo))
            ->when(is_numeric($minBalance), fn ($query) => $query->where('balance', '>=', (float) $minBalance))
            ->when(is_numeric($maxBalance), fn ($query) => $query->where('balance', '<=', (float) $maxBalance))
            ->orderByRaw("CASE WHEN status = 'quitado' THEN 2 ELSE 1 END")
            ->orderBy('due_date')
            ->orderByDesc('id');
    }
}
