<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $categories = Category::query()
            ->withCount('catalogItems')
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($categories);
    }

    public function show(Category $categoria): JsonResponse
    {
        return response()->json($categoria->loadCount('catalogItems'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'active' => ['nullable', 'boolean'],
        ]);

        $category = Category::query()->create([
            'name' => $data['name'],
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $categoria): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoria->id),
            ],
            'active' => ['sometimes', 'required', 'boolean'],
        ]);

        $categoria->update($data);

        return response()->json($categoria->fresh()->loadCount('catalogItems'));
    }

    public function destroy(Category $categoria): JsonResponse
    {
        $categoria->update(['active' => false]);

        return response()->json(status: 204);
    }
}
