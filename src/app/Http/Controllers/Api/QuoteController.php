<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\QuoteService;
use App\Services\SaleService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
        private readonly SaleService $saleService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $quotes = Quote::query()
            ->with(['client', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->latest()
            ->paginate($perPage);

        $payload = $quotes->toArray();

        if (!$canViewFinancial) {
            $payload['data'] = array_map(fn (array $quote): array => $this->maskFinancialFromQuotePayload($quote), $payload['data']);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'status' => ['nullable', 'in:aberto,aprovado,convertido,cancelado,expirado'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $quote = Quote::query()->create([
            ...$data,
            'status' => $data['status'] ?? 'aberto',
            'created_by_user_id' => $request->user()?->id,
        ]);

        $payload = $quote->load('client')->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromQuotePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload, 201);
    }

    public function show(Quote $orcamento): JsonResponse
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';
        $payload = $orcamento->load(['client', 'items.catalogItem', 'pieceDesigns', 'designSketches'])->toArray();

        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromQuotePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function update(Request $request, Quote $orcamento): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'client_id' => ['sometimes', 'required', 'integer', 'exists:clients,id'],
            'status' => ['sometimes', 'required', 'in:aberto,aprovado,convertido,cancelado,expirado'],
            'discount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $orcamento->update($data);
        $orcamento = $this->quoteService->recalculate($orcamento->fresh());

        $payload = $orcamento->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromQuotePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function destroy(Quote $orcamento): JsonResponse
    {
        $orcamento->delete();

        return response()->json(status: 204);
    }

    public function replaceItems(Request $request, Quote $orcamento): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['nullable', 'integer', 'exists:catalog_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.item_type' => ['nullable', 'in:produto,acessorio'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.width_mm' => ['nullable', 'numeric', 'gt:0'],
            'items.*.height_mm' => ['nullable', 'numeric', 'gt:0'],
            'items.*.metadata' => ['nullable', 'array'],
        ]);

        $quote = $this->quoteService->replaceItems($orcamento, $data['items']);

        $payload = $quote->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromQuotePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function addPieceDesigns(Request $request, Quote $orcamento): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'designs' => ['required', 'array', 'min:1'],
            'designs.*.title' => ['nullable', 'string', 'max:160'],
            'designs.*.width_mm' => ['nullable', 'numeric', 'gt:0'],
            'designs.*.height_mm' => ['nullable', 'numeric', 'gt:0'],
            'designs.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'designs.*.canvas_json' => ['nullable', 'string'],
            'designs.*.preview_png' => ['nullable', 'string'],
            'designs.*.notes' => ['nullable', 'string', 'max:500'],
            'designs.*.data_json' => ['nullable', 'array'],
        ]);

        foreach ($data['designs'] as $index => $design) {
            $canvasJson = trim((string) ($design['canvas_json'] ?? ''));
            if ($canvasJson === '') {
                $canvasJson = json_encode($design['data_json'] ?? ['legacy_piece_design' => true], JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            $title = trim((string) ($design['title'] ?? ''));
            if ($title === '') {
                $title = 'Desenho tecnico #'.($index + 1).' - Orcamento #'.$orcamento->id;
            }

            $orcamento->designSketches()->create([
                'created_by_user_id' => $request->user()?->id,
                'title' => $title,
                'width_mm' => isset($design['width_mm']) ? (float) $design['width_mm'] : null,
                'height_mm' => isset($design['height_mm']) ? (float) $design['height_mm'] : null,
                'canvas_json' => $canvasJson,
                'preview_png' => !empty($design['preview_png']) ? (string) $design['preview_png'] : null,
                'notes' => !empty($design['notes']) ? (string) $design['notes'] : null,
            ]);
        }

        $payload = $orcamento->fresh(['designSketches', 'pieceDesigns', 'items'])->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskFinancialFromQuotePayload($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function convertToSale(Request $request, Quote $orcamento): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        try {
            $sale = $this->saleService->createFromQuote($orcamento, $request->user()?->id);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $payload = $sale->toArray();
        if (!$canViewFinancial) {
            foreach (['subtotal', 'discount', 'total'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $payload[$field] = null;
                }
            }
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload, 201);
    }

    public function expireOverdue(): JsonResponse
    {
        $affected = $this->quoteService->expireOverdueQuotes();

        return response()->json([
            'message' => 'Orcamentos vencidos atualizados.',
            'affected' => $affected,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function maskFinancialFromQuotePayload(array $payload): array
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

        return $payload;
    }
}
