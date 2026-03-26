<?php

namespace Database\Factories;

use App\Models\CashEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashEntry>
 */
class CashEntryFactory extends Factory
{
    protected $model = CashEntry::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['entrada', 'saida']),
            'origin_type' => $this->faker->randomElement(['sale', 'payable', 'receivable', 'manual', null]),
            'origin_id' => null,
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 50, 2000),
            'occurred_at' => $this->faker->dateTime(),
            'user_id' => User::factory(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
