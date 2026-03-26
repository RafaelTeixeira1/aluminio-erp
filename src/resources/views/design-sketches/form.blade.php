@extends('layouts.app')

@section('title', $sketch ? 'Editar Desenho' : 'Novo Desenho')
@section('page-title', $sketch ? 'Editar Desenho' : 'Novo Desenho')

@section('content')
<div class="max-w-7xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $sketch ? 'Editar desenho' : 'Novo desenho' }}</h1>
            <p class="text-gray-600 mt-1">Desenhe janelas, portas e divisorias para acelerar o entendimento tecnico.</p>
        </div>
        <a href="{{ route('designSketches.index') }}" class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg hover:bg-gray-300">Voltar</a>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">{{ $errors->first() }}</div>
    @endif

    <form id="sketch-form" action="{{ $sketch ? route('designSketches.update', $sketch) : route('designSketches.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($sketch)
            @method('PUT')
        @endif

        <div class="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Titulo *</label>
                <input type="text" name="title" value="{{ old('title', $sketch->title ?? '') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Janela sala 1,20 x 1,00">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Largura (mm)</label>
                <input type="number" step="0.01" min="1" name="width_mm" value="{{ old('width_mm', $sketch->width_mm ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="1200">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Altura (mm)</label>
                <input type="number" step="0.01" min="1" name="height_mm" value="{{ old('height_mm', $sketch->height_mm ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="1000">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Orcamento relacionado (opcional)</label>
                <select name="quote_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Sem vinculacao</option>
                    @php $selectedQuote = (string) old('quote_id', $sketch->quote_id ?? ($defaultQuoteId ?? '')); @endphp
                    @foreach($quotes as $quote)
                        <option value="{{ $quote->id }}" {{ $selectedQuote === (string) $quote->id ? 'selected' : '' }}>
                            #{{ $quote->id }} - {{ $quote->client?->name ?? 'Orcamento rapido' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-6">
                <label class="block text-xs font-medium text-gray-600 mb-1">Observacoes</label>
                <input type="text" name="notes" value="{{ old('notes', $sketch->notes ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Janela de correr com 2 folhas e bandeira fixa">
            </div>
            <input type="hidden" name="canvas_json" id="canvas_json" value="{{ old('canvas_json', $sketch->canvas_json ?? '') }}">
            <input type="hidden" name="preview_png" id="preview_png" value="{{ old('preview_png', $sketch->preview_png ?? '') }}">
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <button type="button" class="tool-btn bg-blue-600 text-white px-3 py-1.5 rounded" data-tool="pen">Mao livre</button>
                <button type="button" class="tool-btn bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="line">Linha</button>
                <button type="button" class="tool-btn bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="rect">Retangulo</button>
                <button type="button" class="tool-btn bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="dimension">Cota</button>
                <button type="button" class="tool-btn bg-gray-200 text-gray-900 px-3 py-1.5 rounded" data-tool="text">Texto</button>
                <button type="button" id="undo-btn" class="bg-gray-200 text-gray-900 px-3 py-1.5 rounded">Desfazer</button>
                <button type="button" id="clear-btn" class="bg-red-100 text-red-700 px-3 py-1.5 rounded">Limpar</button>

                <label class="text-xs text-gray-600 ml-2">Cor</label>
                <input type="color" id="color-picker" value="#1f2937" class="w-10 h-8 border border-gray-300 rounded">

                <label class="text-xs text-gray-600">Espessura</label>
                <input type="range" id="line-width" min="1" max="12" value="2" class="w-32">
                <span id="line-width-label" class="text-xs text-gray-600">2px</span>

                <label class="text-xs text-gray-600 ml-2">Escala</label>
                <input type="number" id="mm-per-pixel" min="0.001" step="0.001" value="1" class="w-24 h-8 border border-gray-300 rounded px-2 text-xs">
                <span class="text-xs text-gray-600">mm/px</span>

                <label class="inline-flex items-center gap-1 text-xs text-gray-700 ml-2">
                    <input type="checkbox" id="snap-enabled" checked>
                    Snap
                </label>
                <label class="text-xs text-gray-600">Tolerancia</label>
                <input type="number" id="snap-tolerance" min="2" max="40" step="1" value="12" class="w-16 h-8 border border-gray-300 rounded px-2 text-xs">

                <label class="inline-flex items-center gap-1 text-xs text-gray-700 ml-2">
                    <input type="checkbox" id="dimension-orthogonal" checked>
                    Cota ortogonal
                </label>
                <label class="inline-flex items-center gap-1 text-xs text-gray-700">
                    <input type="checkbox" id="dimension-edit-label">
                    Editar valor da cota
                </label>
            </div>

            <div class="border border-gray-300 rounded-lg overflow-auto bg-gray-50">
                <canvas id="sketch-canvas" width="1200" height="650" class="bg-white"></canvas>
            </div>
            <p class="text-xs text-gray-500 mt-2">Dica: use a ferramenta Cota para marcar medidas automaticamente em mm.</p>

            <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2 text-xs">
                <div class="rounded border border-blue-200 bg-blue-50 px-3 py-2">
                    <span class="text-blue-700">Ferramenta:</span>
                    <strong id="tool-label" class="text-blue-900">Mao livre</strong>
                </div>
                <div class="rounded border border-indigo-200 bg-indigo-50 px-3 py-2 md:col-span-2">
                    <span class="text-indigo-700">Passo atual:</span>
                    <strong id="step-hint" class="text-indigo-900">Clique e arraste para desenhar.</strong>
                </div>
                <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2">
                    <span class="text-emerald-700">Escala:</span>
                    <strong id="scale-info" class="text-emerald-900">1.0000 mm/px</strong>
                </div>
                <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 md:col-span-4">
                    <span class="text-gray-600">Cursor:</span>
                    <strong id="cursor-info" class="text-gray-900">x: 0, y: 0</strong>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">{{ $sketch ? 'Atualizar desenho' : 'Salvar desenho' }}</button>
            <a href="{{ route('designSketches.index') }}" class="bg-gray-300 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-400">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('sketch-canvas');
    const ctx = canvas.getContext('2d');
    const form = document.getElementById('sketch-form');
    const canvasJsonInput = document.getElementById('canvas_json');
    const previewPngInput = document.getElementById('preview_png');
    const colorPicker = document.getElementById('color-picker');
    const lineWidth = document.getElementById('line-width');
    const lineWidthLabel = document.getElementById('line-width-label');
    const mmPerPixelInput = document.getElementById('mm-per-pixel');
    const widthMmInput = document.querySelector('input[name="width_mm"]');
    const heightMmInput = document.querySelector('input[name="height_mm"]');
    const snapEnabledInput = document.getElementById('snap-enabled');
    const snapToleranceInput = document.getElementById('snap-tolerance');
    const dimensionOrthogonalInput = document.getElementById('dimension-orthogonal');
    const dimensionEditLabelInput = document.getElementById('dimension-edit-label');
    const toolLabel = document.getElementById('tool-label');
    const stepHint = document.getElementById('step-hint');
    const cursorInfo = document.getElementById('cursor-info');
    const scaleInfo = document.getElementById('scale-info');

    let tool = 'pen';
    let drawing = false;
    let startPoint = null;
    let currentPath = [];
    let shapes = [];
    let previewShape = null;
    let hoverPoint = null;
    let snapGuides = { x: null, y: null, point: null };

    const toolButtons = Array.from(document.querySelectorAll('.tool-btn'));

    const setTool = (nextTool) => {
        tool = nextTool;
        toolButtons.forEach((btn) => {
            const active = btn.dataset.tool === nextTool;
            btn.classList.toggle('bg-blue-600', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('bg-gray-200', !active);
            btn.classList.toggle('text-gray-900', !active);
        });

        updateHelper();
    };

    const toolName = (value) => {
        if (value === 'pen') return 'Mao livre';
        if (value === 'line') return 'Linha';
        if (value === 'rect') return 'Retangulo';
        if (value === 'dimension') return 'Cota';
        if (value === 'text') return 'Texto';

        return value;
    };

    const updateHelper = (point = null) => {
        if (toolLabel) {
            toolLabel.textContent = toolName(tool);
        }

        if (scaleInfo) {
            scaleInfo.textContent = `${readMmPerPixel().toFixed(4)} mm/px`;
        }

        if (cursorInfo && point) {
            cursorInfo.textContent = `x: ${Math.round(point.x)}, y: ${Math.round(point.y)}`;
        }

        if (!stepHint) {
            return;
        }

        if (tool === 'dimension' && !drawing) {
            stepHint.textContent = 'Cota: clique no ponto inicial da medida.';
            return;
        }

        if (tool === 'dimension' && drawing) {
            stepHint.textContent = 'Cota: mova o mouse para o ponto final e solte para confirmar.';
            return;
        }

        if (tool === 'text') {
            stepHint.textContent = 'Texto: clique no canvas e digite a anotacao.';
            return;
        }

        if (!drawing) {
            stepHint.textContent = 'Clique e arraste para desenhar.';
            return;
        }

        stepHint.textContent = 'Solte o mouse para concluir o desenho atual.';
    };

    const getPoint = (event) => {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;

        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY,
        };
    };

    const readMmPerPixel = () => {
        const direct = Number.parseFloat(mmPerPixelInput?.value || '0');
        if (Number.isFinite(direct) && direct > 0) {
            return direct;
        }

        const widthMm = Number.parseFloat(widthMmInput?.value || '0');
        if (Number.isFinite(widthMm) && widthMm > 0) {
            return widthMm / canvas.width;
        }

        const heightMm = Number.parseFloat(heightMmInput?.value || '0');
        if (Number.isFinite(heightMm) && heightMm > 0) {
            return heightMm / canvas.height;
        }

        return 1;
    };

    const readSnapTolerance = () => {
        const value = Number.parseFloat(snapToleranceInput?.value || '12');
        if (Number.isFinite(value) && value > 0) {
            return value;
        }

        return 12;
    };

    const collectSnapPoints = () => {
        const points = [];

        shapes.forEach((shape) => {
            if ((shape.type === 'line' || shape.type === 'dimension') && shape.start && shape.end) {
                points.push(shape.start, shape.end);
            }

            if (shape.type === 'rect' && shape.start && shape.end) {
                const x1 = shape.start.x;
                const y1 = shape.start.y;
                const x2 = shape.end.x;
                const y2 = shape.end.y;
                points.push(
                    { x: x1, y: y1 },
                    { x: x1, y: y2 },
                    { x: x2, y: y1 },
                    { x: x2, y: y2 },
                );
            }

            if (shape.type === 'text' && shape.position) {
                points.push(shape.position);
            }

            if (shape.type === 'pen' && Array.isArray(shape.points) && shape.points.length > 1) {
                points.push(shape.points[0], shape.points[shape.points.length - 1]);
            }
        });

        return points;
    };

    const snapPoint = (point) => {
        if (!snapEnabledInput?.checked) {
            snapGuides = { x: null, y: null, point: null };
            return point;
        }

        const tolerance = readSnapTolerance();
        const snapPoints = collectSnapPoints();
        if (snapPoints.length === 0) {
            return point;
        }

        let snappedX = point.x;
        let snappedY = point.y;
        let bestDistance = Infinity;

        snapPoints.forEach((candidate) => {
            if (!candidate) {
                return;
            }

            const dx = candidate.x - point.x;
            const dy = candidate.y - point.y;
            const distance = Math.sqrt((dx * dx) + (dy * dy));

            if (distance <= tolerance && distance < bestDistance) {
                bestDistance = distance;
                snappedX = candidate.x;
                snappedY = candidate.y;
            }
        });

        snapPoints.forEach((candidate) => {
            if (!candidate) {
                return;
            }

            if (Math.abs(candidate.x - point.x) <= tolerance) {
                snappedX = candidate.x;
                snapGuides.x = candidate.x;
            }

            if (Math.abs(candidate.y - point.y) <= tolerance) {
                snappedY = candidate.y;
                snapGuides.y = candidate.y;
            }
        });

        if (bestDistance !== Infinity) {
            snapGuides.point = { x: snappedX, y: snappedY };
        } else {
            snapGuides.point = null;
        }

        return { x: snappedX, y: snappedY };
    };

    const applyDimensionConstraint = (start, end) => {
        if (!dimensionOrthogonalInput?.checked) {
            return end;
        }

        const dx = end.x - start.x;
        const dy = end.y - start.y;

        if (Math.abs(dx) >= Math.abs(dy)) {
            return { x: end.x, y: start.y };
        }

        return { x: start.x, y: end.y };
    };

    const drawArrowHead = (x, y, angle, size, color) => {
        ctx.save();
        ctx.fillStyle = color;
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.lineTo(-size, size * 0.45);
        ctx.lineTo(-size, -size * 0.45);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
    };

    const drawShape = (shape) => {
        ctx.save();
        ctx.strokeStyle = shape.color || '#111827';
        ctx.fillStyle = shape.color || '#111827';
        ctx.lineWidth = Number(shape.lineWidth || 2);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (shape.type === 'pen' && Array.isArray(shape.points) && shape.points.length > 0) {
            ctx.beginPath();
            ctx.moveTo(shape.points[0].x, shape.points[0].y);
            for (let i = 1; i < shape.points.length; i++) {
                ctx.lineTo(shape.points[i].x, shape.points[i].y);
            }
            ctx.stroke();
        }

        if (shape.type === 'line' && shape.start && shape.end) {
            ctx.beginPath();
            ctx.moveTo(shape.start.x, shape.start.y);
            ctx.lineTo(shape.end.x, shape.end.y);
            ctx.stroke();
        }

        if (shape.type === 'rect' && shape.start && shape.end) {
            const x = Math.min(shape.start.x, shape.end.x);
            const y = Math.min(shape.start.y, shape.end.y);
            const w = Math.abs(shape.end.x - shape.start.x);
            const h = Math.abs(shape.end.y - shape.start.y);
            ctx.strokeRect(x, y, w, h);
        }

        if (shape.type === 'dimension' && shape.start && shape.end) {
            const start = shape.start;
            const end = shape.end;
            const dx = end.x - start.x;
            const dy = end.y - start.y;
            const angle = Math.atan2(dy, dx);
            const distancePx = Math.sqrt((dx * dx) + (dy * dy));
            const mmPerPixel = Number(shape.mmPerPixel || 1);
            const distanceMm = distancePx * mmPerPixel;
            const label = shape.label || `${distanceMm.toFixed(1)} mm`;
            const arrowSize = Math.max(6, Number(shape.lineWidth || 2) * 3);

            ctx.beginPath();
            ctx.moveTo(start.x, start.y);
            ctx.lineTo(end.x, end.y);
            ctx.stroke();

            drawArrowHead(end.x, end.y, angle, arrowSize, shape.color || '#111827');
            drawArrowHead(start.x, start.y, angle + Math.PI, arrowSize, shape.color || '#111827');

            const midX = (start.x + end.x) / 2;
            const midY = (start.y + end.y) / 2;
            const offset = 16;
            const textX = midX + Math.cos(angle - Math.PI / 2) * offset;
            const textY = midY + Math.sin(angle - Math.PI / 2) * offset;

            ctx.save();
            ctx.font = `${Math.max(11, Number(shape.lineWidth || 2) * 5)}px Arial`;
            const textWidth = ctx.measureText(label).width;
            ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
            ctx.fillRect(textX - (textWidth / 2) - 4, textY - 10, textWidth + 8, 16);
            ctx.fillStyle = shape.color || '#111827';
            ctx.fillText(label, textX - (textWidth / 2), textY + 2);
            ctx.restore();
        }

        if (shape.type === 'text' && shape.position) {
            ctx.font = `${Math.max(10, Number(shape.lineWidth || 2) * 6)}px Arial`;
            ctx.fillText(shape.text || '', shape.position.x, shape.position.y);
        }

        ctx.restore();
    };

    const redraw = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        ctx.save();
        ctx.strokeStyle = '#f3f4f6';
        ctx.lineWidth = 1;
        for (let x = 0; x <= canvas.width; x += 40) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, canvas.height);
            ctx.stroke();
        }
        for (let y = 0; y <= canvas.height; y += 40) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
            ctx.stroke();
        }
        ctx.restore();

        shapes.forEach(drawShape);
        if (previewShape) {
            drawShape(previewShape);
        }

        if (snapEnabledInput?.checked) {
            ctx.save();
            ctx.setLineDash([5, 5]);
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(37, 99, 235, 0.65)';

            if (snapGuides.x !== null) {
                ctx.beginPath();
                ctx.moveTo(snapGuides.x, 0);
                ctx.lineTo(snapGuides.x, canvas.height);
                ctx.stroke();
            }

            if (snapGuides.y !== null) {
                ctx.beginPath();
                ctx.moveTo(0, snapGuides.y);
                ctx.lineTo(canvas.width, snapGuides.y);
                ctx.stroke();
            }

            if (snapGuides.point) {
                ctx.setLineDash([]);
                ctx.fillStyle = 'rgba(37, 99, 235, 0.9)';
                ctx.beginPath();
                ctx.arc(snapGuides.point.x, snapGuides.point.y, 4, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.restore();
        }
    };

    const pushShape = (shape) => {
        shapes.push(shape);
        previewShape = null;
        redraw();
    };

    canvas.addEventListener('mousedown', (event) => {
        const point = snapPoint(getPoint(event));
        hoverPoint = point;
        updateHelper(point);

        if (tool === 'text') {
            const text = window.prompt('Digite o texto para inserir no desenho:');
            if (text && text.trim() !== '') {
                pushShape({
                    type: 'text',
                    text: text.trim(),
                    position: point,
                    color: colorPicker.value,
                    lineWidth: Number(lineWidth.value),
                });
            }
            return;
        }

        drawing = true;
        startPoint = point;

        if (tool === 'pen') {
            currentPath = [point];
            previewShape = {
                type: 'pen',
                points: currentPath,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            };
            redraw();
        }
    });

    canvas.addEventListener('mousemove', (event) => {
        let point = snapPoint(getPoint(event));
        hoverPoint = point;
        updateHelper(point);

        if (!drawing || !startPoint) {
            redraw();
            return;
        }

        if (tool === 'dimension') {
            point = applyDimensionConstraint(startPoint, point);
        }

        if (tool === 'pen') {
            currentPath.push(point);
            previewShape = {
                type: 'pen',
                points: [...currentPath],
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            };
        }

        if (tool === 'line') {
            previewShape = {
                type: 'line',
                start: startPoint,
                end: point,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            };
        }

        if (tool === 'dimension') {
            const mmPerPixel = readMmPerPixel();
            const dx = point.x - startPoint.x;
            const dy = point.y - startPoint.y;
            const distancePx = Math.sqrt((dx * dx) + (dy * dy));
            const distanceMm = distancePx * mmPerPixel;

            previewShape = {
                type: 'dimension',
                start: startPoint,
                end: point,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
                mmPerPixel,
                label: `${distanceMm.toFixed(1)} mm`,
            };
        }

        if (tool === 'rect') {
            previewShape = {
                type: 'rect',
                start: startPoint,
                end: point,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            };
        }

        redraw();
    });

    window.addEventListener('mouseup', (event) => {
        if (!drawing || !startPoint) {
            return;
        }

        let point = snapPoint(getPoint(event));
        if (tool === 'dimension') {
            point = applyDimensionConstraint(startPoint, point);
        }

        if (tool === 'pen' && currentPath.length > 1) {
            pushShape({
                type: 'pen',
                points: [...currentPath],
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            });
        }

        if (tool === 'line') {
            pushShape({
                type: 'line',
                start: startPoint,
                end: point,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            });
        }

        if (tool === 'dimension') {
            const mmPerPixel = readMmPerPixel();
            const dx = point.x - startPoint.x;
            const dy = point.y - startPoint.y;
            const distancePx = Math.sqrt((dx * dx) + (dy * dy));
            const distanceMm = distancePx * mmPerPixel;
            let label = `${distanceMm.toFixed(1)} mm`;

            if (dimensionEditLabelInput?.checked) {
                const typed = window.prompt('Informe o valor da cota (mm):', distanceMm.toFixed(1));
                if (typed !== null && typed.trim() !== '') {
                    const numeric = Number.parseFloat(typed.replace(',', '.'));
                    if (Number.isFinite(numeric) && numeric > 0) {
                        label = `${numeric.toFixed(1)} mm`;
                    }
                }
            }

            pushShape({
                type: 'dimension',
                start: startPoint,
                end: point,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
                mmPerPixel,
                label,
            });
        }

        if (tool === 'rect') {
            pushShape({
                type: 'rect',
                start: startPoint,
                end: point,
                color: colorPicker.value,
                lineWidth: Number(lineWidth.value),
            });
        }

        drawing = false;
        startPoint = null;
        currentPath = [];
        previewShape = null;
        updateHelper(hoverPoint);
        redraw();
    });

    canvas.addEventListener('mouseleave', () => {
        hoverPoint = null;
        snapGuides = { x: null, y: null, point: null };
        if (cursorInfo) {
            cursorInfo.textContent = 'x: -, y: -';
        }
        redraw();
    });

    document.getElementById('undo-btn').addEventListener('click', () => {
        if (shapes.length === 0) {
            return;
        }
        shapes.pop();
        redraw();
    });

    document.getElementById('clear-btn').addEventListener('click', () => {
        if (!window.confirm('Limpar todo o desenho atual?')) {
            return;
        }
        shapes = [];
        previewShape = null;
        redraw();
    });

    toolButtons.forEach((btn) => {
        btn.addEventListener('click', () => setTool(btn.dataset.tool));
    });

    lineWidth.addEventListener('input', () => {
        lineWidthLabel.textContent = `${lineWidth.value}px`;
    });

    if (mmPerPixelInput) {
        mmPerPixelInput.addEventListener('change', () => {
            const value = Number.parseFloat(mmPerPixelInput.value || '0');
            if (!Number.isFinite(value) || value <= 0) {
                mmPerPixelInput.value = '1';
            }
            updateHelper(hoverPoint);
        });
    }

    if (snapToleranceInput) {
        snapToleranceInput.addEventListener('change', () => {
            const value = Number.parseFloat(snapToleranceInput.value || '0');
            if (!Number.isFinite(value) || value < 2) {
                snapToleranceInput.value = '12';
            }
            updateHelper(hoverPoint);
        });
    }

    snapEnabledInput?.addEventListener('change', () => {
        if (!snapEnabledInput.checked) {
            snapGuides = { x: null, y: null, point: null };
        }
        updateHelper(hoverPoint);
        redraw();
    });

    dimensionOrthogonalInput?.addEventListener('change', () => updateHelper(hoverPoint));
    dimensionEditLabelInput?.addEventListener('change', () => updateHelper(hoverPoint));

    form.addEventListener('submit', () => {
        const payload = {
            width: canvas.width,
            height: canvas.height,
            shapes,
        };

        canvasJsonInput.value = JSON.stringify(payload);
        previewPngInput.value = canvas.toDataURL('image/png');
    });

    const existing = canvasJsonInput.value || '';
    if (existing.trim() !== '') {
        try {
            const parsed = JSON.parse(existing);
            if (Array.isArray(parsed.shapes)) {
                shapes = parsed.shapes;
            }
        } catch (error) {
            console.warn('Nao foi possivel carregar desenho salvo.', error);
        }
    }

    const tryApplyAutoScale = () => {
        if (!mmPerPixelInput) {
            return;
        }

        const widthMm = Number.parseFloat(widthMmInput?.value || '0');
        if (Number.isFinite(widthMm) && widthMm > 0) {
            mmPerPixelInput.value = (widthMm / canvas.width).toFixed(4);
            return;
        }

        const heightMm = Number.parseFloat(heightMmInput?.value || '0');
        if (Number.isFinite(heightMm) && heightMm > 0) {
            mmPerPixelInput.value = (heightMm / canvas.height).toFixed(4);
        }
    };

    widthMmInput?.addEventListener('change', tryApplyAutoScale);
    heightMmInput?.addEventListener('change', tryApplyAutoScale);
    tryApplyAutoScale();

    setTool('pen');
    updateHelper();
    redraw();
});
</script>
@endsection
