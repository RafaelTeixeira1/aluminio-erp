@extends('layouts.app')

@section('title', $quote ? 'Editar Orcamento' : 'Novo Orcamento')
@section('page-title', $quote ? 'Editar Orcamento' : 'Novo Orcamento')

@section('content')
<div class="max-w-5xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">{{ $quote ? 'Editar Orcamento' : 'Novo Orcamento' }}</h1>

    <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
        <h2 class="text-sm font-semibold text-blue-900 mb-2">Fluxo de preenchimento</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-blue-800">
            <div><strong>1.</strong> (Opcional) selecione cliente, validade e pagamento.</div>
            <div><strong>2.</strong> Em cada item, escolha o produto e informe quantidade.</div>
            <div><strong>3.</strong> Complete dados técnicos e salve o orçamento.</div>
        </div>
        <p class="mt-2 text-xs text-blue-900 font-medium">Obrigatório para salvar: pelo menos 1 item com Produto e Quantidade.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <h2 class="text-sm font-semibold text-red-800 mb-2">Existem campos obrigatórios pendentes</h2>
            <ul class="text-xs text-red-700 list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-indigo-900 mb-1">Desenho tecnico integrado</h2>
            <p class="text-xs text-indigo-800">O desenho agora pode ser feito opcionalmente durante a montagem do orçamento, sem precisar salvar antes.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($quote)
                <a href="{{ route('designSketches.create', ['quote_id' => $quote->id]) }}" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">Novo desenho deste orcamento</a>
                <a href="{{ route('designSketches.index', ['quote_id' => $quote->id]) }}" class="bg-white text-indigo-700 border border-indigo-200 px-3 py-2 rounded-lg hover:bg-indigo-100 transition text-sm">Ver desenhos vinculados</a>
            @else
                <span class="inline-flex items-center px-3 py-2 rounded-lg bg-white border border-indigo-200 text-xs text-indigo-700">Desenho opcional direto neste formulário</span>
            @endif
        </div>
    </div>

    <form id="quote-form" action="{{ $quote ? route('quotes.update', $quote) : route('quotes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($quote)
            @method('PUT')
        @endif

        <div class="rounded-lg border border-gray-200 p-4">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Dados Gerais do Orçamento</h2>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Cliente</label>
                    <span id="js-quick-quote-badge" class="items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 hidden">
                        Orçamento rápido
                    </span>
                </div>
                <select name="client_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Selecione...</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $quote->client_id ?? '') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    @foreach(['aberto','aprovado','cancelado','expirado'] as $status)
                        <option value="{{ $status }}" {{ old('status', $quote->status ?? 'aberto') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valido ate</label>
                <input type="date" name="valid_until" value="{{ old('valid_until', $quote?->valid_until?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meio de Pagamento</label>
                <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Selecione...</option>
                    @php $paymentMethod = old('payment_method', $quote->payment_method ?? ''); @endphp
                    <option value="boleto" {{ $paymentMethod === 'boleto' ? 'selected' : '' }}>Boleto</option>
                    <option value="pix" {{ $paymentMethod === 'pix' ? 'selected' : '' }}>Pix</option>
                    <option value="cartao" {{ $paymentMethod === 'cartao' ? 'selected' : '' }}>Cartao</option>
                    <option value="boleto_pix" {{ $paymentMethod === 'boleto_pix' ? 'selected' : '' }}>Boleto + Pix</option>
                    <option value="pix_cartao" {{ $paymentMethod === 'pix_cartao' ? 'selected' : '' }}>Pix + Cartao</option>
                    <option value="boleto_cartao" {{ $paymentMethod === 'boleto_cartao' ? 'selected' : '' }}>Boleto + Cartao</option>
                    <option value="misto" {{ $paymentMethod === 'misto' ? 'selected' : '' }}>Misto</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desconto (R$)</label>
                <input type="number" step="0.01" min="0" name="discount" value="{{ old('discount', $quote->discount ?? 0) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg js-discount" placeholder="0,00">
            </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Observacoes</label>
            <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('notes', $quote->notes ?? '') }}</textarea>
        </div>

        @php
            $sketchEnabledOld = old('sketch_enabled');
            $inlineSketchEnabled = $sketchEnabledOld !== null
                ? (bool) $sketchEnabledOld
                : (($inlineSketch ?? null) !== null);
            $inlineSketchId = old('sketch_id', $inlineSketch->id ?? '');
            $inlineSketchTitle = old('sketch_title', $inlineSketch->title ?? ($quote ? ('Desenho integrado orçamento #'.$quote->id) : 'Desenho integrado do orçamento'));
            $inlineSketchWidth = old('sketch_width_mm', $inlineSketch->width_mm ?? '');
            $inlineSketchHeight = old('sketch_height_mm', $inlineSketch->height_mm ?? '');
            $inlineSketchCanvasJson = old('sketch_canvas_json', $inlineSketch->canvas_json ?? '');
            $inlineSketchPreview = old('sketch_preview_png', $inlineSketch->preview_png ?? '');
            $inlineSketchNotes = old('sketch_notes', $inlineSketch->notes ?? '');
        @endphp

        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Desenho técnico (opcional)</h2>
                    <p class="text-xs text-gray-600">Use para ilustrar medidas e divisórias durante a adição dos itens.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" id="js-sketch-enabled" {{ $inlineSketchEnabled ? 'checked' : '' }}>
                    Incluir desenho neste orçamento
                </label>
            </div>

            <input type="hidden" name="sketch_enabled" id="sketch_enabled" value="{{ $inlineSketchEnabled ? '1' : '0' }}">
            <input type="hidden" name="sketch_id" value="{{ $inlineSketchId }}">
            <input type="hidden" name="sketch_canvas_json" id="sketch_canvas_json" value="{{ $inlineSketchCanvasJson }}">
            <input type="hidden" name="sketch_preview_png" id="sketch_preview_png" value="{{ $inlineSketchPreview }}">

            <div id="js-sketch-panel" class="space-y-3 {{ $inlineSketchEnabled ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Título do desenho</label>
                        <input type="text" name="sketch_title" id="sketch_title" value="{{ $inlineSketchTitle }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Fachada principal com 3 vãos">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Largura (mm)</label>
                        <input type="number" step="0.01" min="1" name="sketch_width_mm" id="sketch_width_mm" value="{{ $inlineSketchWidth }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Altura (mm)</label>
                        <input type="number" step="0.01" min="1" name="sketch_height_mm" id="sketch_height_mm" value="{{ $inlineSketchHeight }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="flex flex-nowrap md:flex-wrap items-center gap-2 min-w-max md:min-w-0">
                        <button type="button" class="js-inline-sketch-tool bg-blue-600 text-white px-3 py-1.5 rounded" data-tool="pen">Mão livre</button>
                        <button type="button" class="js-inline-sketch-tool bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="line">Linha</button>
                        <button type="button" class="js-inline-sketch-tool bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="rect">Retângulo</button>
                        <button type="button" class="js-inline-sketch-tool bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="dimension">Cota</button>
                        <button type="button" class="js-inline-sketch-tool bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="text">Texto</button>
                        <button type="button" id="js-inline-repeat-last-dimension" class="bg-amber-100 text-amber-800 px-3 py-1.5 rounded">Repetir última cota</button>
                        <button type="button" id="js-inline-repeat-last-text" class="bg-sky-100 text-sky-800 px-3 py-1.5 rounded">Repetir último texto</button>
                        <button type="button" id="js-inline-sketch-undo" class="bg-gray-200 text-gray-900 px-3 py-1.5 rounded">Desfazer</button>
                        <button type="button" id="js-inline-sketch-clear" class="bg-red-100 text-red-700 px-3 py-1.5 rounded">Limpar</button>
                        <button type="button" id="js-inline-sketch-fullscreen" class="bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded">Tela cheia</button>
                        <label class="inline-flex items-center gap-1 text-xs text-gray-700 ml-2">
                            <input type="checkbox" id="js-inline-light-snap" checked>
                            Snap leve
                        </label>
                        <label class="text-xs text-gray-600 ml-2">Cor</label>
                        <input type="color" id="js-inline-sketch-color" value="#1f2937" class="w-10 h-8 border border-gray-300 rounded">
                        <label class="text-xs text-gray-600">Espessura</label>
                        <input type="range" id="js-inline-sketch-width" min="1" max="10" value="2" class="w-28">
                    </div>
                </div>

                <div class="rounded-lg border border-violet-200 bg-violet-50 p-3 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <span class="text-violet-900 font-semibold">Ferramenta ativa: <span id="js-inline-tool-label">Mão livre</span></span>
                        <div class="flex items-center gap-2">
                            <span id="js-inline-tool-hint" class="text-violet-800">Arraste para desenhar livremente.</span>
                            <button type="button" id="js-inline-help-toggle" class="px-2 py-1 rounded border border-violet-300 bg-white text-violet-800 hover:bg-violet-100">Ocultar ajuda</button>
                        </div>
                    </div>
                    <div id="js-inline-help-content" class="grid grid-cols-1 md:grid-cols-5 gap-2 text-xs">
                        <div class="rounded border border-violet-200 bg-white px-2 py-1.5 text-violet-900"><strong>Mão livre</strong>: traço rápido para ilustração.</div>
                        <div class="rounded border border-violet-200 bg-white px-2 py-1.5 text-violet-900"><strong>Linha</strong>: use snap leve para horizontal/vertical.</div>
                        <div class="rounded border border-violet-200 bg-white px-2 py-1.5 text-violet-900"><strong>Retângulo</strong>: contorno de vãos e módulos.</div>
                        <div class="rounded border border-violet-200 bg-white px-2 py-1.5 text-violet-900"><strong>Cota</strong>: informe medida na caixa lateral.</div>
                        <div class="rounded border border-violet-200 bg-white px-2 py-1.5 text-violet-900"><strong>Texto</strong>: escreva e clique para posicionar.</div>
                    </div>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-amber-800 mb-1">Valor da próxima cota</label>
                            <input type="text" id="js-inline-dimension-label" class="w-full px-3 py-2 border border-amber-300 rounded-lg bg-white" placeholder="Ex: 1200 mm (se vazio, usa sugestão automática)">
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-amber-800 mb-1">Sugestões</span>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="js-inline-dimension-suggestion px-2 py-1 text-xs rounded bg-white border border-amber-300 text-amber-800" data-value="600 mm">600</button>
                                <button type="button" class="js-inline-dimension-suggestion px-2 py-1 text-xs rounded bg-white border border-amber-300 text-amber-800" data-value="800 mm">800</button>
                                <button type="button" class="js-inline-dimension-suggestion px-2 py-1 text-xs rounded bg-white border border-amber-300 text-amber-800" data-value="1200 mm">1200</button>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-amber-900">Cota: clique e arraste. O valor usado será o da caixa acima; se estiver vazia, o sistema usa a sugestão automática.</p>
                </div>

                <div class="rounded-lg border border-sky-200 bg-sky-50 p-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-sky-800 mb-1">Texto da próxima legenda</label>
                            <input type="text" id="js-inline-text-value" class="w-full px-3 py-2 border border-sky-300 rounded-lg bg-white" placeholder="Ex: Folha fixa / abrir para direita">
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-sky-800 mb-1">Sugestões</span>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="js-inline-text-suggestion px-2 py-1 text-xs rounded bg-white border border-sky-300 text-sky-800" data-value="Folha fixa">Folha fixa</button>
                                <button type="button" class="js-inline-text-suggestion px-2 py-1 text-xs rounded bg-white border border-sky-300 text-sky-800" data-value="Correr">Correr</button>
                                <button type="button" class="js-inline-text-suggestion px-2 py-1 text-xs rounded bg-white border border-sky-300 text-sky-800" data-value="Porta">Porta</button>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-sky-900">Texto: escreva na caixa acima e clique no desenho para posicionar, sem pop-up.</p>
                </div>

                <div id="js-inline-sketch-feedback" class="hidden rounded-lg border px-3 py-2 text-xs"></div>

                <div id="js-inline-sketch-stage" class="border border-gray-300 rounded-lg overflow-auto bg-gray-50">
                    <canvas id="js-inline-sketch-canvas" width="1100" height="420" class="bg-white w-full h-auto"></canvas>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observações do desenho</label>
                    <input type="text" name="sketch_notes" id="sketch_notes" value="{{ $inlineSketchNotes }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Detalhes de abertura, divisórias, trilhos, etc.">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-semibold">Itens do Orçamento</h2>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Selecione o produto e informe quantidade</span>
                    <button type="button" id="add-item-row" class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded hover:bg-blue-700">+ Adicionar item</button>
                </div>
            </div>
            <div id="quote-items-container" class="space-y-2">
                @php
                    $rows = max(3, $quote?->items?->count() ?? 0);
                @endphp
                @for($i = 0; $i < $rows; $i++)
                    @php $item = $quote->items[$i] ?? null; @endphp
                    @php
                        $existingImage = is_array($item?->metadata ?? null) ? ($item->metadata['image'] ?? '') : '';
                    @endphp
                    @php
                        $itemMeta = is_array($item?->metadata ?? null) ? $item->metadata : [];
                        $rowHasError = $errors->has("items.$i.catalog_item_id")
                            || $errors->has("items.$i.quantity")
                            || $errors->has("items.$i.unit_price")
                            || $errors->has("items.$i.width_mm")
                            || $errors->has("items.$i.height_mm")
                            || $errors->has("items.$i.image")
                            || $errors->has("items.$i.bnf")
                            || $errors->has("items.$i.bar_cut_size")
                            || $errors->has("items.$i.pieces_quantity")
                            || $errors->has("items.$i.weight")
                            || $errors->has("items.$i.total_weight")
                            || $errors->has("items.$i.item_observation");
                    @endphp
                    <div class="border {{ $rowHasError ? 'border-red-300' : 'border-gray-200' }} p-3 rounded-lg bg-gray-50 js-item-row {{ $rowHasError ? 'js-item-row-error' : '' }}" data-index="{{ $i }}">
                        <div class="flex justify-between items-center border-b border-gray-200 pb-2 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-600">Item #{{ $i + 1 }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 js-item-status-badge">Item vazio</span>
                            </div>
                            <button type="button" class="text-xs text-red-600 hover:text-red-700 js-remove-item-row">Remover</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Produto</label>
                                <select name="items[{{ $i }}][catalog_item_id]" class="w-full px-2 py-2 border border-gray-300 rounded js-product-select">
                                    <option value="">Selecione o produto...</option>
                                    @foreach($products as $product)
                                        <option
                                            value="{{ $product->id }}"
                                            data-name="{{ $product->name }}"
                                            data-type="{{ $product->item_type }}"
                                            data-price="{{ $product->price }}"
                                            data-image="{{ $product->image_path ?? '' }}"
                                            data-kgm="{{ $product->effective_weight_per_meter_kg ?? '' }}"
                                            {{ old("items.$i.catalog_item_id", $item->catalog_item_id ?? '') == $product->id ? 'selected' : '' }}
                                        >{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Quantidade</label>
                                <input type="number" step="0.001" name="items[{{ $i }}][quantity]" value="{{ old("items.$i.quantity", $item->quantity ?? '') }}" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-quantity">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Total do item</label>
                                <input type="number" step="0.01" value="" placeholder="0,00" class="w-full px-2 py-2 border border-gray-300 rounded bg-white js-line-total" readonly>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Dados automáticos do produto</label>
                                <input type="hidden" name="items[{{ $i }}][item_name]" value="{{ old("items.$i.item_name", $item->item_name ?? '') }}" class="js-item-name">
                                <input type="hidden" name="items[{{ $i }}][item_type]" value="{{ old("items.$i.item_type", $item->item_type ?? 'produto') }}" class="js-item-type">
                                <input type="hidden" name="items[{{ $i }}][unit_price]" value="{{ old("items.$i.unit_price", $item->unit_price ?? '') }}" class="js-item-price">
                                <input type="hidden" name="items[{{ $i }}][weight_per_meter_kg]" value="{{ old("items.$i.weight_per_meter_kg", $itemMeta['weight_per_meter_kg'] ?? '') }}" class="js-item-kgm">
                                <div class="px-2 py-2 border border-gray-300 rounded bg-white">
                                    <div class="text-sm font-medium text-gray-900 js-item-name-display">{{ old("items.$i.item_name", $item->item_name ?? '-') ?: '-' }}</div>
                                    <div class="text-xs text-gray-600 js-item-type-display">{{ ucfirst((string) old("items.$i.item_type", $item->item_type ?? '-')) }}</div>
                                    <div class="text-xs text-gray-700 js-item-price-display">R$ {{ number_format((float) old("items.$i.unit_price", $item->unit_price ?? 0), 2, ',', '.') }}</div>
                                    <div class="text-xs text-gray-700 js-item-kgm-display">kg/m {{ is_numeric(old("items.$i.weight_per_meter_kg", $itemMeta['weight_per_meter_kg'] ?? null)) ? number_format((float) old("items.$i.weight_per_meter_kg", $itemMeta['weight_per_meter_kg'] ?? 0), 3, ',', '.') : '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Largura (mm)</label>
                                <input type="number" step="0.01" name="items[{{ $i }}][width_mm]" value="{{ old("items.$i.width_mm", $item->width_mm ?? '') }}" placeholder="0,00" class="w-full px-2 py-2 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Altura (mm)</label>
                                <input type="number" step="0.01" name="items[{{ $i }}][height_mm]" value="{{ old("items.$i.height_mm", $item->height_mm ?? '') }}" placeholder="0,00" class="w-full px-2 py-2 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Imagem do item (opcional)</label>
                                <input type="hidden" name="items[{{ $i }}][existing_image]" value="{{ old("items.$i.existing_image", $existingImage) }}" class="js-existing-image">
                                <input type="file" name="items[{{ $i }}][image]" accept="image/*" class="w-full text-xs border border-gray-300 rounded px-2 py-2 js-image-upload">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Ações da imagem</label>
                                <label class="inline-flex items-center gap-1 text-xs text-gray-600">
                                    <input type="checkbox" name="items[{{ $i }}][remove_image]" value="1" {{ old("items.$i.remove_image") ? 'checked' : '' }} class="js-remove-image">
                                    Remover imagem
                                </label>
                                @if(!empty($existingImage))
                                    <a href="{{ asset($existingImage) }}" target="_blank" class="block text-xs text-blue-600 hover:underline js-image-link">Ver imagem atual</a>
                                    <img src="{{ asset($existingImage) }}" alt="Miniatura do item" class="mt-2 w-16 h-16 object-cover border border-gray-200 rounded js-image-preview">
                                @else
                                    <a href="#" target="_blank" class="hidden text-xs text-blue-600 hover:underline js-image-link">Ver imagem atual</a>
                                    <img src="" alt="Miniatura do item" class="hidden mt-2 w-16 h-16 object-cover border border-gray-200 rounded js-image-preview">
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">BNF</label>
                                <input type="text" name="items[{{ $i }}][bnf]" value="{{ old("items.$i.bnf", $itemMeta['bnf'] ?? '') }}" placeholder="Cor / código" class="w-full px-2 py-2 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Corte (barra)</label>
                                <input type="text" name="items[{{ $i }}][bar_cut_size]" value="{{ old("items.$i.bar_cut_size", $itemMeta['bar_cut_size'] ?? '') }}" placeholder="Ex: 6m" class="w-full px-2 py-2 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Qtd. peças</label>
                                <input type="number" step="0.001" name="items[{{ $i }}][pieces_quantity]" value="{{ old("items.$i.pieces_quantity", $itemMeta['pieces_quantity'] ?? '') }}" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-pieces-quantity">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Peso</label>
                                <input type="number" step="0.001" name="items[{{ $i }}][weight]" value="{{ old("items.$i.weight", $itemMeta['weight'] ?? '') }}" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-weight">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Peso total</label>
                                <input type="number" step="0.001" name="items[{{ $i }}][total_weight]" value="{{ old("items.$i.total_weight", $itemMeta['total_weight'] ?? '') }}" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-total-weight">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Observação técnica</label>
                                <input type="text" name="items[{{ $i }}][item_observation]" value="{{ old("items.$i.item_observation", $itemMeta['item_observation'] ?? '') }}" placeholder="Detalhe do item" class="w-full px-2 py-2 border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal estimado</span>
                    <strong id="js-preview-subtotal">R$ 0,00</strong>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-gray-600">Desconto</span>
                    <strong id="js-preview-discount">R$ 0,00</strong>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-gray-600">Total estimado</span>
                    <strong id="js-preview-total">R$ 0,00</strong>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Observacao da Quantificacao dos Itens</label>
            <textarea name="item_quantification_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('item_quantification_notes', $quote->item_quantification_notes ?? '') }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <button id="js-submit-quote" type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed">{{ $quote ? 'Atualizar' : 'Salvar' }}</button>
            <a href="{{ route('quotes.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">Cancelar</a>
            <span id="js-submit-hint" class="text-xs text-gray-500"></span>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('quote-items-container');
    const addItemButton = document.getElementById('add-item-row');
    const quoteForm = document.getElementById('quote-form');
    const submitButton = document.getElementById('js-submit-quote');
    const submitHint = document.getElementById('js-submit-hint');
    const clientSelect = document.querySelector('select[name="client_id"]');
    const quickQuoteBadge = document.getElementById('js-quick-quote-badge');
    const subtotalElement = document.getElementById('js-preview-subtotal');
    const discountElement = document.getElementById('js-preview-discount');
    const totalElement = document.getElementById('js-preview-total');
    const discountInput = document.querySelector('.js-discount');
    const sketchEnabledCheckbox = document.getElementById('js-sketch-enabled');
    const sketchEnabledInput = document.getElementById('sketch_enabled');
    const sketchPanel = document.getElementById('js-sketch-panel');
    const sketchCanvas = document.getElementById('js-inline-sketch-canvas');
    const sketchCanvasJsonInput = document.getElementById('sketch_canvas_json');
    const sketchPreviewInput = document.getElementById('sketch_preview_png');
    const sketchStage = document.getElementById('js-inline-sketch-stage');
    const sketchToolButtons = Array.from(document.querySelectorAll('.js-inline-sketch-tool'));
    const sketchUndoButton = document.getElementById('js-inline-sketch-undo');
    const sketchClearButton = document.getElementById('js-inline-sketch-clear');
    const sketchFullscreenButton = document.getElementById('js-inline-sketch-fullscreen');
    const lightSnapEnabledInput = document.getElementById('js-inline-light-snap');
    const sketchColorInput = document.getElementById('js-inline-sketch-color');
    const sketchWidthInput = document.getElementById('js-inline-sketch-width');
    const sketchWidthMmInput = document.getElementById('sketch_width_mm');
    const toolLabelElement = document.getElementById('js-inline-tool-label');
    const toolHintElement = document.getElementById('js-inline-tool-hint');
    const helpToggleButton = document.getElementById('js-inline-help-toggle');
    const helpContentElement = document.getElementById('js-inline-help-content');
    const dimensionLabelInput = document.getElementById('js-inline-dimension-label');
    const dimensionSuggestionButtons = Array.from(document.querySelectorAll('.js-inline-dimension-suggestion'));
    const inlineTextValueInput = document.getElementById('js-inline-text-value');
    const inlineTextSuggestionButtons = Array.from(document.querySelectorAll('.js-inline-text-suggestion'));
    const repeatLastDimensionButton = document.getElementById('js-inline-repeat-last-dimension');
    const repeatLastTextButton = document.getElementById('js-inline-repeat-last-text');
    const sketchFeedback = document.getElementById('js-inline-sketch-feedback');

    let inlineSketchTool = 'pen';
    let inlineSketchDrawing = false;
    let inlineSketchStart = null;
    let inlineSketchPath = [];
    let inlineSketchShapes = [];
    let inlineSketchPreview = null;

    const INLINE_DIMENSION_STORAGE_KEY = 'quote_inline_dimension_label';
    const INLINE_TEXT_STORAGE_KEY = 'quote_inline_text_value';
    const INLINE_HELP_COLLAPSED_KEY = 'quote_inline_help_collapsed';

    const inlineSketchSetHelpCollapsed = (collapsed) => {
        if (!helpContentElement || !helpToggleButton) {
            return;
        }

        helpContentElement.classList.toggle('hidden', collapsed);
        helpToggleButton.textContent = collapsed ? 'Mostrar ajuda' : 'Ocultar ajuda';
        window.localStorage.setItem(INLINE_HELP_COLLAPSED_KEY, collapsed ? '1' : '0');
    };

    const showInlineSketchFeedback = (message, kind = 'info') => {
        if (!sketchFeedback) {
            return;
        }

        sketchFeedback.classList.remove('hidden', 'border-amber-300', 'bg-amber-50', 'text-amber-900', 'border-blue-300', 'bg-blue-50', 'text-blue-900');
        if (kind === 'warning') {
            sketchFeedback.classList.add('border-amber-300', 'bg-amber-50', 'text-amber-900');
        } else {
            sketchFeedback.classList.add('border-blue-300', 'bg-blue-50', 'text-blue-900');
        }

        sketchFeedback.textContent = message;
        window.clearTimeout(showInlineSketchFeedback.timeoutId);
        showInlineSketchFeedback.timeoutId = window.setTimeout(() => {
            sketchFeedback.classList.add('hidden');
        }, 2800);
    };

    const inlineSketchCtx = sketchCanvas ? sketchCanvas.getContext('2d') : null;

    const inlineSketchRender = () => {
        if (!inlineSketchCtx || !sketchCanvas) {
            return;
        }

        inlineSketchCtx.clearRect(0, 0, sketchCanvas.width, sketchCanvas.height);

        const drawShape = (shape) => {
            inlineSketchCtx.strokeStyle = shape.color || '#1f2937';
            inlineSketchCtx.fillStyle = shape.color || '#1f2937';
            inlineSketchCtx.lineWidth = Number(shape.lineWidth || 2);
            inlineSketchCtx.lineCap = 'round';
            inlineSketchCtx.lineJoin = 'round';

            if (shape.type === 'pen') {
                const points = Array.isArray(shape.points) ? shape.points : [];
                if (points.length < 2) {
                    return;
                }
                inlineSketchCtx.beginPath();
                inlineSketchCtx.moveTo(points[0].x, points[0].y);
                for (let i = 1; i < points.length; i += 1) {
                    inlineSketchCtx.lineTo(points[i].x, points[i].y);
                }
                inlineSketchCtx.stroke();
                return;
            }

            if (shape.type === 'line') {
                inlineSketchCtx.beginPath();
                inlineSketchCtx.moveTo(shape.x1, shape.y1);
                inlineSketchCtx.lineTo(shape.x2, shape.y2);
                inlineSketchCtx.stroke();
                return;
            }

            if (shape.type === 'rect') {
                const width = shape.x2 - shape.x1;
                const height = shape.y2 - shape.y1;
                inlineSketchCtx.strokeRect(shape.x1, shape.y1, width, height);
                return;
            }

            if (shape.type === 'dimension') {
                const x1 = Number(shape.x1 || 0);
                const y1 = Number(shape.y1 || 0);
                const x2 = Number(shape.x2 || 0);
                const y2 = Number(shape.y2 || 0);

                inlineSketchCtx.beginPath();
                inlineSketchCtx.moveTo(x1, y1);
                inlineSketchCtx.lineTo(x2, y2);
                inlineSketchCtx.stroke();

                const label = String(shape.label || '').trim();
                if (label !== '') {
                    const mx = (x1 + x2) / 2;
                    const my = (y1 + y2) / 2;
                    inlineSketchCtx.save();
                    inlineSketchCtx.font = '12px sans-serif';
                    inlineSketchCtx.fillStyle = shape.color || '#1f2937';
                    const textWidth = inlineSketchCtx.measureText(label).width;
                    inlineSketchCtx.fillStyle = '#ffffff';
                    inlineSketchCtx.fillRect(mx - textWidth / 2 - 4, my - 16, textWidth + 8, 16);
                    inlineSketchCtx.fillStyle = shape.color || '#1f2937';
                    inlineSketchCtx.fillText(label, mx - textWidth / 2, my - 4);
                    inlineSketchCtx.restore();
                }
                return;
            }

            if (shape.type === 'text') {
                const content = String(shape.text || '').trim();
                if (content === '') {
                    return;
                }

                inlineSketchCtx.save();
                inlineSketchCtx.font = `${Math.max(12, Number(shape.lineWidth || 2) * 6)}px sans-serif`;
                inlineSketchCtx.fillStyle = shape.color || '#1f2937';
                inlineSketchCtx.fillText(content, Number(shape.x || 0), Number(shape.y || 0));
                inlineSketchCtx.restore();
            }
        };

        inlineSketchShapes.forEach(drawShape);
        if (inlineSketchPreview) {
            drawShape(inlineSketchPreview);
        }
    };

    const inlineSketchSetTool = (tool) => {
        inlineSketchTool = tool;

        const toolMeta = {
            pen: { label: 'Mão livre', hint: 'Arraste para desenhar livremente.' },
            line: { label: 'Linha', hint: 'Clique e arraste para uma linha única.' },
            rect: { label: 'Retângulo', hint: 'Clique e arraste para definir o retângulo.' },
            dimension: { label: 'Cota', hint: 'Arraste e use o valor da caixa de cota.' },
            text: { label: 'Texto', hint: 'Clique para posicionar o texto da caixa lateral.' },
        };

        const currentMeta = toolMeta[tool] || toolMeta.pen;
        if (toolLabelElement) {
            toolLabelElement.textContent = currentMeta.label;
        }
        if (toolHintElement) {
            toolHintElement.textContent = currentMeta.hint;
        }

        sketchToolButtons.forEach((button) => {
            const isActive = button.dataset.tool === tool;
            button.classList.toggle('bg-blue-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('bg-gray-200', !isActive);
            button.classList.toggle('text-gray-900', !isActive);
        });
    };

    const inlineSketchApplyLightSnap = (start, current, tool) => {
        if (!lightSnapEnabledInput?.checked) {
            return current;
        }

        if (!['line', 'dimension'].includes(tool)) {
            return current;
        }

        const dx = current.x - start.x;
        const dy = current.y - start.y;

        if (Math.abs(dx) >= Math.abs(dy)) {
            return { x: current.x, y: start.y };
        }

        return { x: start.x, y: current.y };
    };

    const inlineSketchPointer = (event) => {
        if (!sketchCanvas) {
            return { x: 0, y: 0 };
        }

        const rect = sketchCanvas.getBoundingClientRect();
        const scaleX = rect.width > 0 ? (sketchCanvas.width / rect.width) : 1;
        const scaleY = rect.height > 0 ? (sketchCanvas.height / rect.height) : 1;
        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY,
        };
    };

    const inlineSketchMmPerPixel = () => {
        if (!sketchCanvas) {
            return 1;
        }

        const widthMm = Number.parseFloat(sketchWidthMmInput?.value || '');
        if (Number.isFinite(widthMm) && widthMm > 0) {
            return widthMm / sketchCanvas.width;
        }

        return 1;
    };

    const inlineSketchSuggestedDimensionLabel = (x1, y1, x2, y2) => {
        const distancePx = Math.hypot(x2 - x1, y2 - y1);
        const distanceMm = distancePx * inlineSketchMmPerPixel();
        return `${distanceMm.toFixed(0)} mm`;
    };

    const inlineSketchSerialize = () => {
        if (!sketchCanvasJsonInput || !sketchPreviewInput || !sketchCanvas) {
            return;
        }

        sketchCanvasJsonInput.value = JSON.stringify({ shapes: inlineSketchShapes });
        if (inlineSketchShapes.length > 0) {
            sketchPreviewInput.value = sketchCanvas.toDataURL('image/png');
        } else {
            sketchPreviewInput.value = '';
        }
    };

    const inlineSketchLoad = () => {
        if (!sketchCanvasJsonInput) {
            return;
        }

        const raw = String(sketchCanvasJsonInput.value || '').trim();
        if (raw === '') {
            inlineSketchShapes = [];
            inlineSketchRender();
            return;
        }

        try {
            const parsed = JSON.parse(raw);
            inlineSketchShapes = Array.isArray(parsed?.shapes) ? parsed.shapes : [];
        } catch (_error) {
            inlineSketchShapes = [];
        }

        inlineSketchRender();
    };

    const inlineSketchSyncVisibility = () => {
        if (!sketchEnabledCheckbox || !sketchEnabledInput || !sketchPanel) {
            return;
        }

        const enabled = sketchEnabledCheckbox.checked;
        sketchEnabledInput.value = enabled ? '1' : '0';
        sketchPanel.classList.toggle('hidden', !enabled);

        if (enabled) {
            inlineSketchRender();
        }
    };

    if (sketchCanvas && inlineSketchCtx) {
        sketchCanvas.addEventListener('mousedown', (event) => {
            if (!sketchEnabledCheckbox?.checked) {
                return;
            }

            inlineSketchStart = inlineSketchPointer(event);

            if (inlineSketchTool === 'text') {
                const typedText = String(inlineTextValueInput?.value || '').trim();
                if (typedText === '') {
                    showInlineSketchFeedback('Preencha o campo "Texto da próxima legenda" antes de posicionar no desenho.', 'warning');
                    inlineSketchStart = null;
                    return;
                }

                inlineSketchShapes.push({
                    type: 'text',
                    x: inlineSketchStart.x,
                    y: inlineSketchStart.y,
                    text: typedText,
                    color: sketchColorInput?.value || '#1f2937',
                    lineWidth: Number(sketchWidthInput?.value || 2),
                });
                inlineSketchRender();
                inlineSketchSerialize();
                inlineSketchStart = null;
                return;
            }

            inlineSketchDrawing = true;

            if (inlineSketchTool === 'pen') {
                inlineSketchPath = [inlineSketchStart];
                inlineSketchPreview = {
                    type: 'pen',
                    points: inlineSketchPath,
                    color: sketchColorInput?.value || '#1f2937',
                    lineWidth: Number(sketchWidthInput?.value || 2),
                };
            } else if (inlineSketchTool === 'dimension') {
                inlineSketchPreview = {
                    type: 'dimension',
                    x1: inlineSketchStart.x,
                    y1: inlineSketchStart.y,
                    x2: inlineSketchStart.x,
                    y2: inlineSketchStart.y,
                    color: sketchColorInput?.value || '#1f2937',
                    lineWidth: Number(sketchWidthInput?.value || 2),
                    label: '0 mm',
                };
            }
        });

        sketchCanvas.addEventListener('mousemove', (event) => {
            if (!inlineSketchDrawing || !inlineSketchStart) {
                return;
            }

            const current = inlineSketchPointer(event);
            const snappedCurrent = inlineSketchApplyLightSnap(inlineSketchStart, current, inlineSketchTool);
            const color = sketchColorInput?.value || '#1f2937';
            const lineWidth = Number(sketchWidthInput?.value || 2);

            if (inlineSketchTool === 'pen') {
                inlineSketchPath.push(current);
                inlineSketchPreview = {
                    type: 'pen',
                    points: [...inlineSketchPath],
                    color,
                    lineWidth,
                };
            } else if (inlineSketchTool === 'dimension') {
                const manualLabel = String(dimensionLabelInput?.value || '').trim();
                const suggestedLabel = inlineSketchSuggestedDimensionLabel(inlineSketchStart.x, inlineSketchStart.y, snappedCurrent.x, snappedCurrent.y);
                inlineSketchPreview = {
                    type: 'dimension',
                    x1: inlineSketchStart.x,
                    y1: inlineSketchStart.y,
                    x2: snappedCurrent.x,
                    y2: snappedCurrent.y,
                    color,
                    lineWidth,
                    label: manualLabel !== '' ? manualLabel : suggestedLabel,
                };
            } else {
                inlineSketchPreview = {
                    type: inlineSketchTool,
                    x1: inlineSketchStart.x,
                    y1: inlineSketchStart.y,
                    x2: inlineSketchTool === 'line' ? snappedCurrent.x : current.x,
                    y2: inlineSketchTool === 'line' ? snappedCurrent.y : current.y,
                    color,
                    lineWidth,
                };
            }

            inlineSketchRender();
        });

        const finishInlineSketchDrawing = () => {
            if (!inlineSketchDrawing) {
                return;
            }

            inlineSketchDrawing = false;

            if (inlineSketchPreview) {
                if (inlineSketchPreview.type === 'dimension') {
                    const suggested = inlineSketchSuggestedDimensionLabel(
                        Number(inlineSketchPreview.x1 || 0),
                        Number(inlineSketchPreview.y1 || 0),
                        Number(inlineSketchPreview.x2 || 0),
                        Number(inlineSketchPreview.y2 || 0)
                    );

                    const manual = String(dimensionLabelInput?.value || '').trim();
                    inlineSketchPreview.label = manual !== '' ? manual : suggested;
                }

                inlineSketchShapes.push(inlineSketchPreview);
            }

            inlineSketchPreview = null;
            inlineSketchPath = [];
            inlineSketchStart = null;
            inlineSketchRender();
            inlineSketchSerialize();
        };

        sketchCanvas.addEventListener('mouseup', finishInlineSketchDrawing);
        sketchCanvas.addEventListener('mouseleave', finishInlineSketchDrawing);
    }

    sketchToolButtons.forEach((button) => {
        button.addEventListener('click', () => inlineSketchSetTool(button.dataset.tool || 'pen'));
    });

    if (sketchUndoButton) {
        sketchUndoButton.addEventListener('click', () => {
            inlineSketchShapes.pop();
            inlineSketchRender();
            inlineSketchSerialize();
        });
    }

    if (sketchClearButton) {
        sketchClearButton.addEventListener('click', () => {
            inlineSketchShapes = [];
            inlineSketchPreview = null;
            inlineSketchRender();
            inlineSketchSerialize();
        });
    }

    if (sketchFullscreenButton && sketchStage) {
        const syncFullscreenButtonLabel = () => {
            const isFullscreen = document.fullscreenElement === sketchStage;
            sketchFullscreenButton.textContent = isFullscreen ? 'Sair tela cheia' : 'Tela cheia';

            if (sketchCanvas) {
                sketchCanvas.style.height = isFullscreen ? '78vh' : '';
            }

            if (sketchStage) {
                sketchStage.style.maxWidth = isFullscreen ? '100vw' : '';
            }
        };

        sketchFullscreenButton.addEventListener('click', async () => {
            if (document.fullscreenElement === sketchStage) {
                await document.exitFullscreen();
                return;
            }

            if (typeof sketchStage.requestFullscreen === 'function') {
                await sketchStage.requestFullscreen();
            }
        });

        document.addEventListener('fullscreenchange', syncFullscreenButtonLabel);
        syncFullscreenButtonLabel();
    }

    if (sketchEnabledCheckbox) {
        sketchEnabledCheckbox.addEventListener('change', inlineSketchSyncVisibility);
    }

    if (helpToggleButton && helpContentElement) {
        const isCollapsed = window.localStorage.getItem(INLINE_HELP_COLLAPSED_KEY) === '1';
        inlineSketchSetHelpCollapsed(isCollapsed);

        helpToggleButton.addEventListener('click', () => {
            const currentlyCollapsed = helpContentElement.classList.contains('hidden');
            inlineSketchSetHelpCollapsed(!currentlyCollapsed);
        });
    }

    if (dimensionLabelInput) {
        const saved = window.localStorage.getItem(INLINE_DIMENSION_STORAGE_KEY);
        if ((dimensionLabelInput.value || '').trim() === '' && saved) {
            dimensionLabelInput.value = saved;
        }

        dimensionLabelInput.addEventListener('input', () => {
            window.localStorage.setItem(INLINE_DIMENSION_STORAGE_KEY, String(dimensionLabelInput.value || '').trim());
        });
    }

    if (inlineTextValueInput) {
        const saved = window.localStorage.getItem(INLINE_TEXT_STORAGE_KEY);
        if ((inlineTextValueInput.value || '').trim() === '' && saved) {
            inlineTextValueInput.value = saved;
        }

        inlineTextValueInput.addEventListener('input', () => {
            window.localStorage.setItem(INLINE_TEXT_STORAGE_KEY, String(inlineTextValueInput.value || '').trim());
        });
    }

    dimensionSuggestionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const value = String(button.dataset.value || '').trim();
            if (dimensionLabelInput && value !== '') {
                dimensionLabelInput.value = value;
                window.localStorage.setItem(INLINE_DIMENSION_STORAGE_KEY, value);
                dimensionLabelInput.focus();
            }
        });
    });

    inlineTextSuggestionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const value = String(button.dataset.value || '').trim();
            if (inlineTextValueInput && value !== '') {
                inlineTextValueInput.value = value;
                window.localStorage.setItem(INLINE_TEXT_STORAGE_KEY, value);
                inlineTextValueInput.focus();
            }
        });
    });

    if (repeatLastDimensionButton) {
        repeatLastDimensionButton.addEventListener('click', () => {
            const lastDimension = [...inlineSketchShapes].reverse().find((shape) => shape.type === 'dimension');
            if (!lastDimension) {
                showInlineSketchFeedback('Nenhuma cota anterior para repetir.', 'warning');
                return;
            }

            inlineSketchShapes.push({
                ...lastDimension,
                x1: Number(lastDimension.x1 || 0) + 12,
                y1: Number(lastDimension.y1 || 0) + 12,
                x2: Number(lastDimension.x2 || 0) + 12,
                y2: Number(lastDimension.y2 || 0) + 12,
            });
            inlineSketchRender();
            inlineSketchSerialize();
            showInlineSketchFeedback('Última cota repetida com deslocamento.', 'info');
        });
    }

    if (repeatLastTextButton) {
        repeatLastTextButton.addEventListener('click', () => {
            const lastText = [...inlineSketchShapes].reverse().find((shape) => shape.type === 'text');
            if (!lastText) {
                showInlineSketchFeedback('Nenhum texto anterior para repetir.', 'warning');
                return;
            }

            inlineSketchShapes.push({
                ...lastText,
                x: Number(lastText.x || 0) + 16,
                y: Number(lastText.y || 0) + 16,
            });
            inlineSketchRender();
            inlineSketchSerialize();
            showInlineSketchFeedback('Último texto repetido com deslocamento.', 'info');
        });
    }

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
    };

    const recalculateQuoteTotals = () => {
        if (!itemsContainer || !subtotalElement || !discountElement || !totalElement) {
            return;
        }

        let subtotal = 0;
        itemsContainer.querySelectorAll('.js-item-row').forEach((row) => {
            const quantityInput = row.querySelector('.js-quantity');
            const priceInput = row.querySelector('.js-item-price');
            const quantity = Number.parseFloat(quantityInput?.value || '0');
            const price = Number.parseFloat(priceInput?.value || '0');

            if (Number.isFinite(quantity) && Number.isFinite(price) && quantity > 0 && price >= 0) {
                subtotal += quantity * price;
            }
        });

        const discount = Number.parseFloat(discountInput?.value || '0');
        const safeDiscount = Number.isFinite(discount) && discount > 0 ? discount : 0;
        const total = Math.max(subtotal - safeDiscount, 0);

        subtotalElement.textContent = formatCurrency(subtotal);
        discountElement.textContent = formatCurrency(safeDiscount);
        totalElement.textContent = formatCurrency(total);
    };

    const createItemTemplate = (index) => `
        <div class="border border-gray-200 p-3 rounded-lg bg-gray-50 js-item-row" data-index="${index}">
            <div class="flex justify-between items-center border-b border-gray-200 pb-2 mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-600">Item #${index + 1}</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 js-item-status-badge">Item vazio</span>
                </div>
                <button type="button" class="text-xs text-red-600 hover:text-red-700 js-remove-item-row">Remover</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Produto</label>
                    <select name="items[${index}][catalog_item_id]" class="w-full px-2 py-2 border border-gray-300 rounded js-product-select">
                        <option value="">Selecione o produto...</option>
                        @foreach($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-type="{{ $product->item_type }}"
                                data-price="{{ $product->price }}"
                                data-image="{{ $product->image_path ?? '' }}"
                                data-kgm="{{ $product->effective_weight_per_meter_kg ?? '' }}"
                            >{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Quantidade</label>
                    <input type="number" step="0.001" name="items[${index}][quantity]" value="" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-quantity">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Total do item</label>
                    <input type="number" step="0.01" value="" placeholder="0,00" class="w-full px-2 py-2 border border-gray-300 rounded bg-white js-line-total" readonly>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Dados automáticos do produto</label>
                    <input type="hidden" name="items[${index}][item_name]" value="" class="js-item-name">
                    <input type="hidden" name="items[${index}][item_type]" value="produto" class="js-item-type">
                    <input type="hidden" name="items[${index}][unit_price]" value="" class="js-item-price">
                    <input type="hidden" name="items[${index}][weight_per_meter_kg]" value="" class="js-item-kgm">
                    <div class="px-2 py-2 border border-gray-300 rounded bg-white">
                        <div class="text-sm font-medium text-gray-900 js-item-name-display">-</div>
                        <div class="text-xs text-gray-600 js-item-type-display">-</div>
                        <div class="text-xs text-gray-700 js-item-price-display">R$ 0,00</div>
                        <div class="text-xs text-gray-700 js-item-kgm-display">kg/m -</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Largura (mm)</label>
                    <input type="number" step="0.01" name="items[${index}][width_mm]" value="" placeholder="0,00" class="w-full px-2 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Altura (mm)</label>
                    <input type="number" step="0.01" name="items[${index}][height_mm]" value="" placeholder="0,00" class="w-full px-2 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Imagem do item (opcional)</label>
                    <input type="hidden" name="items[${index}][existing_image]" value="" class="js-existing-image">
                    <input type="file" name="items[${index}][image]" accept="image/*" class="w-full text-xs border border-gray-300 rounded px-2 py-2 js-image-upload">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ações da imagem</label>
                    <label class="inline-flex items-center gap-1 text-xs text-gray-600">
                        <input type="checkbox" name="items[${index}][remove_image]" value="1" class="js-remove-image">
                        Remover imagem
                    </label>
                    <a href="#" target="_blank" class="hidden text-xs text-blue-600 hover:underline js-image-link">Ver imagem atual</a>
                    <img src="" alt="Miniatura do item" class="hidden mt-2 w-16 h-16 object-cover border border-gray-200 rounded js-image-preview">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">BNF</label>
                    <input type="text" name="items[${index}][bnf]" value="" placeholder="Cor / código" class="w-full px-2 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Corte (barra)</label>
                    <input type="text" name="items[${index}][bar_cut_size]" value="" placeholder="Ex: 6m" class="w-full px-2 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Qtd. peças</label>
                    <input type="number" step="0.001" name="items[${index}][pieces_quantity]" value="" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-pieces-quantity">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Peso</label>
                    <input type="number" step="0.001" name="items[${index}][weight]" value="" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-weight">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Peso total</label>
                    <input type="number" step="0.001" name="items[${index}][total_weight]" value="" placeholder="0,000" class="w-full px-2 py-2 border border-gray-300 rounded js-total-weight">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observação técnica</label>
                    <input type="text" name="items[${index}][item_observation]" value="" placeholder="Detalhe do item" class="w-full px-2 py-2 border border-gray-300 rounded">
                </div>
            </div>
        </div>
    `;

    const bindItemRow = (row) => {
        const select = row.querySelector('.js-product-select');
        if (!select) {
            return;
        }

        const nameInput = row.querySelector('.js-item-name');
        const typeInput = row.querySelector('.js-item-type');
        const typeDisplayInput = row.querySelector('.js-item-type-display');
        const nameDisplayInput = row.querySelector('.js-item-name-display');
        const priceDisplayInput = row.querySelector('.js-item-price-display');
        const kgmDisplayInput = row.querySelector('.js-item-kgm-display');
        const priceInput = row.querySelector('.js-item-price');
        const kgmInput = row.querySelector('.js-item-kgm');
        const quantityInput = row.querySelector('.js-quantity');
        const lineTotalInput = row.querySelector('.js-line-total');
        const existingImageInput = row.querySelector('.js-existing-image');
        const removeImageInput = row.querySelector('.js-remove-image');
        const imageLink = row.querySelector('.js-image-link');
        const imageUploadInput = row.querySelector('.js-image-upload');
        const imagePreview = row.querySelector('.js-image-preview');
        const removeRowButton = row.querySelector('.js-remove-item-row');
        const piecesQuantityInput = row.querySelector('.js-pieces-quantity');
        const weightInput = row.querySelector('.js-weight');
        const totalWeightInput = row.querySelector('.js-total-weight');
        const statusBadge = row.querySelector('.js-item-status-badge');

        const getRowStatus = () => {
            const hasData = rowHasAnyData(row);
            const hasProduct = select && String(select.value || '').trim() !== '';
            const quantity = Number.parseFloat(quantityInput?.value || '0');
            const hasQuantity = Number.isFinite(quantity) && quantity > 0;

            if (!hasData) {
                return 'empty';
            }

            if (hasProduct && hasQuantity) {
                return 'valid';
            }

            return 'incomplete';
        };

        const updateRowStatusBadge = () => {
            if (!statusBadge) {
                return;
            }

            const status = getRowStatus();
            statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full js-item-status-badge';
            if (status === 'valid') {
                statusBadge.classList.add('bg-green-100', 'text-green-700');
                statusBadge.textContent = 'Item válido';
                return;
            }

            if (status === 'incomplete') {
                statusBadge.classList.add('bg-amber-100', 'text-amber-700');
                statusBadge.textContent = 'Item incompleto';
                return;
            }

            statusBadge.classList.add('bg-gray-200', 'text-gray-700');
            statusBadge.textContent = 'Item vazio';
        };

        const resolveAssetUrl = (path) => {
            const normalized = String(path || '').trim();
            if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
                return normalized;
            }

            return '/' + normalized.replace(/^\/+/, '');
        };

        const updateImageElements = () => {
            if (!imageLink || !imagePreview || !existingImageInput) {
                return;
            }

            if (removeImageInput?.checked) {
                imageLink.classList.add('hidden');
                imageLink.setAttribute('href', '#');
                imagePreview.classList.add('hidden');
                imagePreview.setAttribute('src', '');
                return;
            }

            const existingImage = (existingImageInput.value || '').trim();
            if (existingImage !== '') {
                const url = resolveAssetUrl(existingImage);
                imageLink.setAttribute('href', url);
                imageLink.classList.remove('hidden');
                imagePreview.setAttribute('src', url);
                imagePreview.classList.remove('hidden');
            } else {
                imageLink.classList.add('hidden');
                imageLink.setAttribute('href', '#');
                imagePreview.classList.add('hidden');
                imagePreview.setAttribute('src', '');
            }
        };

        const applyProduct = () => {
            const option = select.options[select.selectedIndex];
            if (!option || option.value === '') {
                return;
            }

            const productName = option.getAttribute('data-name') || '';
            const productType = option.getAttribute('data-type') || 'produto';
            const productPrice = option.getAttribute('data-price') || '';
            const productImage = option.getAttribute('data-image') || '';
            const productKgm = option.getAttribute('data-kgm') || '';

            if (nameInput) {
                nameInput.value = productName;
            }

            if (nameDisplayInput) {
                nameDisplayInput.textContent = productName || '-';
            }

            if (typeInput) {
                typeInput.value = productType;
            }

            if (typeDisplayInput) {
                typeDisplayInput.value = productType.charAt(0).toUpperCase() + productType.slice(1);
                typeDisplayInput.textContent = productType.charAt(0).toUpperCase() + productType.slice(1);
            }

            if (priceInput) {
                priceInput.value = productPrice;
            }

            if (priceDisplayInput) {
                const parsedPrice = Number.parseFloat(productPrice || '0');
                priceDisplayInput.textContent = formatCurrency(Number.isFinite(parsedPrice) ? parsedPrice : 0);
            }

            if (kgmInput) {
                kgmInput.value = productKgm;
            }

            if (kgmDisplayInput) {
                const parsedKgm = Number.parseFloat(productKgm || '');
                kgmDisplayInput.textContent = Number.isFinite(parsedKgm)
                    ? `kg/m ${parsedKgm.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })}`
                    : 'kg/m -';

                if (Number.isFinite(parsedKgm) && weightInput && String(weightInput.value || '').trim() === '') {
                    weightInput.value = parsedKgm.toFixed(3);
                    calculateTotalWeight();
                }
            }

            if (existingImageInput) {
                existingImageInput.value = productImage;
                if (removeImageInput) {
                    removeImageInput.checked = false;
                }
            }

            updateImageElements();
        };

        const calculateTotalWeight = () => {
            if (!piecesQuantityInput || !weightInput || !totalWeightInput) {
                return;
            }

            const pieces = Number.parseFloat(piecesQuantityInput.value || '0');
            const weight = Number.parseFloat(weightInput.value || '0');
            if (!Number.isFinite(pieces) || !Number.isFinite(weight) || pieces <= 0 || weight < 0) {
                if ((piecesQuantityInput.value || '') === '' || (weightInput.value || '') === '') {
                    totalWeightInput.value = '';
                }
                return;
            }

            totalWeightInput.value = (pieces * weight).toFixed(3);
        };

        const calculateLineTotal = () => {
            if (!quantityInput || !priceInput || !lineTotalInput) {
                recalculateQuoteTotals();
                updateRowStatusBadge();
                updateSubmitState();
                return;
            }

            const quantity = Number.parseFloat(quantityInput.value || '0');
            const price = Number.parseFloat(priceInput.value || '0');
            if (!Number.isFinite(quantity) || !Number.isFinite(price) || quantity <= 0 || price < 0) {
                lineTotalInput.value = '';
                recalculateQuoteTotals();
                updateRowStatusBadge();
                updateSubmitState();
                return;
            }

            lineTotalInput.value = (quantity * price).toFixed(2);
            recalculateQuoteTotals();
            updateRowStatusBadge();
            updateSubmitState();
        };

        if (imageUploadInput) {
            imageUploadInput.addEventListener('change', () => {
                const file = imageUploadInput.files?.[0];
                if (!file || !imagePreview || !imageLink) {
                    updateImageElements();
                    return;
                }

                const objectUrl = URL.createObjectURL(file);
                imagePreview.setAttribute('src', objectUrl);
                imagePreview.classList.remove('hidden');
                imageLink.classList.add('hidden');
            });
        }

        if (removeImageInput) {
            removeImageInput.addEventListener('change', updateImageElements);
        }

        if (piecesQuantityInput) {
            piecesQuantityInput.addEventListener('input', calculateTotalWeight);
        }

        if (weightInput) {
            weightInput.addEventListener('input', calculateTotalWeight);
        }

        if (quantityInput) {
            quantityInput.addEventListener('input', calculateLineTotal);
        }

        if (priceInput) {
            priceInput.addEventListener('input', calculateLineTotal);
        }

        if (removeRowButton) {
            removeRowButton.addEventListener('click', () => {
                row.remove();
                recalculateQuoteTotals();
                updateSubmitState();
            });
        }

        select.addEventListener('change', applyProduct);
        applyProduct();
        updateImageElements();
        calculateTotalWeight();
        calculateLineTotal();
        updateRowStatusBadge();
    };

    const markFieldError = (element, hasError) => {
        if (!element) {
            return;
        }

        element.classList.toggle('border-red-500', hasError);
        element.classList.toggle('ring-1', hasError);
        element.classList.toggle('ring-red-200', hasError);
    };

    const clearRowErrorState = (row) => {
        row.classList.remove('border-red-300', 'js-item-row-error');
        row.classList.add('border-gray-200');
    };

    const setRowErrorState = (row) => {
        row.classList.remove('border-gray-200');
        row.classList.add('border-red-300', 'js-item-row-error');
    };

    const rowHasAnyData = (row) => {
        const fields = row.querySelectorAll('input[name^="items["], select[name^="items["]');
        for (const field of fields) {
            const name = field.getAttribute('name') || '';
            if (name.endsWith('[remove_image]')) {
                continue;
            }

            if (field.type === 'hidden') {
                continue;
            }

            if (field.type === 'file') {
                if (field.files && field.files.length > 0) {
                    return true;
                }
                continue;
            }

            if (field.type === 'checkbox') {
                if (field.checked) {
                    return true;
                }
                continue;
            }

            if (String(field.value || '').trim() !== '') {
                return true;
            }
        }

        return false;
    };

    const updateSubmitState = () => {
        if (!submitButton) {
            return;
        }

        const rows = Array.from(document.querySelectorAll('.js-item-row'));
        const validRowsCount = rows.filter((row) => {
            const hasData = rowHasAnyData(row);
            if (!hasData) {
                return false;
            }

            const productSelect = row.querySelector('.js-product-select');
            const quantityInput = row.querySelector('.js-quantity');
            const hasProduct = productSelect && String(productSelect.value || '').trim() !== '';
            const quantity = Number.parseFloat(quantityInput?.value || '0');
            const hasQuantity = Number.isFinite(quantity) && quantity > 0;

            return hasProduct && hasQuantity;
        }).length;

        const canSubmit = validRowsCount > 0;
        submitButton.disabled = !canSubmit;

        if (submitHint) {
            submitHint.textContent = canSubmit
                ? 'Pronto para salvar.'
                : 'Preencha ao menos 1 item com produto e quantidade.';
        }
    };

    const updateQuickQuoteBadge = () => {
        if (!quickQuoteBadge || !clientSelect) {
            return;
        }

        const hasClient = String(clientSelect.value || '').trim() !== '';
        quickQuoteBadge.classList.toggle('hidden', hasClient);
        quickQuoteBadge.classList.toggle('inline-flex', !hasClient);
    };

    document.querySelectorAll('.js-item-row').forEach(bindItemRow);

    const validateBeforeSubmit = () => {
        let firstInvalidField = null;

        const rows = Array.from(document.querySelectorAll('.js-item-row'));
        let validRowsCount = 0;

        rows.forEach((row) => {
            clearRowErrorState(row);
            const productSelect = row.querySelector('.js-product-select');
            const quantityInput = row.querySelector('.js-quantity');

            markFieldError(productSelect, false);
            markFieldError(quantityInput, false);

            const hasData = rowHasAnyData(row);
            const hasProduct = productSelect && String(productSelect.value || '').trim() !== '';
            const quantity = Number.parseFloat(quantityInput?.value || '0');
            const hasQuantity = Number.isFinite(quantity) && quantity > 0;

            if (!hasData) {
                return;
            }

            if (!hasProduct || !hasQuantity) {
                setRowErrorState(row);
                if (!hasProduct) {
                    markFieldError(productSelect, true);
                    firstInvalidField = firstInvalidField || productSelect;
                }
                if (!hasQuantity) {
                    markFieldError(quantityInput, true);
                    firstInvalidField = firstInvalidField || quantityInput;
                }
                return;
            }

            validRowsCount += 1;
        });

        if (validRowsCount === 0) {
            const firstRow = rows[0] || null;
            if (firstRow) {
                setRowErrorState(firstRow);
                const firstProduct = firstRow.querySelector('.js-product-select');
                const firstQuantity = firstRow.querySelector('.js-quantity');
                markFieldError(firstProduct, true);
                markFieldError(firstQuantity, true);
                firstInvalidField = firstInvalidField || firstProduct || firstQuantity;
            }
        }

        if (firstInvalidField) {
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidField.focus();
            return false;
        }

        return true;
    };

    if (addItemButton && itemsContainer) {
        addItemButton.addEventListener('click', () => {
            const indexes = Array.from(itemsContainer.querySelectorAll('.js-item-row'))
                .map((row) => Number.parseInt(row.getAttribute('data-index') || '-1', 10))
                .filter((value) => Number.isInteger(value) && value >= 0);
            const nextIndex = indexes.length > 0 ? Math.max(...indexes) + 1 : 0;
            itemsContainer.insertAdjacentHTML('beforeend', createItemTemplate(nextIndex));
            const newRow = itemsContainer.querySelector('.js-item-row:last-child');
            if (newRow) {
                bindItemRow(newRow);
                recalculateQuoteTotals();
            }
        });
    }

    if (discountInput) {
        discountInput.addEventListener('input', recalculateQuoteTotals);
    }

    if (clientSelect) {
        clientSelect.addEventListener('change', () => {
            updateSubmitState();
            updateQuickQuoteBadge();
        });
    }

    if (quoteForm) {
        quoteForm.addEventListener('submit', (event) => {
            inlineSketchSerialize();
            if (!validateBeforeSubmit()) {
                event.preventDefault();
            }
        });
    }

    const firstServerErrorRow = document.querySelector('.js-item-row-error');
    if (firstServerErrorRow) {
        firstServerErrorRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const firstFocusable = firstServerErrorRow.querySelector('select, input:not([type="hidden"]), textarea');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }

    recalculateQuoteTotals();
    updateSubmitState();
    updateQuickQuoteBadge();
    inlineSketchSetTool('pen');
    inlineSketchLoad();
    inlineSketchSyncVisibility();
});
</script>
@endsection
