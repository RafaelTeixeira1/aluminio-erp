<?php

namespace Database\Factories;

use App\Models\Payable;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payable>
 */
class PayableFactory extends Factory
{
    protected $model = Payable::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);

        return [
            'vendor_name' => $this->faker->company(),
            'supplier_id' => Supplier::factory(),
            'purchase_order_id' => null,
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['materia-prima', 'insumos', 'servicos', 'outros']),
            'document_number' => $this->faker->bothify('###-###-###'),
            'created_by_user_id' => User::factory(),
            'settled_by_user_id' => null,
            'status' => 'aberto',
            'amount_total' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'due_date' => $this->faker->dateTime(),
            'paid_at' => null,
            'notes' => $this->faker->sentence(),
        ];
    }
}
