<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocumentSequence;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SequenceController extends Controller
{
    public function __construct(private readonly SequenceService $sequenceService)
    {
    }

    public function index(): View
    {
        $sequences = DocumentSequence::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('settings.sequences.index', [
            'sequences' => $sequences,
        ]);
    }

    public function show(DocumentSequence $sequence): View
    {
        $sequence->loadCount('logs');
        $history = $sequence->logs()->latest('generated_at')->paginate(20);

        return view('settings.sequences.show', [
            'sequence' => $sequence,
            'history' => $history,
        ]);
    }

    public function edit(DocumentSequence $sequence): View
    {
        return view('settings.sequences.edit', [
            'sequence' => $sequence,
        ]);
    }

    public function update(Request $request, DocumentSequence $sequence): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'description' => 'required|string|max:160',
            'prefix' => 'required|string|max:20',
            'pattern' => 'required|string|max:60',
            'reset_frequency' => 'required|string|in:never,annual,monthly',
        ], [
            'description.required' => 'A descrição é obrigatória',
            'prefix.required' => 'O prefixo é obrigatório',
            'pattern.required' => 'O padrão é obrigatório',
            'reset_frequency.required' => 'A frequência de reset é obrigatória',
        ]);

        $this->sequenceService->updateSequence(
            $sequence->code,
            $request->input('description'),
            $request->input('prefix'),
            $request->input('pattern'),
            $request->input('reset_frequency'),
        );

        return redirect()->route('settings.sequences.show', $sequence)
            ->with('success', 'Sequência atualizada com sucesso!');
    }

    public function reset(DocumentSequence $sequence): \Illuminate\Http\RedirectResponse
    {
        $this->sequenceService->resetSequence($sequence->code);

        return redirect()->route('settings.sequences.show', $sequence)
            ->with('success', 'Sequência resetada para 1');
    }
}
