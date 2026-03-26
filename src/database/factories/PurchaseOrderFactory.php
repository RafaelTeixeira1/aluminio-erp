<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);

        return [
            'supplier_id' => Supplier::factory(),
            'created_by_user_id' => User::factory(),
            'order_number' => $this->faker->unique()->numberBetween(1, 99999),
            'status' => $this->faker->randomElement(['aberto', 'recebido', 'cancelado']),
            'ordered_at' => $this->faker->dateTime(),
            'expected_delivery_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'payment_due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'received_at' => null,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'notes' => $this->faker->sentence(),
        ];
    }
}
