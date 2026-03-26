<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends ManagementController
{
    public function indexCrud(): View
    {
        $categories = Category::query()
            ->withCount('catalogItems')
            ->orderBy('name')
            ->paginate(15);

        return view('categories.crud-index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('categories.form', ['category' => null]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());
        return redirect()->route('categories.crud')->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', ['category' => $category]);
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        return redirect()->route('categories.crud')->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        return redirect()->route('categories.crud')->with('success', 'Categoria deletada com sucesso!');
    }
}
