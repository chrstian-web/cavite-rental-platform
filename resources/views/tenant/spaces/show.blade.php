@extends('layouts.app')

@section('title', $space->space_number.' — '.$property->name)

@section('content')
    @php
        $photos = $space->images->map(fn ($img) => asset('storage/'.$img->path))->values();
        $typeLabel = $space->space_type ? str($space->space_type)->replace('_', ' ')->title() : null;

        $attrs = $space->attributes ?? [];
        $genderLabels = ['male' => 'Male only', 'female' => 'Female only', 'mixed' => 'Mixed'];
        $gender = $genderLabels[$attrs['gender_restriction'] ?? ''] ?? null;

        $curfew = null;
        if (! empty($attrs['curfew_time'])) {
            try { $curfew = \Illuminate\Support\Carbon::createFromFormat('H:i', substr($attrs['curfew_time'], 0, 5))->format('g:i A'); }
            catch (\Throwable $e) { $curfew = $attrs['curfew_time']; }
        }

        $utilityLabels = ['water' => 'Water', 'electricity' => 'Electricity', 'wifi' => 'Wifi', 'cable' => 'Cable TV'];
        $utilities = collect($space->utilities_included ?? [])->map(fn ($u) => $utilityLabels[$u] ?? str($u)->title())->values();

        $left = $space->spotsLeft();
    @endphp

    <a href="{{ route('properties.show', $property->slug) }}" class="back-link">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        <span>{{ $property->name }}</span>
    </a>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-4">
        {{-- ───────── Left: room details ───────── --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Gallery --}}
            <div x-data="{ photos: @js($photos), active: 0 }">
                <div class="aspect-video bg-slate-100 rounded-xl overflow-hidden grid place-items-center text-slate-400">
                    <template x-if="photos.length">
                        <img :src="photos[active]" alt="{{ $space->space_number }}" class="w-full h-full object-cover">
                    </template>
                    <span x-show="!photos.length">No photos uploaded for this unit yet</span>
                </div>
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-2" x-show="photos.length > 1" x-cloak>
                    <template x-for="(src, i) in photos" :key="i">
                        <button type="button" @click="active = i"
                                class="aspect-square rounded-lg overflow-hidden border-2"
                                :class="active === i ? 'border-rose-500' : 'border-transparent'">
                            <img :src="src" alt="" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Title --}}
            <div>
                <span class="text-xs uppercase tracking-wide text-blue-600 font-medium">
                    {{ $property->name }}@if ($typeLabel) &middot; {{ $typeLabel }}@endif
                </span>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $space->space_number }}</h1>
                <p class="text-slate-500">{{ collect([$property->address_line, $property->barangay?->name, $property->location?->city_municipality])->filter()->implode(', ') }}, Cavite</p>
            </div>

            {{-- Key facts --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ([
                    ['Bedrooms', $space->bedrooms],
                    ['Bathrooms', $space->bathrooms],
                    ['Capacity', $space->total_capacity.' '.($space->total_capacity > 1 ? 'persons' : 'person')],
                    ['Floor area', $space->floor_area_sqm ? rtrim(rtrim(number_format($space->floor_area_sqm, 2), '0'), '.').' sqm' : '—'],
                ] as [$label, $value])
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <p class="text-xs text-slate-500">{{ $label }}</p>
                        <p class="text-lg font-bold text-slate-900 mt-0.5">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Details list --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="font-semibold text-slate-900 mb-3">Room details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Furnishing</dt><dd class="text-slate-900">{{ $space->is_furnished ? 'Furnished' : 'Unfurnished' }}</dd></div>
                    @if ($space->total_capacity > 1)
                        <div class="flex justify-between"><dt class="text-slate-500">Beds / slots left</dt><dd class="text-slate-900">{{ $left }} of {{ $space->total_capacity }}</dd></div>
                    @endif
                    @if (! empty($attrs['floor']))
                        <div class="flex justify-between"><dt class="text-slate-500">Floor</dt><dd class="text-slate-900">{{ $attrs['floor'] }}</dd></div>
                    @endif
                    @if (! empty($attrs['room_type']))
                        <div class="flex justify-between"><dt class="text-slate-500">Room type</dt><dd class="text-slate-900">{{ $attrs['room_type'] }}</dd></div>
                    @endif
                    @if ($gender)
                        <div class="flex justify-between"><dt class="text-slate-500">Gender restriction</dt><dd class="text-slate-900">{{ $gender }}</dd></div>
                    @endif
                    @if ($curfew)
                        <div class="flex justify-between"><dt class="text-slate-500">Curfew</dt><dd class="text-slate-900">{{ $curfew }}</dd></div>
                    @endif
                </dl>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-2">Utilities included</p>
                    @if ($utilities->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($utilities as $utility)
                                <span class="text-xs bg-emerald-50 text-emerald-700 rounded-full px-3 py-1">{{ $utility }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">None — utilities are paid separately.</p>
                    @endif
                </div>
            </div>

            {{-- About the property --}}
            @if ($property->description)
                <div>
                    <h2 class="font-semibold text-slate-900 mb-2">About {{ $property->name }}</h2>
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ $property->description }}</p>
                </div>
            @endif

            @if (! empty($property->house_rules))
                <div>
                    <h2 class="font-semibold text-slate-900 mb-2">House rules</h2>
                    <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                        @foreach ($property->house_rules as $rule)<li>{{ $rule }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- ───────── Right: price, apply, viewing, map ───────── --}}
        <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <p class="text-sm text-slate-500">Monthly rent</p>
                <p class="text-2xl font-bold text-slate-900">₱{{ number_format($space->monthly_rent) }}</p>

                <dl class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Security deposit</dt><dd class="text-slate-900">₱{{ number_format($space->security_deposit ?? 0) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Advance payment</dt><dd class="text-slate-900">₱{{ number_format($space->advance_payment ?? 0) }}</dd></div>
                </dl>

                <a href="{{ route('tenant.applications.create', $space) }}"
                   class="mt-5 block text-center bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg py-2.5">
                    Continue to application
                </a>
                <p class="text-xs text-slate-400 text-center mt-2">Not sure yet? Request a viewing below first.</p>
            </div>

            {{-- Request a viewing --}}
            <section class="bg-white border border-slate-200 rounded-xl p-5" aria-labelledby="request-viewing-heading">
                <h2 id="request-viewing-heading" class="font-semibold text-slate-900">Request a Viewing</h2>
                <p class="text-xs text-slate-500 mt-1 mb-4">Visit {{ $space->space_number }} in person. The owner will confirm or suggest another time.</p>

                <form method="POST" action="{{ route('tenant.viewings.store', $property) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="rental_space_id" value="{{ $space->id }}">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="viewing-date">Preferred date</label>
                            <input id="viewing-date" type="date" name="preferred_date" required min="{{ now()->toDateString() }}"
                                   value="{{ old('preferred_date') }}"
                                   class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1" for="viewing-time">Preferred time</label>
                            <input id="viewing-time" type="time" name="preferred_time" required
                                   value="{{ old('preferred_time') }}"
                                   class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="viewing-message">Message (optional)</label>
                        <textarea id="viewing-message" name="message" rows="2" maxlength="1000"
                                  class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-sm font-medium rounded-lg py-2.5">
                        Send viewing request
                    </button>
                </form>
            </section>

            @include('partials.property-map', ['property' => $property])
        </div>
    </div>
@endsection
