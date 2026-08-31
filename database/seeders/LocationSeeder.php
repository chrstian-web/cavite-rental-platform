<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    // Municipalities/cities from the thesis brief. Stored as data, not hard-coded
    // anywhere else in the app, so this list can be edited without touching code.
    public function run(): void
    {
        $cities = [
            'Dasmariñas', 'General Trias', 'Imus', 'Bacoor', 'Kawit', 'Tanza',
            'Trece Martires', 'Silang', 'Carmona', 'Tagaytay', 'Naic', 'Rosario',
            'General Mariano Alvarez', 'Magallanes', 'Maragondon', 'Amadeo',
            'Mendez', 'Alfonso', 'Indang', 'Gen. Emilio Aguinaldo',
        ];

        foreach ($cities as $city) {
            Location::updateOrCreate(
                ['province' => 'Cavite', 'city_municipality' => $city],
                ['is_active' => true]
            );
        }
    }
}
