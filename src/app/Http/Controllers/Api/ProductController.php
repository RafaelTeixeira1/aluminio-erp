<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $search = $request->string('search')->toString();
        $type = $request->string('tipo_item')->toString();

        $products = CatalogItem::query()
            ->with('category')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($type !== '', fn ($q) => $q->where('item_type', $type))
            ->orderBy('name')
            ->paginate($perPage);

        $payload = $products->toArray();
        if (!$canViewFinancial) {
            $payload['data'] = array_map(fn (array $item): array => $this->maskProductFinancial($item), $payload['data']);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['required', 'in:produto,acessorio'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'stock_minimum' => ['nullable', 'numeric', 'min:0'],
            'weight_per_meter_kg' => ['nullable', 'numeric', 'min:0'],
            'material' => ['nullable', 'string', 'max:120'],
            'finish' => ['nullable', 'string', 'max:120'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0'],
            'standard_width_mm' => ['nullable', 'numeric', 'min:0'],
            'standard_height_mm' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:120'],
            'product_line' => ['nullable', 'string', 'max:120'],
            'technical_notes' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = 'storage/'.$request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        $product = CatalogItem::query()->create($data);
        $payload = $product->load('category')->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskProductFinancial($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload, 201);
    }

    public function show(CatalogItem $produto): JsonResponse
    {
        $canViewFinancial = (string) (request()->user()?->profile ?? '') !== 'vendedor';
        $payload = $produto->load('category')->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskProductFinancial($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function update(Request $request, CatalogItem $produto): JsonResponse
    {
        $canViewFinancial = (string) ($request->user()?->profile ?? '') !== 'vendedor';
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_type' => ['sometimes', 'required', 'in:produto,acessorio'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_minimum' => ['sometimes', 'required', 'numeric', 'min:0'],
            'weight_per_meter_kg' => ['nullable', 'numeric', 'min:0'],
            'material' => ['nullable', 'string', 'max:120'],
            'finish' => ['nullable', 'string', 'max:120'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0'],
            'standard_width_mm' => ['nullable', 'numeric', 'min:0'],
            'standard_height_mm' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:120'],
            'product_line' => ['nullable', 'string', 'max:120'],
            'technical_notes' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_image') && !empty($produto->image_path)) {
            Storage::disk('public')->delete(str_replace('storage/', '', (string) $produto->image_path));
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if (!empty($produto->image_path)) {
                Storage::disk('public')->delete(str_replace('storage/', '', (string) $produto->image_path));
            }

            $data['image_path'] = 'storage/'.$request->file('image')->store('products', 'public');
        }

        unset($data['image'], $data['remove_image']);

        $produto->update($data);
        $payload = $produto->fresh()->load('category')->toArray();
        if (!$canViewFinancial) {
            $payload = $this->maskProductFinancial($payload);
        }

        $payload['can_view_financial'] = $canViewFinancial;

        return response()->json($payload);
    }

    public function destroy(CatalogItem $produto): JsonResponse
    {
        $produto->update(['is_active' => false]);

        return response()->json(status: 204);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function maskProductFinancial(array $payload): array
    {
        if (array_key_exists('price', $payload)) {
            $payload['price'] = null;
        }

        return $payload;
    }
}
