<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashEntry;
use App\Models\Payable;
use App\Models\Receivable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 30), 100);
        $type = $request->string('type')->toString();
        $origin = trim((string) $request->query('origin', ''));
        $sortBy = $request->string('sort_by', 'occurred_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query = CashEntry::query()
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($origin !== '', fn ($q) => $q->where('origin_type', $origin));

        if (in_array($sortBy, ['occurred_at', 'amount'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $entries = $query->with('user:id,name')
            ->paginate($perPage);

        return response()->json($entries);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:entrada,saida'],
            'origin_type' => ['nullable', 'string', 'max:60'],
            'origin_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = CashEntry::query()->create([
            'type' => (string) $data['type'],
            'origin_type' => $data['origin_type'] ?? null,
            'origin_id' => $data['origin_id'] ?? null,
            'description' => (string) $data['description'],
            'amount' => (float) $data['amount'],
            'occurred_at' => now()->parse($data['occurred_at']),
            'user_id' => $request->user()?->id,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json($entry, 201);
    }

    public function show(CashEntry $entrada): JsonResponse
    {
        $entrada->load('user:id,name');

        return response()->json($entrada);
    }

    public function destroy(CashEntry $entrada): JsonResponse
    {
        $entrada->delete();

        return response()->json(status: 204);
    }

    public function summary(Request $request): JsonResponse
    {
        $startDate = Carbon::parse((string) $request->query('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse((string) $request->query('end_date', now()->endOfMonth()->toDateString()));

        $query = CashEntry::query()
            ->whereBetween('occurred_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        $inflow = (float) (clone $query)->where('type', 'entrada')->sum('amount');
        $outflow = (float) (clone $query)->where('type', 'saida')->sum('amount');

        $receivablesOpen = (float) (Receivable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->sum('balance') ?? 0);

        $payablesOpen = (float) (Payable::query()
            ->whereIn('status', ['aberto', 'parcial'])
            ->sum('balance') ?? 0);

        return response()->json([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'actual' => [
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $inflow - $outflow,
            ],
            'projected' => [
                'inflow' => $receivablesOpen,
                'outflow' => $payablesOpen,
                'net' => $receivablesOpen - $payablesOpen,
            ],
            'total' => [
                'net' => ($inflow - $outflow) + ($receivablesOpen - $payablesOpen),
            ],
        ]);
    }

    public function byType(Request $request): JsonResponse
    {
        $startDate = Carbon::parse((string) $request->query('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse((string) $request->query('end_date', now()->endOfMonth()->toDateString()));

        $byType = CashEntry::query()
            ->whereBetween('occurred_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw("type")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('type')
            ->get();

        return response()->json($byType);
    }

    public function byOrigin(Request $request): JsonResponse
    {
        $startDate = Carbon::parse((string) $request->query('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse((string) $request->query('end_date', now()->endOfMonth()->toDateString()));

        $byOrigin = CashEntry::query()
            ->whereBetween('occurred_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw("COALESCE(NULLIF(origin_type, ''), 'manual') as origin")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('origin')
            ->orderByDesc('total')
            ->get();

        return response()->json($byOrigin);
    }
}
