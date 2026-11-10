<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Owner subscription plans: which plan an owner is on, how many of their
 * properties tenants can see, and the search boost that paid plans earn.
 */
class SubscriptionService
{
    public function __construct(protected OnlinePaymentService $online)
    {
    }

    // ── Plan catalogue ───────────────────────────────────────────

    /** @return array<string, array{name:string, price:int, max_properties:int, boost:int}> */
    public function plans(): array
    {
        return config('subscriptions.plans');
    }

    public function plan(string $key): array
    {
        $plans = $this->plans();
        abort_unless(isset($plans[$key]), 404, 'Unknown plan.');

        return $plans[$key];
    }

    /** The cheapest plan whose limit covers $propertyCount; VIP when none does. */
    public function recommend(int $propertyCount): string
    {
        foreach ($this->plans() as $key => $plan) {
            if ($propertyCount <= $plan['max_properties']) {
                return $key;
            }
        }

        return array_key_last($this->plans());
    }

    // ── What an owner is on ──────────────────────────────────────

    public function activeSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()->active()->latest('id')->first();
    }

    /** Plan key in force right now. Owners who have not chosen yet count as Free. */
    public function currentPlanKey(User $user): string
    {
        return $this->activeSubscription($user)?->plan ?? 'free';
    }

    /** True once the owner has made a choice (Free counts). A checkout still awaiting payment does not. */
    public function hasChosen(User $user): bool
    {
        return $user->subscriptions()->where('status', '!=', 'pending')->exists();
    }

    /**
     * How many properties tenants may see, or null for "no limit yet".
     * Owners keep every listing visible until they actually make a choice.
     */
    public function visibleLimit(User $user): ?int
    {
        if (! $this->hasChosen($user)) {
            return null;
        }

        return $this->plan($this->currentPlanKey($user))['max_properties'];
    }

    public function propertyCount(User $user): int
    {
        return Property::where('owner_id', $user->id)->count();
    }

    // ── Choosing a plan ──────────────────────────────────────────

    /**
     * Rule shared by the page and the controller: while a paid plan is running,
     * an owner can renew or move up, but not drop to a lower tier before it ends.
     * (When it ends the owner falls back to Free automatically.)
     */
    public function canSwitchTo(User $user, string $planKey): bool
    {
        $active = $this->activeSubscription($user);

        if (! $active || $active->plan === 'free') {
            return true;
        }

        return $this->plan($planKey)['boost'] >= $this->plan($active->plan)['boost'];
    }

    public function chooseFree(User $user): Subscription
    {
        return DB::transaction(function () use ($user) {
            $this->closeActive($user);

            $subscription = $user->subscriptions()->create([
                'plan' => 'free',
                'amount' => 0,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => null,
            ]);

            $this->syncVisibility($user);

            return $subscription;
        });
    }

    /**
     * Starts payment for a paid plan and returns the URL to send the owner to.
     * Nothing is activated here: the plan only starts once payment is confirmed.
     */
    public function startCheckout(User $user, string $planKey, string $successUrl, string $cancelUrl): string
    {
        $plan = $this->plan($planKey);
        abort_if($plan['price'] <= 0, 422, 'The Free plan does not need payment.');
        abort_unless($this->canSwitchTo($user, $planKey), 422, 'Your current plan is higher. It moves to a lower plan when it ends.');
        abort_if($this->online->isFake() && ! $this->online->fakeAllowed(), 503, 'Online payments are not configured.');

        $user->subscriptions()->where('status', 'pending')->update(['status' => 'cancelled']);

        $subscription = $user->subscriptions()->create([
            'plan' => $planKey,
            'amount' => $plan['price'],
            'status' => 'pending',
            'gateway' => $this->online->gatewayName(),
        ]);

        if ($this->online->isFake()) {
            $subscription->update(['gateway_checkout_id' => 'sub_demo_'.Str::lower(Str::random(16))]);

            return route('owner.subscription.demo', $subscription);
        }

        $secret = (string) config('services.paymongo.secret_key', '');
        abort_if($secret === '', 503, 'PayMongo is not configured.');

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()->asJson()
            ->post('https://api.paymongo.com/v2/checkout_sessions', ['data' => ['attributes' => [
                'line_items' => [[
                    'name' => 'CaviteRentals '.$plan['name'].' plan ('.config('subscriptions.period_days').' days)',
                    'amount' => $plan['price'] * 100,
                    'currency' => 'PHP',
                    'quantity' => 1,
                ]],
                'payment_method_types' => config('services.paymongo.payment_methods', ['card', 'gcash', 'qrph']),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'reference_number' => 'SUB-'.$subscription->id.'-'.Str::upper(Str::random(6)),
                'send_email_receipt' => false,
                'metadata' => ['subscription_id' => (string) $subscription->id],
            ]]])
            ->throw();

        $checkoutId = $response->json('data.id');
        $url = $response->json('data.attributes.checkout_url');

        if (! $checkoutId || ! $url) {
            throw new RuntimeException('PayMongo did not return a checkout URL.');
        }

        $subscription->update(['gateway_checkout_id' => $checkoutId]);

        return $url;
    }

    /** Local demo only: stands in for the gateway confirming payment. */
    public function confirmDemoPayment(Subscription $subscription): Subscription
    {
        abort_unless($this->online->fakeAllowed() && $subscription->gateway === 'fake', 404);

        return $this->activate($subscription);
    }

    /**
     * Called by the PayMongo webhook. Returns true when the checkout belonged to a
     * subscription (so the payment webhook should stop looking for a rental payment).
     */
    public function handlePaidCheckout(string $checkoutId, mixed $paidMinor): bool
    {
        $subscription = Subscription::where('gateway', 'paymongo')
            ->where('gateway_checkout_id', $checkoutId)
            ->first();

        if (! $subscription) {
            return false;
        }

        if ($paidMinor === null || (int) $paidMinor !== $subscription->amount * 100) {
            Log::warning('PayMongo subscription amount missing or mismatched; plan not activated.', [
                'subscription_id' => $subscription->id, 'expected' => $subscription->amount * 100, 'received' => $paidMinor,
            ]);

            return true;
        }

        $this->activate($subscription);

        return true;
    }

    /** Idempotent: a second call for the same subscription changes nothing. */
    public function activate(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $locked = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                return $locked;
            }

            $user = $locked->user;
            $current = $this->activeSubscription($user);

            // Renewing the same plan adds a period on top of the time already paid for.
            $start = $current && $current->plan === $locked->plan && $current->ends_at && $current->ends_at->isFuture()
                ? $current->ends_at
                : now();

            $this->closeActive($user);

            $locked->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $start->copy()->addDays((int) config('subscriptions.period_days', 30)),
            ]);

            $this->syncVisibility($user);

            return $locked->fresh();
        });
    }

    // ── Visibility and expiry ────────────────────────────────────

    /**
     * Shows the owner's oldest properties up to the plan limit and hides the rest.
     * Does nothing for an owner who has not chosen a plan yet.
     */
    public function syncVisibility(User $user): void
    {
        $limit = $this->visibleLimit($user);

        if ($limit === null) {
            return;
        }

        $ids = Property::where('owner_id', $user->id)->orderBy('id')->pluck('id');

        Property::whereIn('id', $ids->take($limit))->where('subscription_hidden', true)->update(['subscription_hidden' => false]);

        if ($ids->count() > $limit) {
            Property::whereIn('id', $ids->slice($limit))->where('subscription_hidden', false)->update(['subscription_hidden' => true]);
        }
    }

    /** Moves owners whose paid period ended back to Free and re-applies their limit. */
    public function expireDue(): int
    {
        $due = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->with('user')
            ->get();

        foreach ($due as $subscription) {
            DB::transaction(function () use ($subscription) {
                $subscription->update(['status' => 'expired']);

                if ($subscription->user && ! $this->activeSubscription($subscription->user)) {
                    $subscription->user->subscriptions()->create([
                        'plan' => 'free', 'amount' => 0, 'status' => 'active', 'starts_at' => now(),
                    ]);
                }

                if ($subscription->user) {
                    $this->syncVisibility($subscription->user);
                }
            });
        }

        return $due->count();
    }

    protected function closeActive(User $user): void
    {
        $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);
    }
}
