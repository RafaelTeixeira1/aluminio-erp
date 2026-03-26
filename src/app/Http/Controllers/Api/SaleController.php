<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\SaleService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $sales = Sale::query()
            ->with(['client', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($perPage);

        $payload = $sales->toArray();

        if (!$canViewFinancial) {
            $payload['data'] = array_map(fn (array $sale): array => $this->maskFinancialFromSalePayload($sale), $payload['data']);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sale = Sale::query()->create([
            'client_id' => $data['client_id'] ?? null,
            'discount' => $data['discount'] ?? 0,
            'status' => 'pendente',
            'created_by_user_id' => $request->user()?->id,
        ]);

        $payload = $sale->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromSalePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload, 201);
    }

    public function show(Sale $venda): JsonResponse
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';
        $payload = $venda->load(['client', 'quote', 'items.catalogItem'])->toArray();

        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromSalePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function replaceItems(Request $request, Sale $venda): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', 'in:produto,acessorio'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.metadata' => ['nullable', 'array'],
        ]);

        try {
            $sale = $this->saleService->replaceItems($venda, $data['items']);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = $sale->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromSalePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function confirm(Request $request, Sale $venda): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        try {
            $sale = $this->saleService->confirmSale($venda, $request->user()?->id);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = $sale->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromSalePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function maskFinancialFromSalePayload(array $payload): array
    {
        foreach (['subtotal', 'discount', 'total'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = null;
            }
        }

        if (isset($payload['items']) && is_array($payload['items'])) {
            $payload['items'] = array_map(function ($item): array {
                if (!is_array($item)) {
                    return [];
                }

                if (array_key_exists('unit_price', $item)) {
                    $item['unit_price'] = null;
                }

                if (array_key_exists('line_total', $item)) {
                    $item['line_total'] = null;
                }

                return $item;
            }, $payload['items']);
        }

        if (isset($payload['quote']) && is_array($payload['quote'])) {
            foreach (['subtotal', 'discount', 'total'] as $field) {
                if (array_key_exists($field, $payload['quote'])) {
                    $payload['quote'][$field] = null;
                }
            }
        }

        return $payload;
    }
}
