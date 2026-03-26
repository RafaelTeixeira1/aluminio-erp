<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receivable;
use App\Services\ReceivableService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function __construct(private readonly ReceivableService $receivableService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);
        $status = $request->string('status')->toString();
        $clientId = (int) ($request->integer('client_id') ?? 0);
        $overdue = $request->boolean('overdue', false);
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query = Receivable::query()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($clientId > 0, fn ($q) => $q->where('client_id', $clientId))
            ->when($overdue, function ($q) {
                $q->whereIn('status', ['aberto', 'parcial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString());
            });

        if (in_array($sortBy, ['created_at', 'due_date', 'amount_total'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $receivables = $query->with('client:id,name')
            ->with('sale:id,total')
            ->paginate($perPage);

        return response()->json($receivables);
    }

    public function show(Receivable $titulo): JsonResponse
    {
        $titulo->load([
            'client:id,name,phone,email,document',
            'sale:id,total,created_at',
            'createdBy:id,name',
            'settledBy:id,name',
        ]);

        return response()->json($titulo);
    }

    public function settle(Request $request, Receivable $titulo): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'settled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $receivable = $this->receivableService->settle(
                $titulo,
                (float) $data['amount'],
                $request->user()?->id,
                !empty($data['settled_at']) ? now()->parse((string) $data['settled_at']) : null,
                $data['notes'] ?? null
            );

            return response()->json($receivable);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        $startDate = Carbon::parse((string) $request->query('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse((string) $request->query('end_date', now()->endOfMonth()->toDateString()));

        $open = (int) Receivable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $overdue = (int) Receivable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $openBalance = (float) (Receivable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('balance') ?? 0);

        $settledThisPeriod = (float) (Receivable::query()
            ->where('status', 'quitado')
            ->whereBetween('settled_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->sum('amount_paid') ?? 0);

        return response()->json([
            'open_count' => $open,
            'overdue_count' => $overdue,
            'open_balance' => $openBalance,
            'settled_this_period' => $settledThisPeriod,
        ]);
    }

    public function byClient(Request $request, int $clientId): JsonResponse
    {
        $receivables = Receivable::query()
            ->where('client_id', $clientId)
            ->orderByDesc('due_date')
            ->with('sale:id,total')
            ->get();

        return response()->json($receivables);
    }
}
