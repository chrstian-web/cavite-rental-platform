<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'province' => 'Cavite',
            'city_municipality' => $this->faker->unique()->city(),
            'is_active' => true,
        ];
    }
}
