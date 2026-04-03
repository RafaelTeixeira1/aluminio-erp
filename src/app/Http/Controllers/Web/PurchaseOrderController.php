<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\CatalogItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService
    ) {}

    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier', 'items']);

        // Filtro por status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtro por fornecedor
        if ($request->has('supplier_id') && $request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Busca por número do pedido
        if ($request->has('search') && $request->search) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        $purchaseOrders = $query
            ->latest('created_at')
            ->paginate(15);

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
            'status' => $request->status,
            'supplier_id' => $request->supplier_id,
            'search' => $request->search,
        ]);
    }

    public function create(Request $request): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $catalogItems = CatalogItem::where('is_active', true)->orderBy('name')->get();
        
        $selectedSupplier = null;
        if ($request->has('supplier_id')) {
            $selectedSupplier = Supplier::find($request->supplier_id);
        }

        return view('purchase-orders.form', [
            'purchaseOrder' => null,
            'suppliers' => $suppliers,
            'catalogItems' => $catalogItems,
            'selectedSupplier' => $selectedSupplier,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        try {
            $this->purchaseOrderService->create(
                $request->validated(),
                Auth::id()
            );

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Pedido de compra criado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao criar pedido: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'items.catalogItem', 'items.receipts']);

        return view('purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function receiveItem(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $request->validate([
            'purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'quantity_received' => ['required', 'numeric', 'min:0.01'],
            'received_at' => ['nullable', 'date'],
        ]);

        try {
            $this->purchaseOrderService->receiveItem(
                $purchaseOrder,
                $request->purchase_order_item_id,
                $request->quantity_received,
                Auth::id(),
                $request->received_at ? \Carbon\Carbon::parse($request->received_at) : null,
                $request->notes
            );

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Item recebido com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao receber item: ' . $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->purchaseOrderService->cancel($purchaseOrder);

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Pedido cancelado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }
}
