<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            'utility' => ['Wifi', 'Water Refilling Station', 'Backup Generator', 'Elevator'],
            'safety' => ['CCTV', '24/7 Security Guard', 'Fire Extinguisher', 'Gated Entrance'],
            'comfort' => ['Air Conditioning', 'Furnished', 'Balcony', 'Study Desk'],
            'lifestyle' => ['Parking', 'Laundry Area', 'Kitchen Access', 'Rooftop / Common Area'],
        ];

        foreach ($amenities as $category => $names) {
            foreach ($names as $name) {
                Amenity::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'category' => $category]
                );
            }
        }
    }
}
