<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use App\Support\DocumentViewerItems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProofViewerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: Payment} */
    private function payment(?string $proofPath = 'payment-proofs/proof.png', ?string $originalName = 'proof.png', string $status = 'paid'): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);
        $contract = RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 2500, 'start_date' => now(),
            'end_date' => now()->addYear(), 'status' => 'active',
        ]);
        $payment = $contract->payments()->create([
            'user_id' => $tenant->id, 'payment_type' => 'monthly_rent', 'amount' => 2500,
            'due_date' => today(), 'status' => $status, 'reference_number' => 'GC-12345',
            'proof_path' => $proofPath, 'proof_original_filename' => $originalName,
        ]);

        return [$owner, $tenant, $payment];
    }

    public function test_tenant_proof_button_opens_the_in_page_viewer_instead_of_navigating(): void
    {
        [, $tenant, $payment] = $this->payment();

        $this->actingAs($tenant)
            ->get(route('tenant.payments.show', $payment))
            ->assertOk()
            ->assertSee('View submitted proof of payment')
            ->assertSee("\$dispatch('open-docs'", false)   // click is intercepted and opens the viewer
            ->assertSee('docViewer(', false)               // the viewer is on the page
            ->assertSee('Proof of payment');
    }

    public function test_owner_proof_button_opens_the_in_page_viewer(): void
    {
        [$owner, , $payment] = $this->payment();

        $this->actingAs($owner)
            ->get(route('owner.payments.show', $payment))
            ->assertOk()
            ->assertSee('View submitted proof of payment')
            ->assertSee("\$dispatch('open-docs'", false)
            ->assertSee('docViewer(', false);
    }

    public function test_proof_and_receipt_use_the_app_s_soft_button_style_not_plain_links(): void
    {
        [, $tenant, $payment] = $this->payment();

        $html = $this->actingAs($tenant)->get(route('tenant.payments.show', $payment))->getContent();

        // Check these two specific buttons (the layout has other rt-soft elements, e.g. the notification bell).
        $this->assertMatchesRegularExpression('/class="[^"]*\brt-soft\b[^"]*">View submitted proof of payment</', $html);
        $this->assertMatchesRegularExpression('/class="[^"]*\brt-soft\b[^"]*">Download receipt \(PDF\)</', $html);
        $this->assertStringNotContainsString('text-blue-600 hover:underline">Download receipt', $html);
    }

    public function test_no_viewer_or_proof_button_when_nothing_was_attached(): void
    {
        [$owner, $tenant, $payment] = $this->payment(null, null);

        $this->actingAs($tenant)->get(route('tenant.payments.show', $payment))
            ->assertOk()
            ->assertDontSee('View submitted proof of payment')
            ->assertDontSee('docViewer(', false)
            ->assertSee('Download receipt (PDF)');

        $this->actingAs($owner)->get(route('owner.payments.show', $payment))
            ->assertOk()
            ->assertSee('No proof file was attached to this submission.')
            ->assertDontSee('docViewer(', false);
    }

    public function test_receipt_button_is_hidden_until_the_payment_is_paid(): void
    {
        [, $tenant, $payment] = $this->payment('payment-proofs/proof.png', 'proof.png', 'submitted');

        $this->actingAs($tenant)->get(route('tenant.payments.show', $payment))
            ->assertOk()
            ->assertSee('View submitted proof of payment')
            ->assertDontSee('Download receipt (PDF)');
    }

    public function test_viewer_item_detects_images_and_pdfs_and_links_to_the_authorized_routes(): void
    {
        [, , $png] = $this->payment('payment-proofs/a.png', 'screenshot.PNG');
        [, , $pdf] = $this->payment('payment-proofs/b.pdf', 'receipt.pdf');
        [, , $noName] = $this->payment('payment-proofs/c.jpg', null);

        $item = DocumentViewerItems::fromPayment($png)[0];
        $this->assertSame('image', $item['kind']);
        $this->assertSame(route('payments.proof.view', $png), $item['src']);
        $this->assertSame(route('payments.proof.download', $png), $item['download']);
        $this->assertSame('Ref # GC-12345', $item['meta']);

        $this->assertSame('pdf', DocumentViewerItems::fromPayment($pdf)[0]['kind']);
        // Falls back to the stored path's extension when the original filename is missing.
        $this->assertSame('image', DocumentViewerItems::fromPayment($noName)[0]['kind']);
    }

    public function test_viewer_items_are_empty_without_a_proof_file(): void
    {
        [, , $payment] = $this->payment(null, null);

        $this->assertSame([], DocumentViewerItems::fromPayment($payment));
    }
}
