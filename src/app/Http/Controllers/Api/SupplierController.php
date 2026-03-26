<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $search = trim((string) $request->query('search', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('document', 'like', '%'.$search.'%')
                        ->orWhere('contact_name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplier = Supplier::query()->create($data);

        return response()->json($supplier, 201);
    }

    public function show(Supplier $fornecedor): JsonResponse
    {
        return response()->json($fornecedor->loadCount(['purchaseOrders', 'payables']));
    }

    public function update(Request $request, Supplier $fornecedor): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fornecedor->update($data);

        return response()->json($fornecedor->fresh());
    }

    public function destroy(Supplier $fornecedor): JsonResponse
    {
        $fornecedor->update(['is_active' => false]);

        return response()->json(status: 204);
    }
}
