<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receivable>
 */
class ReceivableFactory extends Factory
{
    protected $model = Receivable::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);

        return [
            'sale_id' => Sale::factory(),
            'client_id' => Client::factory(),
            'installment_number' => 1,
            'installment_count' => 1,
            'created_by_user_id' => User::factory(),
            'settled_by_user_id' => null,
            'status' => 'aberto',
            'amount_total' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'due_date' => $this->faker->dateTime(),
            'settled_at' => null,
            'notes' => $this->faker->sentence(),
        ];
    }
}
