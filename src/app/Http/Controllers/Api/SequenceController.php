<?php

namespace App\Http\Controllers\Api;

use App\Models\DocumentSequence;
use App\Services\SequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    public function __construct(private readonly SequenceService $sequenceService)
    {
    }

    public function index(): JsonResponse
    {
        $sequences = DocumentSequence::query()
            ->where('is_active', true)
            ->get(['id', 'code', 'description', 'prefix', 'pattern', 'next_number', 'reset_frequency']);

        return response()->json([
            'data' => $sequences,
            'meta' => [
                'total' => $sequences->count(),
            ],
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $sequence = DocumentSequence::where('code', $code)->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $sequence->id,
                'code' => $sequence->code,
                'description' => $sequence->description,
                'prefix' => $sequence->prefix,
                'pattern' => $sequence->pattern,
                'next_number' => $sequence->next_number,
                'reset_frequency' => $sequence->reset_frequency,
                'last_reset_at' => $sequence->last_reset_at,
                'is_active' => $sequence->is_active,
            ],
        ]);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'description' => 'sometimes|string|max:160',
            'prefix' => 'sometimes|string|max:20',
            'pattern' => 'sometimes|string|max:60',
            'reset_frequency' => 'sometimes|string|in:never,annual,monthly',
        ]);

        $sequence = $this->sequenceService->updateSequence(
            $code,
            $request->input('description'),
            $request->input('prefix'),
            $request->input('pattern'),
            $request->input('reset_frequency'),
        );

        return response()->json([
            'data' => [
                'id' => $sequence->id,
                'code' => $sequence->code,
                'description' => $sequence->description,
                'prefix' => $sequence->prefix,
                'pattern' => $sequence->pattern,
                'next_number' => $sequence->next_number,
                'reset_frequency' => $sequence->reset_frequency,
            ],
            'message' => 'Sequência atualizada com sucesso',
        ]);
    }

    public function reset(string $code): JsonResponse
    {
        $sequence = $this->sequenceService->resetSequence($code);

        return response()->json([
            'data' => [
                'id' => $sequence->id,
                'code' => $sequence->code,
                'next_number' => $sequence->next_number,
                'last_reset_at' => $sequence->last_reset_at,
            ],
            'message' => 'Sequência resetada para 1',
        ]);
    }

    public function history(string $code, Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $history = $this->sequenceService->getHistory($code, $limit);

        return response()->json([
            'data' => $history->map(fn($log) => [
                'id' => $log->id,
                'generated_number' => $log->generated_number,
                'document_type' => $log->document_type,
                'document_id' => $log->document_id,
                'generated_by' => $log->generatedBy?->name,
                'generated_at' => $log->generated_at,
            ]),
            'meta' => [
                'total' => $history->count(),
                'sequence_code' => $code,
            ],
        ]);
    }
}
