<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearbyLocationPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_renters_are_prompted_once_per_account(): void
    {
        $renter = User::factory()->tenant()->create([
            'created_at' => now()->subYear(),
        ]);

        $this->actingAs($renter)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('showNearbyPrompt', true)
            ->assertSee('nearby-location-prompt');

        $this->assertNotNull($renter->fresh()->nearby_prompted_at);

        $this->actingAs($renter)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('showNearbyPrompt', false)
            ->assertDontSee('nearby-location-prompt');
    }

    public function test_new_renters_see_a_centered_location_prompt_on_their_dashboard(): void
    {
        $renter = User::factory()->tenant()->create();

        $this->actingAs($renter)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('showNearbyPrompt', true)
            ->assertSee('role="dialog"', false)
            ->assertSee('Share location &amp; find nearby homes', false)
            ->assertSee(route('properties.nearby'), false);
    }
}
