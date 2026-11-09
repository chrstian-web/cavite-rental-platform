<?php

namespace Tests\Feature\Ui;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Standalone action links (Back, Cancel, View ...) should be real buttons in the app's
 * "soft" style (rt-soft), not plain underlined text.
 */
class ActionButtonStylingTest extends TestCase
{
    use RefreshDatabase;

    private function assertSoftButton(string $html, string $text): void
    {
        $this->assertMatchesRegularExpression(
            '/class="[^"]*\brt-soft\b[^"]*"[^>]*>\s*'.$text.'\s*</u',
            $html,
            "\"{$text}\" should be styled as an rt-soft button."
        );
    }

    public function test_admin_user_pages_use_styled_buttons_for_back_cancel_and_deleted_users(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->tenant()->create();

        $index = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();
        $this->assertSoftButton($index, 'Deleted users &rarr;');

        $deleted = $this->actingAs($admin)->get(route('admin.users.deleted'))->assertOk()->getContent();
        $this->assertSoftButton($deleted, '&larr; Back to users');

        $password = $this->actingAs($admin)->get(route('admin.users.password.edit', $target))->assertOk()->getContent();
        $this->assertSoftButton($password, '&larr; Back to users');
        $this->assertSoftButton($password, 'Cancel');
    }

    public function test_tenant_contract_row_action_matches_the_owner_manage_button(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);
        RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 2500, 'start_date' => now(),
            'end_date' => now()->addYear(), 'status' => 'active',
        ]);

        $html = $this->actingAs($tenant)->get(route('tenant.contracts.index'))->assertOk()->getContent();

        $this->assertSoftButton($html, 'View');
        $this->assertStringNotContainsString('text-blue-600 hover:underline">View<', $html);
    }

    public function test_favorites_remove_button_is_a_styled_red_soft_button(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();
        $tenant->favorites()->create(['property_id' => $property->id]);

        $html = $this->actingAs($tenant)->get(route('tenant.favorites.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="[^"]*\brt-soft-red\b[^"]*">Remove from favorites</', $html);
    }
}
