<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $search = $request->string('search')->toString();
        $phone = $request->string('phone')->toString();

        $clients = Client::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($phone !== '', fn ($q) => $q->where('phone', $phone))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'document' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $client = Client::query()->create($data);

        return response()->json($client, 201);
    }

    public function show(Client $cliente): JsonResponse
    {
        return response()->json($cliente);
    }

    public function update(Request $request, Client $cliente): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'document' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $cliente->update($data);

        return response()->json($cliente->fresh());
    }

    public function destroy(Client $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json(status: 204);
    }

    public function history(Client $cliente): JsonResponse
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';
        $cliente->load([
            'quotes' => fn ($q) => $q->latest()->limit(30),
            'sales' => fn ($q) => $q->latest()->limit(30),
        ]);

        $quotes = $cliente->quotes->map(function ($quote) use ($canViewFinancial) {
            $item = $quote->toArray();
            if (!$canViewFinancial) {
                foreach (['subtotal', 'discount', 'total'] as $field) {
                    if (array_key_exists($field, $item)) {
                        $item[$field] = null;
                    }
                }
            }

            return $item;
        })->values();

        $sales = $cliente->sales->map(function ($sale) use ($canViewFinancial) {
            $item = $sale->toArray();
            if (!$canViewFinancial) {
                foreach (['subtotal', 'discount', 'total'] as $field) {
                    if (array_key_exists($field, $item)) {
                        $item[$field] = null;
                    }
                }
            }

            return $item;
        })->values();

        return response()->json([
            'can_view_financial' => $canViewFinancial,
            'client' => $cliente,
            'quotes' => $quotes,
            'sales' => $sales,
        ]);
    }
}
