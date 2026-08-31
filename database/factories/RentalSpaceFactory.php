<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\RentalSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalSpaceFactory extends Factory
{
    protected $model = RentalSpace::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'space_number' => strtoupper($this->faker->bothify('Unit ##?')),
            'space_type' => $this->faker->randomElement(['Studio', '1-Bedroom', 'Shared']),
            'bedrooms' => 1,
            'bathrooms' => 1,
            'total_capacity' => 2,
            'occupied_capacity' => 0,
            'is_furnished' => true,
            'monthly_rent' => 8000,
            'security_deposit' => 8000,
            'advance_payment' => 8000,
            'status' => 'available',
        ];
    }
}
