<?php

namespace Tests\Feature\Onboarding;

use App\Models\Favorite;
use App\Models\Property;
use App\Models\User;
use App\Services\OnboardingChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_renter_sees_the_welcome_popup_and_checklist(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => null]);

        $this->actingAs($tenant)->get('/dashboard')
            ->assertOk()
            ->assertSee('var SHOW_WELCOME = true;', false)
            ->assertSee('Getting started')
            ->assertSee('Add your phone number');
    }

    public function test_new_owner_sees_the_welcome_popup_and_checklist(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()
            ->assertSee('var SHOW_WELCOME = true;', false)
            ->assertSee('Submit your verification documents');
    }

    public function test_welcome_popup_is_not_shown_again_after_it_was_seen(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->post('/onboarding/welcome')->assertNoContent();
        $this->assertNotNull($tenant->fresh()->onboarding_welcomed_at);

        $this->actingAs($tenant)->get('/dashboard')
            ->assertOk()
            ->assertSee('var SHOW_WELCOME = false;', false);
    }

    public function test_finishing_the_tour_is_remembered(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->post('/onboarding/tour-completed')->assertNoContent();

        $fresh = $tenant->fresh();
        $this->assertNotNull($fresh->onboarding_tour_completed_at);
        $this->assertNotNull($fresh->onboarding_welcomed_at);
    }

    public function test_checklist_can_be_hidden_and_comes_back_with_the_tour_link(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->post('/onboarding/checklist/dismiss')->assertNoContent();
        $this->actingAs($tenant)->get('/dashboard')->assertOk()->assertDontSee('Getting started');

        $this->actingAs($tenant)->post('/onboarding/restart')->assertRedirect(route('dashboard', ['tour' => 1]));
        $this->assertNull($tenant->fresh()->onboarding_checklist_dismissed_at);
        $this->actingAs($tenant)->get('/dashboard?tour=1')->assertOk()->assertSee('Getting started');
    }

    public function test_renter_checklist_ticks_off_from_real_data(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => '09171234567']);
        $property = Property::factory()->create();

        $before = app(OnboardingChecklist::class)->for($tenant);
        $this->assertSame(1, $before['done']); // phone only

        Favorite::create(['user_id' => $tenant->id, 'property_id' => $property->id]);

        $after = app(OnboardingChecklist::class)->for($tenant->fresh());
        $this->assertSame(2, $after['done']);
        $this->assertSame('viewing', $after['next']);
        $this->assertFalse($after['complete']);
    }

    public function test_owner_checklist_locks_property_steps_until_verified(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $checklist = app(OnboardingChecklist::class)->for($owner);
        $items = collect($checklist['items'])->keyBy('key');

        $this->assertFalse($items['submit-verification']['done']);
        $this->assertTrue($items['add-property']['locked']);
        $this->assertTrue($items['add-unit']['locked']);
        $this->assertSame('submit-verification', $checklist['next']);
    }

    public function test_super_admin_and_manager_get_no_checklist_or_tour(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $manager = User::factory()->manager()->create();

        $this->assertNull(app(OnboardingChecklist::class)->for($admin));
        $this->assertNull(app(OnboardingChecklist::class)->for($manager));

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee('ob-welcome')->assertDontSee('Take the tour');
    }

    public function test_guests_cannot_use_onboarding_routes(): void
    {
        $this->post('/onboarding/welcome')->assertRedirect('/login');
    }

    public function test_every_checklist_item_has_a_step_by_step_guide(): void
    {
        $tenant = User::factory()->tenant()->create();
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        foreach ([$tenant, $owner] as $user) {
            foreach (app(OnboardingChecklist::class)->for($user)['items'] as $item) {
                $this->assertNotEmpty($item['guide']['intro'], $item['key']);
                $this->assertGreaterThanOrEqual(2, count($item['guide']['steps']), $item['key']);
                $this->assertNotEmpty($item['guide']['cta'], $item['key']);
                $this->assertNotEmpty($item['url'], $item['key']);
            }
        }
    }

    public function test_owner_guide_for_adding_a_property_explains_the_lock_until_verified(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $item = collect(app(OnboardingChecklist::class)->for($owner)['items'])->firstWhere('key', 'add-property');

        $this->assertStringContainsString('unlock', $item['guide']['intro']);
    }

    public function test_dashboard_embeds_the_guides_and_the_what_should_i_do_button(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->get('/dashboard')
            ->assertOk()
            ->assertSee('What should I do?')
            ->assertSee('data-guide-key="favorite"', false)
            ->assertSee('id="ob-guides"', false)
            ->assertSee('Click the empty heart');
    }

    public function test_guide_panel_is_available_on_the_public_home_pages_for_signed_in_renters(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->get('/properties')
            ->assertOk()
            ->assertSee('id="ob-panel"', false);
    }

    public function test_guests_do_not_get_the_guide_panel(): void
    {
        $this->get('/properties')->assertOk()->assertDontSee('id="ob-panel"', false);
    }

    public function test_opening_a_renter_tutorial_ticks_that_step_and_it_stays_ticked(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => '09171234567']);

        $this->actingAs($tenant)->postJson('/onboarding/checklist/seen', ['key' => 'viewing'])->assertNoContent();

        $this->assertArrayHasKey('viewing', $tenant->fresh()->onboarding_steps_seen);

        $checklist = app(OnboardingChecklist::class)->for($tenant->fresh());
        $items = collect($checklist['items'])->keyBy('key');

        $this->assertSame(2, $checklist['done']);        // phone (real data) + the opened tutorial
        $this->assertTrue($items['viewing']['done']);
        $this->assertFalse($items['favorite']['done']);
        $this->assertSame('favorite', $checklist['next']); // first step still to do

        $this->actingAs($tenant)->get('/dashboard')->assertOk()->assertSee('2 of 5 done.');
    }

    public function test_phone_step_is_never_ticked_by_opening_a_tutorial(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => null]);

        $this->actingAs($tenant)->postJson('/onboarding/checklist/seen', ['key' => 'phone'])->assertStatus(422);
        $this->actingAs($tenant)->postJson('/onboarding/checklist/seen', ['key' => 'nonsense'])->assertStatus(422);

        $this->assertSame(0, app(OnboardingChecklist::class)->for($tenant->fresh())['done']);
    }

    public function test_only_renters_can_tick_tutorial_steps(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->postJson('/onboarding/checklist/seen', ['key' => 'favorite'])->assertForbidden();
        $this->post('/onboarding/checklist/seen')->assertRedirect('/login');
    }

    public function test_finishing_every_step_shows_the_appreciation_card_instead_of_the_checklist(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => '09171234567']);

        foreach (['favorite', 'viewing', 'application', 'contract'] as $key) {
            $this->actingAs($tenant)->postJson('/onboarding/checklist/seen', ['key' => $key])->assertNoContent();
        }

        $this->assertTrue(app(OnboardingChecklist::class)->for($tenant->fresh())['complete']);

        $this->actingAs($tenant)->get('/dashboard')
            ->assertOk()
            ->assertSee('id="ob-thanks"', false)
            ->assertSee('Thank you for completing your getting-started guide')
            ->assertDontSee('id="ob-checklist"', false);
    }

    public function test_appreciation_card_can_be_closed_for_good(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => '09171234567']);

        foreach (['favorite', 'viewing', 'application', 'contract'] as $key) {
            $this->actingAs($tenant)->postJson('/onboarding/checklist/seen', ['key' => $key]);
        }

        $this->actingAs($tenant)->post('/onboarding/checklist/dismiss')->assertNoContent();

        $this->actingAs($tenant)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('id="ob-thanks"', false)
            ->assertDontSee('id="ob-checklist"', false);
    }

    public function test_unfinished_checklist_still_shows_the_checklist_and_owners_get_no_hidden_card(): void
    {
        $tenant = User::factory()->tenant()->create(['phone' => '09171234567']);
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        // Renters get the card pre-rendered (hidden) so it can appear the moment they tick the last step.
        $this->actingAs($tenant)->get('/dashboard')->assertOk()->assertSee('id="ob-checklist"', false);

        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertSee('id="ob-checklist"', false)
            ->assertDontSee('id="ob-thanks"', false);
    }
}
