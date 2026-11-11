<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptions)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('owner.subscription.index', [
            'currentKey' => $this->subscriptions->currentPlanKey($user),
            'active' => $this->subscriptions->activeSubscription($user),
        ]);
    }

    /** "Maybe later": hide the plan popup until the owner logs out and back in. */
    public function dismissPopup(Request $request): \Illuminate\Http\Response
    {
        $request->session()->put('subscription_popup_dismissed', true);

        return response()->noContent();
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys($this->subscriptions->plans()))],
        ]);

        if (! $this->subscriptions->canSwitchTo($user, $data['plan'])) {
            return redirect()->route('owner.subscription.index')
                ->with('status', 'Your current plan is still running. It moves to a lower plan automatically when it ends.');
        }

        if ($data['plan'] === 'free') {
            $this->subscriptions->chooseFree($user);
            $hidden = $user->properties()->where('subscription_hidden', true)->count();

            return redirect()->route('owner.subscription.index')->with('status', $hidden > 0
                ? "You're on the Free plan. Your 2 oldest properties stay visible and {$hidden} "
                    .($hidden === 1 ? 'is' : 'are').' hidden from tenants.'
                : "You're on the Free plan.");
        }

        $url = $this->subscriptions->startCheckout(
            $user,
            $data['plan'],
            route('owner.subscription.index', ['checkout' => 'success']),
            route('owner.subscription.index', ['checkout' => 'cancelled']),
        );

        return redirect()->away($url);
    }

    /** Local demo checkout (fake gateway only). */
    public function demo(Request $request, Subscription $subscription): View
    {
        abort_unless($subscription->user_id === $request->user()->id && $subscription->status === 'pending', 404);

        return view('owner.subscription.demo', [
            'subscription' => $subscription,
            'plan' => $this->subscriptions->plan($subscription->plan),
        ]);
    }

    public function demoConfirm(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 404);

        $this->subscriptions->confirmDemoPayment($subscription);

        return redirect()->route('owner.subscription.index')
            ->with('status', 'Payment recorded (demo). Your plan is now active.');
    }
}
