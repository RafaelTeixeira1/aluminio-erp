<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $total = $this->faker->randomFloat(2, 100, 5000);
        $discount = $this->faker->randomFloat(2, 0, $total * 0.2);

        return [
            'client_id' => Client::factory(),
            'created_by_user_id' => User::factory(),
            'sale_number' => $this->faker->unique()->numberBetween(1, 99999),
            'status' => 'confirmada',
            'subtotal' => $total,
            'total' => $total,
            'discount' => $discount,
        ];
    }
}
