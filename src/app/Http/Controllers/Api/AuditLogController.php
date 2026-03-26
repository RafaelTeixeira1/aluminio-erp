<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'period_from' => ['nullable', 'date'],
            'period_to' => ['nullable', 'date', 'after_or_equal:period_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $logs = AuditLog::query()
            ->with('user:id,name,profile')
            ->when(!empty($data['action']), fn ($q) => $q->where('action', (string) $data['action']))
            ->when(!empty($data['entity_type']), fn ($q) => $q->where('entity_type', (string) $data['entity_type']))
            ->when(!empty($data['entity_id']), fn ($q) => $q->where('entity_id', (int) $data['entity_id']))
            ->when(!empty($data['user_id']), fn ($q) => $q->where('user_id', (int) $data['user_id']))
            ->when(!empty($data['period_from']) && !empty($data['period_to']), function ($q) use ($data) {
                $q->whereBetween('occurred_at', [
                    $data['period_from'].' 00:00:00',
                    $data['period_to'].' 23:59:59',
                ]);
            })
            ->orderByDesc('occurred_at')
            ->paginate($perPage);

        return response()->json($logs);
    }
}
