<?php

namespace Tests\Feature\Subscription;

use App\Models\Property;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlansTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithProperties(int $count): User
    {
        $owner = User::factory()->owner()->create();
        Property::factory()->count($count)->create(['owner_id' => $owner->id]);

        return $owner;
    }

    public function test_recommendation_picks_the_smallest_plan_that_covers_the_properties(): void
    {
        $svc = app(SubscriptionService::class);

        $this->assertSame('free', $svc->recommend(1));
        $this->assertSame('free', $svc->recommend(2));
        $this->assertSame('pro', $svc->recommend(3));
        $this->assertSame('pro', $svc->recommend(6));
        $this->assertSame('pro_plus', $svc->recommend(7));
        $this->assertSame('pro_plus', $svc->recommend(13));
        $this->assertSame('vip', $svc->recommend(14));
        $this->assertSame('vip', $svc->recommend(25));
    }

    public function test_maybe_later_hides_the_popup_until_the_next_login(): void
    {
        $owner = $this->ownerWithProperties(4);

        $this->actingAs($owner)->get('/owner/properties')->assertOk()->assertSee('Choose your plan');

        $this->actingAs($owner)->post('/owner/subscription/dismiss')->assertNoContent();

        $this->actingAs($owner)->get('/owner/properties')->assertOk()->assertDontSee('Choose your plan');

        // Logging out ends the session, so the popup is offered again after the next login.
        $this->actingAs($owner)->post('/logout');
        $this->flushSession();

        $this->actingAs($owner)->get('/owner/properties')->assertOk()->assertSee('Choose your plan');
    }

    public function test_popup_appears_until_an_owner_picks_a_plan(): void
    {
        $owner = $this->ownerWithProperties(4);

        $this->actingAs($owner)->get('/owner/properties')->assertOk()->assertSee('Choose your plan');

        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'free'])->assertRedirect();

        $this->actingAs($owner)->get('/owner/properties')->assertOk()->assertDontSee('Choose your plan');
    }

    public function test_properties_stay_visible_until_the_owner_chooses(): void
    {
        $owner = $this->ownerWithProperties(5);

        $this->assertSame(0, Property::where('owner_id', $owner->id)->where('subscription_hidden', true)->count());
        $this->assertNull(app(SubscriptionService::class)->visibleLimit($owner));
    }

    public function test_choosing_free_keeps_only_the_two_oldest_properties_visible(): void
    {
        $owner = $this->ownerWithProperties(5);
        $oldest = Property::where('owner_id', $owner->id)->orderBy('id')->take(2)->pluck('id');

        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'free'])->assertRedirect();

        $visible = Property::where('owner_id', $owner->id)->where('subscription_hidden', false)->orderBy('id')->pluck('id');
        $this->assertEquals($oldest->all(), $visible->all());
        $this->assertSame(3, Property::where('owner_id', $owner->id)->where('subscription_hidden', true)->count());
    }

    public function test_hidden_properties_are_not_listed_and_their_page_is_not_found_for_tenants(): void
    {
        $owner = $this->ownerWithProperties(3);
        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'free']);

        $hidden = Property::where('owner_id', $owner->id)->where('subscription_hidden', true)->firstOrFail();
        $shown = Property::where('owner_id', $owner->id)->where('subscription_hidden', false)->firstOrFail();

        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->get('/properties')->assertSee($shown->name)->assertDontSee($hidden->name);
        $this->actingAs($tenant)->get('/properties/'.$hidden->slug)->assertNotFound();
        $this->actingAs($owner)->get('/properties/'.$hidden->slug)->assertOk();
    }

    public function test_paid_plan_activates_after_payment_and_shows_every_property(): void
    {
        $owner = $this->ownerWithProperties(5);
        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'free']);

        $response = $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'pro']);
        $subscription = $owner->subscriptions()->where('plan', 'pro')->firstOrFail();

        // Nothing changes until payment is confirmed.
        $response->assertRedirect(route('owner.subscription.demo', $subscription));
        $this->assertSame('pending', $subscription->status);
        $this->assertSame('free', app(SubscriptionService::class)->currentPlanKey($owner));

        $this->actingAs($owner)->post(route('owner.subscription.demo.confirm', $subscription))->assertRedirect();

        $this->assertSame('pro', app(SubscriptionService::class)->currentPlanKey($owner));
        $this->assertSame(0, Property::where('owner_id', $owner->id)->where('subscription_hidden', true)->count());
    }

    public function test_paid_owner_listings_rank_above_newer_free_listings(): void
    {
        $paid = User::factory()->owner()->create();
        $free = User::factory()->owner()->create();
        $older = Property::factory()->create(['owner_id' => $paid->id, 'name' => 'Boosted Older Listing', 'created_at' => now()->subDays(5)]);
        Property::factory()->create(['owner_id' => $free->id, 'name' => 'Plain Newer Listing', 'created_at' => now()]);

        $this->actingAs($paid)->post('/owner/subscription', ['plan' => 'pro']);
        $sub = $paid->subscriptions()->where('plan', 'pro')->firstOrFail();
        $this->actingAs($paid)->post(route('owner.subscription.demo.confirm', $sub));

        $this->get('/properties')->assertSeeInOrder(['Boosted Older Listing', 'Plain Newer Listing']);
        $this->assertTrue($older->fresh()->owner->subscriptions()->active()->where('plan', 'pro')->exists());
    }

    public function test_expired_paid_plan_falls_back_to_free_and_hides_extra_listings(): void
    {
        $owner = $this->ownerWithProperties(4);
        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'pro']);
        $sub = $owner->subscriptions()->where('plan', 'pro')->firstOrFail();
        $this->actingAs($owner)->post(route('owner.subscription.demo.confirm', $sub));

        $sub->refresh()->update(['ends_at' => now()->subMinute()]);

        $this->assertSame(1, app(SubscriptionService::class)->expireDue());
        $this->assertSame('free', app(SubscriptionService::class)->currentPlanKey($owner));
        $this->assertSame(2, Property::where('owner_id', $owner->id)->where('subscription_hidden', true)->count());
    }

    public function test_an_active_paid_plan_cannot_be_dropped_to_a_lower_plan_early(): void
    {
        $owner = $this->ownerWithProperties(2);
        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'pro_plus']);
        $sub = $owner->subscriptions()->where('plan', 'pro_plus')->firstOrFail();
        $this->actingAs($owner)->post(route('owner.subscription.demo.confirm', $sub));

        $this->actingAs($owner)->post('/owner/subscription', ['plan' => 'free'])->assertRedirect(route('owner.subscription.index'));

        $this->assertSame('pro_plus', app(SubscriptionService::class)->currentPlanKey($owner));
    }

    public function test_tenants_cannot_open_the_owner_subscription_page(): void
    {
        $tenant = User::factory()->tenant()->create();

        $this->actingAs($tenant)->get('/owner/subscription')->assertForbidden();
    }
}
