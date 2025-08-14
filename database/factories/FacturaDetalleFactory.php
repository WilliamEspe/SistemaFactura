<?php

namespace Database\Factories;

use App\Models\FacturaDetalle;
use App\Models\Factura;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacturaDetalle>
 */
class FacturaDetalleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = FacturaDetalle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 5);
        $precio_unitario = $this->faker->randomFloat(2, 5, 100);
        
        return [
            'factura_id' => Factura::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $cantidad * $precio_unitario,
        ];
    }

    /**
     * Indicate that the detail is for a specific invoice.
     */
    public function forFactura(Factura $factura): static
    {
        return $this->state(fn (array $attributes) => [
            'factura_id' => $factura->id,
        ]);
    }

    /**
     * Indicate that the detail is for a specific product.
     */
    public function forProducto(Producto $producto): static
    {
        return $this->state(fn (array $attributes) => [
            'producto_id' => $producto->id,
            'precio_unitario' => $producto->precio,
            'subtotal' => $attributes['cantidad'] * $producto->precio,
        ]);
    }
}
