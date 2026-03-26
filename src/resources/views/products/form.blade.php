@extends('layouts.app')

@section('title', $product ? 'Editar Produto' : 'Novo Produto')
@section('page-title', $product ? 'Editar Produto' : 'Novo Produto')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">{{ $product ? 'Editar Produto' : 'Novo Produto' }}</h1>

    <form action="{{ $product ? route('products.update', $product) : route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if($product)
            @method('PUT')
        @endif

        <div class="rounded-lg border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Dados Comerciais</h2>

            <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto</label>
            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                class="w-full px-4 py-2 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            </div>

            <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
            <select name="category_id" required
                class="w-full px-4 py-2 border @error('category_id') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione uma categoria...</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            </div>

            <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <select name="item_type" required
                class="w-full px-4 py-2 border @error('item_type') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione um tipo...</option>
                <option value="produto" {{ old('item_type', $product->item_type ?? '') == 'produto' ? 'selected' : '' }}>Produto</option>
                <option value="acessorio" {{ old('item_type', $product->item_type ?? '') == 'acessorio' ? 'selected' : '' }}>Acessório</option>
            </select>
            @error('item_type')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço (R$)</label>
                    <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" step="0.01" required
                        class="w-full px-4 py-2 border @error('price') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Peso linear (kg/m)</label>
                    <input type="number" name="weight_per_meter_kg" value="{{ old('weight_per_meter_kg', $product->weight_per_meter_kg ?? '') }}" step="0.001" min="0"
                        class="w-full px-4 py-2 border @error('weight_per_meter_kg') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 0,676">
                    <p class="text-xs text-gray-500 mt-1">Use ponto para casas decimais (ex: 0.676).</p>
                    @error('weight_per_meter_kg')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Dados de Estoque</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estoque</label>
                <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required
                    class="w-full px-4 py-2 border @error('stock') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('stock')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estoque Mínimo</label>
                <input type="number" name="stock_minimum" value="{{ old('stock_minimum', $product->stock_minimum ?? 0) }}" required
                    class="w-full px-4 py-2 border @error('stock_minimum') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('stock_minimum')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-sm font-semibold text-gray-800">Dados Tecnicos do Produto</h2>
            <p class="text-xs text-gray-500">Esses campos ajudam no detalhamento de orcamentos e padronizacao do catalogo.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material</label>
                    <input type="text" name="material" value="{{ old('material', $product->material ?? '') }}"
                        class="w-full px-4 py-2 border @error('material') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Aluminio">
                    @error('material')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Acabamento</label>
                    <input type="text" name="finish" value="{{ old('finish', $product->finish ?? '') }}"
                        class="w-full px-4 py-2 border @error('finish') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Anodizado fosco">
                    @error('finish')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Espessura (mm)</label>
                    <input type="number" name="thickness_mm" value="{{ old('thickness_mm', $product->thickness_mm ?? '') }}" step="0.001" min="0"
                        class="w-full px-4 py-2 border @error('thickness_mm') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 1,250">
                    @error('thickness_mm')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Largura padrao (mm)</label>
                    <input type="number" name="standard_width_mm" value="{{ old('standard_width_mm', $product->standard_width_mm ?? '') }}" step="0.01" min="0"
                        class="w-full px-4 py-2 border @error('standard_width_mm') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 6000">
                    @error('standard_width_mm')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Altura padrao (mm)</label>
                    <input type="number" name="standard_height_mm" value="{{ old('standard_height_mm', $product->standard_height_mm ?? '') }}" step="0.01" min="0"
                        class="w-full px-4 py-2 border @error('standard_height_mm') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 1000">
                    @error('standard_height_mm')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                        class="w-full px-4 py-2 border @error('brand') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Vitralsul">
                    @error('brand')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Linha</label>
                    <input type="text" name="product_line" value="{{ old('product_line', $product->product_line ?? '') }}"
                        class="w-full px-4 py-2 border @error('product_line') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Gold 32">
                    @error('product_line')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observacoes tecnicas</label>
                <textarea name="technical_notes" rows="3"
                    class="w-full px-4 py-2 border @error('technical_notes') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ex: Aplicacao, tolerancias, acabamento e detalhes de corte.">{{ old('technical_notes', $product->technical_notes ?? '') }}</textarea>
                @error('technical_notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem do Produto</label>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                class="w-full px-4 py-2 border @error('image') border-red-500 @else border-gray-300 @enderror rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('image')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror

            @if(!empty($product?->image_path))
                <div class="mt-3 flex items-center gap-4">
                    <img src="{{ asset($product->image_path) }}" alt="Imagem atual do produto" class="w-24 h-24 object-cover rounded border border-gray-200">
                    <label class="flex items-center text-sm text-gray-700">
                        <input type="checkbox" name="remove_image" value="1" class="rounded">
                        <span class="ml-2">Remover imagem atual</span>
                    </label>
                </div>
            @endif
        </div>

        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
            <h2 class="text-sm font-semibold text-gray-800">Banco de Imagens do Produto</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo das novas imagens</label>
                    <select name="gallery_kind" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        @php $galleryKind = old('gallery_kind', 'outro'); @endphp
                        <option value="perfil" {{ $galleryKind === 'perfil' ? 'selected' : '' }}>Perfil</option>
                        <option value="roldana" {{ $galleryKind === 'roldana' ? 'selected' : '' }}>Roldana</option>
                        <option value="acessorio" {{ $galleryKind === 'acessorio' ? 'selected' : '' }}>Acessorio</option>
                        <option value="outro" {{ $galleryKind === 'outro' ? 'selected' : '' }}>Outro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enviar multiplas imagens</label>
                    <input type="file" name="gallery_images[]" multiple accept="image/png,image/jpeg,image/webp"
                        class="w-full px-4 py-2 border @error('gallery_images') border-red-500 @else border-gray-300 @enderror rounded-lg">
                    @error('gallery_images')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @error('gallery_images.*')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @if(($product?->images?->count() ?? 0) > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($product->images as $galleryImage)
                        <div class="border border-gray-200 rounded-lg p-2">
                            <img src="{{ asset($galleryImage->image_path) }}" alt="Imagem da galeria" class="w-full h-24 object-cover rounded">
                            <p class="mt-2 text-xs text-gray-600">Tipo: {{ ucfirst((string) $galleryImage->image_kind) }}</p>
                            <label class="mt-1 flex items-center text-xs text-gray-700">
                                <input type="radio" name="primary_image_id" value="{{ $galleryImage->id }}" {{ $galleryImage->is_primary ? 'checked' : '' }}>
                                <span class="ml-1">Principal</span>
                            </label>
                            <label class="mt-1 flex items-center text-xs text-red-700">
                                <input type="checkbox" name="remove_gallery_images[]" value="{{ $galleryImage->id }}">
                                <span class="ml-1">Remover</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }} class="rounded">
                <span class="ml-2 text-sm text-gray-700">Ativo</span>
            </label>
        </div>

        <div class="flex gap-2 pt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                {{ $product ? 'Atualizar' : 'Criar' }}
            </button>
            <a href="{{ route('products.crud') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
