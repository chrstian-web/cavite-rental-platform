{{--
    The four plan cards, shared by the pop-up and the plans page.
    Variables: $plans, $currentKey, $recommendedKey (nullable), $propertyCount, $chosen (bool)
    Choosing Free while some listings would be hidden asks for confirmation first
    (Alpine state `confirmFree` lives on the wrapper that includes this partial).
--}}
@php
    $freeLimit = $plans['free']['max_properties'];
    $wouldHide = max(0, $propertyCount - $freeLimit);
@endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($plans as $key => $plan)
        @php
            $isRecommended = $recommendedKey === $key;
            $isCurrent = $chosen && $currentKey === $key;
        @endphp
        <article class="flex flex-col rounded-2xl border bg-white p-5 {{ $isRecommended ? 'border-blue-600 ring-2 ring-blue-600' : 'border-slate-200' }}">
            <header class="flex items-center justify-between gap-2 min-h-[28px]">
                <h3 class="font-bold text-slate-900">{{ $plan['name'] }}</h3>
                @if ($isCurrent)
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Your plan</span>
                @elseif ($isRecommended)
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Recommended</span>
                @endif
            </header>

            <p class="mt-3 text-3xl font-extrabold text-slate-900">
                ₱{{ number_format($plan['price']) }}<span class="text-sm font-normal text-slate-500"> / month</span>
            </p>

            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                <li class="flex gap-2"><span class="text-blue-600" aria-hidden="true">✓</span>Up to {{ $plan['max_properties'] }} properties shown to tenants</li>
                @if ($plan['boost'] > 0)
                    <li class="flex gap-2"><span class="text-blue-600" aria-hidden="true">✓</span>Boosted: your listings rank higher in search</li>
                    <li class="flex gap-2"><span class="text-blue-600" aria-hidden="true">✓</span>"Featured" badge on your listings</li>
                @else
                    <li class="flex gap-2 text-slate-400"><span aria-hidden="true">–</span>No search boost</li>
                @endif
            </ul>

            <form method="POST" action="{{ route('owner.subscription.store') }}" class="mt-auto pt-5"
                  @if ($key === 'free' && $wouldHide > 0 && ! $isCurrent)
                      x-on:submit="if (! confirmedFree) { $event.preventDefault(); confirmFree = true }"
                  @endif>
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                @if ($isCurrent && $key === 'free')
                    <button type="button" disabled class="w-full rounded-full border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-default">Current plan</button>
                @elseif ($isCurrent)
                    <button type="submit" class="w-full rounded-full border border-blue-600 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50">Renew {{ $plan['name'] }}</button>
                @else
                    <button type="submit" class="w-full rounded-full px-4 py-2.5 text-sm font-semibold {{ $isRecommended ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border border-slate-300 text-slate-800 hover:bg-slate-50' }}">
                        {{ $key === 'free' ? 'Continue with Free' : 'Choose '.$plan['name'] }}
                    </button>
                @endif
            </form>
        </article>
    @endforeach
</div>

@if ($propertyCount > $plans['vip']['max_properties'])
    <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
        You have {{ $propertyCount }} properties, more than any plan covers. VIP shows {{ $plans['vip']['max_properties'] }} of them; the rest stay hidden from tenants.
    </p>
@endif
