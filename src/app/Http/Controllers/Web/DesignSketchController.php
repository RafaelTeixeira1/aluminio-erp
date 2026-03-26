<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DesignSketch;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DesignSketchController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $quoteId = $request->integer('quote_id');

        if (!Schema::hasTable('design_sketches')) {
            return view('design-sketches.index', [
                'sketches' => new LengthAwarePaginator([], 0, 12),
                'search' => $search,
                'quoteId' => $quoteId > 0 ? $quoteId : null,
                'setupRequired' => true,
            ]);
        }

        $sketches = DesignSketch::query()
            ->with(['quote.client'])
            ->when($quoteId > 0, fn ($query) => $query->where('quote_id', $quoteId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('quote', fn ($q) => $q->where('id', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('design-sketches.index', [
            'sketches' => $sketches,
            'search' => $search,
            'quoteId' => $quoteId > 0 ? $quoteId : null,
            'setupRequired' => false,
        ]);
    }

    public function create(Request $request): View
    {
        $quoteId = $request->integer('quote_id');

        return view('design-sketches.form', [
            'sketch' => null,
            'quotes' => Quote::query()->with('client')->orderByDesc('id')->limit(150)->get(),
            'defaultQuoteId' => $quoteId > 0 ? $quoteId : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('design_sketches')) {
            return back()->withErrors(['sketch' => 'Modulo de desenho ainda nao inicializado. Execute as migracoes.']);
        }

        $data = $this->validated($request);

        $sketch = DesignSketch::query()->create([
            ...$data,
            'created_by_user_id' => $request->user()?->id,
        ]);

        return redirect()->route('designSketches.edit', $sketch)->with('success', 'Desenho salvo com sucesso!');
    }

    public function edit(DesignSketch $designSketch): View
    {
        return view('design-sketches.form', [
            'sketch' => $designSketch,
            'quotes' => Quote::query()->with('client')->orderByDesc('id')->limit(150)->get(),
        ]);
    }

    public function update(Request $request, DesignSketch $designSketch): RedirectResponse
    {
        $data = $this->validated($request);
        $designSketch->update($data);

        return back()->with('success', 'Desenho atualizado com sucesso!');
    }

    public function destroy(DesignSketch $designSketch): RedirectResponse
    {
        $designSketch->delete();

        return redirect()->route('designSketches.index')->with('success', 'Desenho removido com sucesso!');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'width_mm' => ['nullable', 'numeric', 'gt:0'],
            'height_mm' => ['nullable', 'numeric', 'gt:0'],
            'canvas_json' => ['required', 'string'],
            'preview_png' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
