<?php

namespace Database\Factories;

use App\Models\Factura;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Factura>
 */
class FacturaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Factura::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'user_id' => User::factory(),
            'total' => $this->faker->randomFloat(2, 10, 1000),
            'anulada' => false,
        ];
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'anulada',
            'motivo_anulacion' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the invoice is from a specific client.
     */
    public function forClient(Cliente $cliente): static
    {
        return $this->state(fn (array $attributes) => [
            'cliente_id' => $cliente->id,
        ]);
    }

    /**
     * Indicate that the invoice is from a specific user.
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
