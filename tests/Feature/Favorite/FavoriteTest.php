<?php

namespace Tests\Feature\Favorite;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_can_favorite_a_property(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($tenant)->post("/tenant/properties/{$property->id}/favorite");

        $response->assertRedirect();
        $this->assertDatabaseHas('favorites', ['user_id' => $tenant->id, 'property_id' => $property->id]);
    }

    public function test_favoriting_the_same_property_twice_toggles_it_off_instead_of_duplicating(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        $this->actingAs($tenant)->post("/tenant/properties/{$property->id}/favorite");
        $this->assertDatabaseCount('favorites', 1);

        $this->actingAs($tenant)->post("/tenant/properties/{$property->id}/favorite");
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_unique_constraint_prevents_duplicate_favorite_rows_at_the_database_level(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        \App\Models\Favorite::create(['user_id' => $tenant->id, 'property_id' => $property->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\Favorite::create(['user_id' => $tenant->id, 'property_id' => $property->id]);
    }
}
