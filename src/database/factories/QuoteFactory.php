<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $total = $this->faker->randomFloat(2, 100, 5000);
        $discount = $this->faker->randomFloat(2, 0, $total * 0.2);

        return [
            'client_id' => Client::factory(),
            'created_by_user_id' => User::factory(),
            'quote_number' => $this->faker->unique()->numberBetween(1, 99999),
            'status' => $this->faker->randomElement(['aberto', 'convertido', 'expirado', 'rejeitado']),
            'valid_until' => $this->faker->dateTimeBetween('now', '+30 days'),
            'subtotal' => $total,
            'total' => $total,
            'discount' => $discount,
            'notes' => $this->faker->sentence(),
        ];
    }
}
