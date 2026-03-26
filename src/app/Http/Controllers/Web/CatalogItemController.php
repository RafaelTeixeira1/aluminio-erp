<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\StoreCatalogItemRequest;
use App\Models\CatalogItem;
use App\Models\CatalogItemImage;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CatalogItemController extends ManagementController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $status = (string) $request->query('status', '');
        $itemType = (string) $request->query('item_type', '');

        $products = CatalogItem::query()
            ->with(['category', 'primaryImage'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when(is_numeric($categoryId), fn ($query) => $query->where('category_id', (int) $categoryId))
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when(in_array($itemType, ['produto', 'acessorio'], true), fn ($query) => $query->where('item_type', $itemType))
            ->orderBy('name')
            ->paginate(12);

        $products->appends($request->query());

        $categories = Category::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => is_numeric($categoryId) ? (int) $categoryId : null,
                'status' => $status,
                'item_type' => $itemType,
            ],
        ]);
    }

    public function indexCrud(): View
    {
        $products = CatalogItem::query()
            ->with(['category', 'primaryImage'])
            ->orderBy('name')
            ->paginate(15);

        return view('products.crud-index', ['products' => $products]);
    }

    public function create(): View
    {
        $categories = Category::where('active', true)->orderBy('name')->get();
        return view('products.form', ['product' => null, 'categories' => $categories]);
    }

    public function store(StoreCatalogItemRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = 'storage/'.$request->file('image')->store('products', 'public');
        }

        unset(
            $data['image'],
            $data['remove_image'],
            $data['gallery_images'],
            $data['gallery_images.*'],
            $data['gallery_kind'],
            $data['primary_image_id'],
            $data['remove_gallery_images'],
            $data['remove_gallery_images.*'],
        );

        $product = CatalogItem::create($data);

        $this->handleGalleryImages($request, $product);
        $this->ensurePrimaryGalleryImage($product);
        $this->syncLegacyImagePath($product);

        return redirect()->route('products.crud')->with('success', 'Produto criado com sucesso!');
    }

    public function edit(CatalogItem $product): View
    {
        $categories = Category::where('active', true)->orderBy('name')->get();
        $product->load('images');

        return view('products.form', ['product' => $product, 'categories' => $categories]);
    }

    public function update(StoreCatalogItemRequest $request, CatalogItem $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('remove_image') && !empty($product->image_path)) {
            Storage::disk('public')->delete(str_replace('storage/', '', (string) $product->image_path));
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if (!empty($product->image_path)) {
                Storage::disk('public')->delete(str_replace('storage/', '', (string) $product->image_path));
            }

            $data['image_path'] = 'storage/'.$request->file('image')->store('products', 'public');
        }

        unset(
            $data['image'],
            $data['remove_image'],
            $data['gallery_images'],
            $data['gallery_images.*'],
            $data['gallery_kind'],
            $data['primary_image_id'],
            $data['remove_gallery_images'],
            $data['remove_gallery_images.*'],
        );

        $product->update($data);

        $this->handleGalleryImages($request, $product);
        $this->applyPrimaryGalleryImage($request, $product);
        $this->ensurePrimaryGalleryImage($product);
        $this->syncLegacyImagePath($product);

        return redirect()->route('products.crud')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(CatalogItem $product): RedirectResponse
    {
        $product->load('images');

        foreach ($product->images as $image) {
            Storage::disk('public')->delete(str_replace('storage/', '', (string) $image->image_path));
        }

        if (!empty($product->image_path)) {
            Storage::disk('public')->delete(str_replace('storage/', '', (string) $product->image_path));
        }

        $product->delete();
        return redirect()->route('products.crud')->with('success', 'Produto deletado com sucesso!');
    }

    private function handleGalleryImages(Request $request, CatalogItem $product): void
    {
        $removeIds = collect((array) $request->input('remove_gallery_images', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($removeIds !== []) {
            $imagesToRemove = CatalogItemImage::query()
                ->where('catalog_item_id', $product->id)
                ->whereIn('id', $removeIds)
                ->get();

            foreach ($imagesToRemove as $image) {
                Storage::disk('public')->delete(str_replace('storage/', '', (string) $image->image_path));
                $image->delete();
            }
        }

        $galleryKind = (string) $request->input('gallery_kind', 'outro');
        if (!in_array($galleryKind, ['perfil', 'roldana', 'acessorio', 'outro'], true)) {
            $galleryKind = 'outro';
        }

        $uploadedImages = $request->file('gallery_images', []);
        if (!is_array($uploadedImages)) {
            return;
        }

        $nextSort = (int) ($product->images()->max('sort_order') ?? 0);

        foreach ($uploadedImages as $uploadedImage) {
            if (!$uploadedImage instanceof UploadedFile) {
                continue;
            }

            $nextSort++;
            $storedPath = 'storage/'.$uploadedImage->store('products/gallery', 'public');

            $product->images()->create([
                'image_path' => $storedPath,
                'image_kind' => $galleryKind,
                'is_primary' => false,
                'sort_order' => $nextSort,
            ]);
        }
    }

    private function applyPrimaryGalleryImage(Request $request, CatalogItem $product): void
    {
        $primaryId = $request->input('primary_image_id');
        if (!is_numeric($primaryId)) {
            return;
        }

        $primaryImage = CatalogItemImage::query()
            ->where('catalog_item_id', $product->id)
            ->where('id', (int) $primaryId)
            ->first();

        if ($primaryImage === null) {
            return;
        }

        CatalogItemImage::query()
            ->where('catalog_item_id', $product->id)
            ->update(['is_primary' => false]);

        $primaryImage->update(['is_primary' => true]);
    }

    private function ensurePrimaryGalleryImage(CatalogItem $product): void
    {
        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        if ($hasPrimary) {
            return;
        }

        $firstImage = $product->images()->orderBy('sort_order')->orderBy('id')->first();
        if ($firstImage !== null) {
            $firstImage->update(['is_primary' => true]);
        }
    }

    private function syncLegacyImagePath(CatalogItem $product): void
    {
        $primaryPath = $product->images()->where('is_primary', true)->value('image_path');
        if (is_string($primaryPath) && trim($primaryPath) !== '') {
            $product->update(['image_path' => $primaryPath]);
        }
    }
}
