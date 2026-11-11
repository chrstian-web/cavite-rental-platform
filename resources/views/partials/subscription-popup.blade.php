{{--
    Plan offer shown automatically to an owner who has not chosen a plan yet:
    new owners right after registering, and existing owners who already have properties.
    "Maybe later" hides it for the rest of this login session (stored server-side, so a
    new tab won't bring it back); it returns after the owner logs out and back in, until a plan
    (Free included) is chosen.
--}}
@php
    $subs = app(\App\Services\SubscriptionService::class);
    $spUser = auth()->user();
    $spCount = $subs->propertyCount($spUser);
    $spPlans = $subs->plans();
    $spRecommended = $spCount > 0 ? $subs->recommend($spCount) : null;
@endphp

<div x-data="{
         open: true, confirmFree: false, confirmedFree: false,
         dismiss() {
             this.open = false;
             try { fetch('{{ route('owner.subscription.dismiss') }}', { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } }); } catch (e) {}
         }
     }"
     x-show="open" x-cloak
     x-on:keydown.escape.window="dismiss()"
     class="fixed inset-0 z-[9990] flex items-start justify-center overflow-y-auto p-4 sm:p-6"
     style="background:rgba(2,6,23,.6)" role="dialog" aria-modal="true" aria-labelledby="sub-popup-title">
    <div class="my-auto w-full max-w-5xl rounded-3xl bg-white p-5 shadow-2xl sm:p-8">
        <div x-show="! confirmFree">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="sub-popup-title" class="text-2xl font-bold text-slate-900">Choose your plan</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if ($spCount > 0)
                            You have {{ $spCount }} {{ \Illuminate\Support\Str::plural('property', $spCount) }}. We marked the plan that keeps {{ $spCount === 1 ? 'it' : 'all of them' }} visible to tenants.
                        @else
                            Pick a plan to start listing. You can change it later.
                        @endif
                    </p>
                </div>
                <button type="button" class="shrink-0 rounded-full border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
                        x-on:click="dismiss()">
                    Maybe later
                </button>
            </div>

            <div class="mt-6">
                @include('partials.subscription-plans', [
                    'plans' => $spPlans, 'currentKey' => 'free', 'recommendedKey' => $spRecommended,
                    'propertyCount' => $spCount, 'chosen' => false,
                ])
            </div>

            <p class="mt-5 text-center text-xs text-slate-500">Paid plans boost your listings to the top of tenant search results.</p>
        </div>

        {{-- Shown only when picking Free would hide listings. --}}
        <div x-show="confirmFree" class="mx-auto max-w-lg py-4 text-center">
            <h2 class="text-xl font-bold text-slate-900">Continue with the Free plan?</h2>
            <p class="mt-3 text-sm text-slate-600">
                Only {{ $spPlans['free']['max_properties'] }} of your {{ $spCount }} properties will stay visible to tenants.
                The other {{ max(0, $spCount - $spPlans['free']['max_properties']) }} will be hidden until you upgrade.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if ($spRecommended && $spRecommended !== 'free')
                    <form method="POST" action="{{ route('owner.subscription.store') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $spRecommended }}">
                        <button type="submit" class="rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                            Choose {{ $spPlans[$spRecommended]['name'] }} (₱{{ number_format($spPlans[$spRecommended]['price']) }}/mo)
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('owner.subscription.store') }}">
                    @csrf
                    <input type="hidden" name="plan" value="free">
                    <button type="submit" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">Continue with Free</button>
                </form>
            </div>
            <button type="button" class="mt-4 text-sm text-slate-500 underline" x-on:click="confirmFree = false">Back to plans</button>
        </div>
    </div>
</div>
