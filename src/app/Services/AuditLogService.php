<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $payload = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $payload,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
