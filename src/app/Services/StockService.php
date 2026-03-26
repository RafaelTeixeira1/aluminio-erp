<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\StockMovement;
use DomainException;

class StockService
{
    public function entry(
        CatalogItem $item,
        float $quantity,
        ?int $userId,
        ?string $notes = null,
        ?string $originType = null,
        ?int $originId = null,
    ): StockMovement {
        return $this->applyDelta($item, abs($quantity), 'entrada', $userId, $notes, $originType, $originId);
    }

    public function manualOutput(
        CatalogItem $item,
        float $quantity,
        ?int $userId,
        ?string $notes = null,
    ): StockMovement {
        return $this->applyDelta($item, -abs($quantity), 'saida', $userId, $notes, 'manual', null);
    }

    public function outputForSale(
        CatalogItem $item,
        float $quantity,
        ?int $userId,
        int $saleId,
    ): StockMovement {
        return $this->applyDelta($item, -abs($quantity), 'saida', $userId, 'Baixa por venda', 'venda', $saleId);
    }

    public function adjust(
        CatalogItem $item,
        float $newStock,
        ?int $userId,
        ?string $notes = null,
    ): StockMovement {
        $delta = $newStock - (float) $item->stock;

        return $this->applyDelta($item, $delta, 'ajuste', $userId, $notes, 'manual', null);
    }

    private function applyDelta(
        CatalogItem $item,
        float $delta,
        string $movementType,
        ?int $userId,
        ?string $notes,
        ?string $originType,
        ?int $originId,
    ): StockMovement {
        $item->refresh();

        $stockBefore = (float) $item->stock;
        $stockAfter = $stockBefore + $delta;

        if ($stockAfter < 0) {
            throw new DomainException('Estoque insuficiente. Operacao cancelada.');
        }

        $item->stock = $stockAfter;
        $item->save();

        return StockMovement::create([
            'catalog_item_id' => $item->id,
            'user_id' => $userId,
            'movement_type' => $movementType,
            'origin_type' => $originType,
            'origin_id' => $originId,
            'quantity' => abs($delta),
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
