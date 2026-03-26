<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(3, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);

        return [
            'sale_id' => Sale::factory(),
            'catalog_item_id' => null,
            'item_name' => $this->faker->word(),
            'item_type' => $this->faker->randomElement(['produto', 'acessorio']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
        ];
    }
}
