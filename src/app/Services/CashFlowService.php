<?php

namespace App\Services;

use App\Models\CashEntry;
use Carbon\Carbon;

class CashFlowService
{
    public function registerEntry(
        string $type,
        float $amount,
        string $description,
        ?string $originType = null,
        ?int $originId = null,
        ?int $userId = null,
        ?Carbon $occurredAt = null,
        ?string $notes = null,
    ): CashEntry {
        return CashEntry::query()->create([
            'type' => $type,
            'origin_type' => $originType,
            'origin_id' => $originId,
            'description' => $description,
            'amount' => $amount,
            'occurred_at' => ($occurredAt ?? now()),
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }
}
