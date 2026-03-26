<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Services\PayableService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function __construct(private readonly PayableService $payableService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);
        $status = $request->string('status')->toString();
        $vendor = trim((string) $request->query('vendor', ''));
        $overdue = $request->boolean('overdue', false);
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query = Payable::query()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($vendor !== '', fn ($q) => $q->where('vendor_name', 'like', "%{$vendor}%"))
            ->when($overdue, function ($q) {
                $q->whereIn('status', ['aberto', 'parcial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString());
            });

        if (in_array($sortBy, ['created_at', 'due_date', 'amount_total'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $payables = $query->paginate($perPage);

        return response()->json($payables);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:160'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'description' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:80'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'amount_total' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payable = $this->payableService->create($data, $request->user()?->id);

            return response()->json($payable, 201);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(Payable $conta): JsonResponse
    {
        $conta->load(['createdBy:id,name', 'settledBy:id,name', 'supplier:id,name', 'purchaseOrder:id,order_number']);

        return response()->json($conta);
    }

    public function update(Request $request, Payable $conta): JsonResponse
    {
        if (in_array($conta->status, ['quitado', 'cancelado'], true)) {
            return response()->json(['error' => 'Conta nao pode ser editada nesse status.'], 422);
        }

        $data = $request->validate([
            'vendor_name' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'required', 'string', 'max:200'],
            'category' => ['sometimes', 'required', 'string', 'max:80'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $conta->update($data);

        return response()->json($conta->fresh());
    }

    public function settle(Request $request, Payable $conta): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payable = $this->payableService->settle(
                $conta,
                (float) $data['amount'],
                $request->user()?->id,
                !empty($data['paid_at']) ? now()->parse((string) $data['paid_at']) : null,
                $data['notes'] ?? null
            );

            return response()->json($payable);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        $startDate = Carbon::parse((string) $request->query('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse((string) $request->query('end_date', now()->endOfMonth()->toDateString()));

        $open = (int) Payable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $overdue = (int) Payable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $openBalance = (float) (Payable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('balance') ?? 0);

        $paidThisPeriod = (float) (Payable::query()
            ->where('status', 'quitado')
            ->whereBetween('paid_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->sum('amount_paid') ?? 0);

        return response()->json([
            'open_count' => $open,
            'overdue_count' => $overdue,
            'open_balance' => $openBalance,
            'paid_this_period' => $paidThisPeriod,
        ]);
    }
}
