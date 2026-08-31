<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $name = $this->faker->company().' '.$this->faker->randomElement(['Residences', 'Suites', 'Homes']);

        return [
            'owner_id' => User::factory()->owner(),
            'location_id' => Location::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'property_type' => $this->faker->randomElement(['condominium', 'boarding_house', 'dormitory']),
            'description' => $this->faker->paragraph(),
            'address_line' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(14.0, 14.5),
            'longitude' => $this->faker->longitude(120.8, 121.1),
            'min_monthly_rent' => 5000,
            'max_monthly_rent' => 10000,
            'availability_status' => 'available',
            'verification_status' => 'verified',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['verification_status' => 'pending']);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['availability_status' => 'unavailable']);
    }
}
