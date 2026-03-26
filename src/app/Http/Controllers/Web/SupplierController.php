<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Supplier::query();

        // Busca por nome ou documento
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%");
        }

        // Filtro por status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $suppliers = $query
            ->withCount('purchaseOrders')
            ->with('purchaseOrders:id,supplier_id,total,status')
            ->orderBy('name')
            ->paginate(15);

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'search' => $request->search,
            'status' => $request->status,
        ]);
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => null]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Fornecedor criado com sucesso!');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'purchaseOrders' => fn ($q) => $q->latest('created_at')->limit(10),
            'purchaseOrders.items',
        ]);

        return view('suppliers.show', ['supplier' => $supplier]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', ['supplier' => $supplier]);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        // Soft delete usando is_active
        $supplier->update(['is_active' => false]);

        return redirect()->route('suppliers.index')->with('success', 'Fornecedor desativado com sucesso!');
    }

    public function restore(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => true]);

        return redirect()->route('suppliers.index')->with('success', 'Fornecedor reativado com sucesso!');
    }
}
