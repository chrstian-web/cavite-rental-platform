<?php

namespace App\Services;

use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;

/**
 * "Getting started" checklist shown on the renter and owner dashboards.
 *
 * Owner steps are ticked from real data. For renters only the phone number is required (ticked
 * from real data); every other step is ticked once the renter has opened its tutorial (stored in
 * users.onboarding_steps_seen), or earlier if they have already done the real thing.
 */
class OnboardingChecklist
{
    /** Renter steps that tick when the tutorial is opened. The phone number is deliberately not one. */
    public const TUTORIAL_STEPS = ['favorite', 'viewing', 'application', 'contract'];

    /**
     * @return array{items: array<int, array<string, mixed>>, done: int, total: int, complete: bool, next: ?string}|null
     *         null when the user has no checklist (managers, super admins).
     */
    public function for(User $user): ?array
    {
        $items = match (true) {
            $user->isTenant() => $this->tenantItems($user),
            $user->isOwner() => $this->ownerItems($user),
            default => null,
        };

        if ($items === null) {
            return null;
        }

        $done = collect($items)->where('done', true)->count();
        $next = collect($items)->first(fn ($i) => ! $i['done'])['key'] ?? null;

        return [
            'items' => $items,
            'done' => $done,
            'total' => count($items),
            'complete' => $done === count($items),
            'next' => $next,
        ];
    }

    /** A step-by-step guide shown when the user clicks a checklist item. */
    protected function guide(string $intro, array $steps, string $cta): array
    {
        return ['intro' => $intro, 'steps' => $steps, 'cta' => $cta];
    }

    protected function tenantItems(User $user): array
    {
        $seen = $user->onboarding_steps_seen ?? [];

        return [
            [
                'key' => 'phone',
                'title' => 'Add your phone number',
                'hint' => 'Owners use it to reach you about viewings and applications.',
                'url' => route('profile.edit'),
                'done' => filled($user->phone),
                'guide' => $this->guide(
                    'A phone number lets owners call or text you about your viewings and applications.',
                    [
                        'Open your profile with the button below.',
                        'Find the Phone box under "Account information".',
                        'Type your 11-digit mobile number, for example 09171234567.',
                        'Press Save.',
                    ],
                    'Open my profile'
                ),
            ],
            [
                'key' => 'favorite',
                'markable' => true,
                'title' => 'Save a home you like',
                'hint' => 'Tap the heart on a listing so you can compare homes later.',
                'url' => route('properties.index'),
                'done' => isset($seen['favorite']) || $user->favorites()->exists(),
                'guide' => $this->guide(
                    'Saving a home keeps it in your Favorites, so you can compare homes later.',
                    [
                        'Open the list of homes with the button below.',
                        'Click a home you like to open its page.',
                        'Click the empty heart (♡) next to the price. It turns red (♥) when saved.',
                        'Find all your saved homes under "Favorites" in the top menu.',
                    ],
                    'Browse homes'
                ),
            ],
            [
                'key' => 'viewing',
                'markable' => true,
                'title' => 'Book a viewing',
                'hint' => 'See the place first, in person or through its virtual tour.',
                'url' => route('properties.index'),
                'done' => isset($seen['viewing']) || $user->viewingRequests()->exists(),
                'guide' => $this->guide(
                    'A viewing lets you see the place before you apply. Check the Virtual Tour on the home page if you cannot visit yet.',
                    [
                        'Open the list of homes with the button below and click a home.',
                        'On the home page, click "Request a Viewing" (under the contact details).',
                        'Choose the date and time you prefer. A short message is optional.',
                        'Submit it. The owner will confirm or suggest another time.',
                        'Track it under "My Rentals", then "My Viewings".',
                    ],
                    'Browse homes'
                ),
            ],
            [
                'key' => 'application',
                'markable' => true,
                'title' => 'Apply for a rental',
                'hint' => 'Pick a unit, then send your application and documents.',
                'url' => route('properties.index'),
                'done' => isset($seen['application']) || RentalApplication::where('user_id', $user->id)->exists(),
                'guide' => $this->guide(
                    'You apply for one specific unit or room, so open the home first.',
                    [
                        'Open the list of homes with the button below and click a home.',
                        'Scroll to "Available units/rooms" and click "Apply" on the unit you want.',
                        'Fill in your move-in date, number of occupants, employment and emergency contact.',
                        'Upload your valid IDs. Students upload a student ID and a parent or guardian ID instead.',
                        'Press "Submit application". Track it under "My Rentals", then "My Applications".',
                    ],
                    'Browse homes'
                ),
            ],
            [
                'key' => 'contract',
                'markable' => true,
                'title' => 'Sign your rental contract',
                'hint' => 'Once an owner approves you, the contract shows up here.',
                'url' => route('tenant.contracts.index'),
                'done' => isset($seen['contract']) || RentalContract::where('user_id', $user->id)->exists(),
                'guide' => $this->guide(
                    'You cannot create this yourself. The owner prepares your contract after approving your application.',
                    [
                        'Wait for the owner to approve your application. A red dot on the bell tells you when something changes.',
                        'Open "My Rentals", then "My Contracts", and click "View" on your contract.',
                        'Pay your security deposit and advance payment from the contract page.',
                        'The owner confirms your payment and activates the contract. Then the unit is yours.',
                    ],
                    'Open my contracts'
                ),
            ],
        ];
    }

    protected function ownerItems(User $user): array
    {
        $verified = $user->isOwnerVerified();
        $status = $user->owner_verification_status ?? 'not_submitted';
        $firstProperty = $user->properties()->oldest()->first();

        $reviewHint = match ($status) {
            'needs_review' => 'Under review. We will notify you when it is decided.',
            'needs_additional_documents' => 'An admin asked for more documents. Upload them to continue.',
            'rejected' => 'Not approved. Check the reason and submit again.',
            default => 'An admin checks your documents before you can list properties.',
        };

        return [
            [
                'key' => 'submit-verification',
                'title' => 'Submit your verification documents',
                'hint' => 'Upload your ID and proof of ownership.',
                'url' => route('owner.verification.show'),
                'done' => $status !== 'not_submitted',
                'guide' => $this->guide(
                    'Admins check your documents before you can list properties. You only need to do this once.',
                    [
                        'Open the verification page with the button below.',
                        'Upload each document. A red * means it is required.',
                        'Use PDF, JPG or PNG files, up to 5MB each.',
                        'Press "Submit for Verification".',
                        'Wait for the review. You will get a notification when it is decided.',
                    ],
                    'Open verification'
                ),
            ],
            [
                'key' => 'get-verified',
                'title' => 'Get verified',
                'hint' => $reviewHint,
                'url' => route('owner.verification.show'),
                'done' => $verified,
                'guide' => $this->guide(
                    'This step is done by an admin, not by you. You can only make sure your documents are complete.',
                    [
                        'Open the verification page and check the status at the top.',
                        'If more documents were requested, upload them there.',
                        'If it was not approved, read the reason, fix the problem and submit again.',
                        'Once approved, "My Properties" and the property tools unlock.',
                    ],
                    'Check my status'
                ),
            ],
            [
                'key' => 'add-property',
                'title' => 'Add your first property',
                'hint' => $verified ? 'Add the name, location, photos and price range.' : 'Unlocks once you are verified.',
                'url' => $verified ? route('owner.properties.create') : route('owner.verification.show'),
                'done' => $firstProperty !== null,
                'locked' => ! $verified && $firstProperty === null,
                'guide' => $verified
                    ? $this->guide(
                        'A property is the building or house. You add its rooms or units in the next step.',
                        [
                            'Press "+ Add Property" with the button below.',
                            'Fill in the property name, type, city and street address.',
                            'Enter the minimum and maximum monthly rent, and the contact details.',
                            'Tick the amenities and upload clear photos. Pick one as the cover photo.',
                            'Save. An admin will then review the listing.',
                        ],
                        'Add a property'
                    )
                    : $this->guide(
                        'Property tools unlock after an admin verifies you.',
                        [
                            'Finish "Submit your verification documents" first.',
                            'Wait for the admin to approve you. You will get a notification.',
                            'Then come back here to add your first property.',
                        ],
                        'Go to verification'
                    ),
            ],
            [
                'key' => 'add-unit',
                'title' => 'Add a unit to rent',
                'hint' => $firstProperty ? 'Add rooms or units with their rent and capacity.' : 'Add a property first.',
                'url' => $firstProperty ? route('owner.properties.spaces.create', $firstProperty) : route('owner.properties.index'),
                'done' => RentalSpace::whereHas('property', fn ($q) => $q->where('owner_id', $user->id))->exists(),
                'guide' => $firstProperty
                    ? $this->guide(
                        'Tenants rent a unit or room, so a property with no units cannot be rented.',
                        [
                            'Open the Units page of your property with the button below.',
                            'Press the button to add a unit.',
                            'Enter the unit or room number, type and capacity.',
                            'Enter the monthly rent, security deposit and advance payment.',
                            'Upload photos of this specific unit, then save.',
                        ],
                        'Add a unit'
                    )
                    : $this->guide(
                        'Units belong to a property, so add a property first.',
                        [
                            'Finish "Add your first property".',
                            'Then come back here to add its rooms or units.',
                        ],
                        'See my properties'
                    ),
                'locked' => $firstProperty === null,
            ],
            [
                'key' => 'property-approved',
                'title' => 'Get your property approved',
                'hint' => 'An admin reviews each listing. Approved ones appear in search.',
                'url' => route('owner.properties.index'),
                'done' => Property::where('owner_id', $user->id)->where('verification_status', 'verified')->exists(),
                'guide' => $this->guide(
                    'An admin checks every listing before it appears in search. You cannot speed this up, but you can make it easy.',
                    [
                        'Open "My Properties" with the button below and check your listing.',
                        'Make sure the address, contact details and rent range are correct.',
                        'Make sure the property has clear photos and a cover photo.',
                        'Wait for the admin. You will get a notification when it is verified.',
                    ],
                    'See my properties'
                ),
                'locked' => $firstProperty === null,
            ],
        ];
    }
}
