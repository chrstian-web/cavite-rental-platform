@extends('layouts.app')

@section('title', 'Apply — '.$property->name)

@section('content')
    <a href="{{ route('properties.show', $property->slug) }}" class="back-link">
        <svg class="back-link__icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        <span>{{ $property->name }}</span>
    </a>

    <div class="mt-5 mb-8">
        <p class="eyebrow mb-2">Rental application</p>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950">Apply for {{ $space->space_number }}</h1>
        <p class="text-sm text-slate-500 mt-1">It takes about 3 minutes. The owner reviews your application and you are notified of the decision.</p>
    </div>

    @php $image = $space->images->firstWhere('is_cover', true) ?? $space->images->first(); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start"
         x-data="{
            status: @js(old('employment_status', '')),
            get isStudent() { return this.status === 'student'; },
            get needsIds() { return ['employed', 'self_employed', 'unemployed'].includes(this.status); }
         }">

        {{-- ───────── Form ───────── --}}
        <form method="POST" action="{{ route('tenant.applications.store', $space) }}" enctype="multipart/form-data"
              class="lg:col-span-2 space-y-6">
            @csrf

            {{-- 1. Move-in --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="flex items-center gap-3 text-base font-bold text-slate-900 mb-5">
                    <span class="grid place-items-center w-7 h-7 rounded-full bg-rose-600 text-white text-xs font-bold">1</span>
                    Move-in details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Desired move-in date <span class="text-rose-600">*</span></label>
                        <input type="date" name="desired_move_in_date" required min="{{ now()->toDateString() }}"
                               value="{{ old('desired_move_in_date') }}"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Length of stay (months)</label>
                        <input type="number" name="length_of_stay_months" min="1" max="60" value="{{ old('length_of_stay_months') }}"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Number of occupants <span class="text-rose-600">*</span></label>
                        <input type="number" name="number_of_occupants" min="1" max="{{ max(1, $space->total_capacity) }}" required
                               value="{{ old('number_of_occupants', 1) }}"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </section>

            {{-- 2. About you --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="flex items-center gap-3 text-base font-bold text-slate-900 mb-5">
                    <span class="grid place-items-center w-7 h-7 rounded-full bg-rose-600 text-white text-xs font-bold">2</span>
                    About you
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Employment status <span class="text-rose-600">*</span></label>
                        <select name="employment_status" required x-model="status"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select one…</option>
                            <option value="employed">Employed</option>
                            <option value="self_employed">Self-employed</option>
                            <option value="student">Student</option>
                            <option value="unemployed">Unemployed</option>
                        </select>
                        @error('employment_status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Monthly income (₱, optional)</label>
                        <input type="number" step="0.01" min="0" name="monthly_income" value="{{ old('monthly_income') }}"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </section>

            {{-- 3. Emergency contact --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="flex items-center gap-3 text-base font-bold text-slate-900 mb-5">
                    <span class="grid place-items-center w-7 h-7 rounded-full bg-rose-600 text-white text-xs font-bold">3</span>
                    Emergency contact
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" placeholder="e.g. Maria Santos"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mobile number</label>
                        <input type="text" name="emergency_contact_number" value="{{ old('emergency_contact_number') }}"
                               placeholder="09171234567" inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Relationship</label>
                        <input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}" placeholder="e.g. Mother"
                               class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </section>

            {{-- 4. Documents --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="flex items-center gap-3 text-base font-bold text-slate-900 mb-1">
                    <span class="grid place-items-center w-7 h-7 rounded-full bg-rose-600 text-white text-xs font-bold">4</span>
                    Identity documents
                </h2>
                <p class="text-sm text-slate-500 mb-5 ml-10">The documents we ask for depend on your employment status.</p>

                <p x-show="!isStudent && !needsIds" class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-400">
                    Choose your employment status above to see which documents to upload.
                </p>

                {{-- Student --}}
                <div x-show="isStudent" x-cloak>
                    <p class="text-sm text-slate-600 mb-4">As a student, please upload your <strong>student ID</strong> and a <strong>valid ID of each parent or guardian</strong> (2 IDs).</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @include('partials.file-upload', ['key' => 'student_id', 'label' => 'Student ID', 'when' => 'isStudent'])
                        <div class="hidden md:block"></div>
                        @include('partials.file-upload', ['key' => 'parent_id_1', 'label' => "Parent/guardian's valid ID (1 of 2)", 'when' => 'isStudent'])
                        @include('partials.file-upload', ['key' => 'parent_id_2', 'label' => "Parent/guardian's valid ID (2 of 2)", 'when' => 'isStudent'])
                    </div>
                </div>

                {{-- Employed / self-employed / unemployed --}}
                <div x-show="needsIds" x-cloak>
                    <p class="text-sm text-slate-600 mb-4">Please upload <strong>2 valid government-issued IDs</strong> (for example a driver's license, passport, UMID or PhilSys ID).</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @include('partials.file-upload', ['key' => 'valid_id_1', 'label' => 'Valid ID (1 of 2)', 'when' => 'needsIds'])
                        @include('partials.file-upload', ['key' => 'valid_id_2', 'label' => 'Valid ID (2 of 2)', 'when' => 'needsIds'])
                    </div>
                </div>

                <p class="flex items-start gap-2 text-xs text-slate-400 mt-5">
                    <svg class="w-4 h-4 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    Your files are stored privately. Only you, the property owner or manager reviewing your application, and platform administrators can open them.
                </p>
            </section>

            {{-- Notes + submit --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6">
                <label class="block text-sm font-medium text-slate-700 mb-1">Message to the owner (optional)</label>
                <textarea name="notes" rows="3" maxlength="2000" placeholder="Anything the owner should know — pets, work schedule, special requests…"
                          class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-6">
                    <p class="text-xs text-slate-400 max-w-md">By submitting, you confirm the information and documents you provided are accurate.</p>
                    <button type="submit" class="btn-primary btn-tenant application-submit-button">Submit application</button>
                </div>
            </section>
        </form>

        {{-- ───────── Summary ───────── --}}
        <aside class="lg:sticky lg:top-24 space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <div class="aspect-[16/10] bg-slate-100">
                    @if ($image)
                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $space->space_number }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full grid place-items-center text-slate-400 text-sm">No photo</div>
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $property->name }}</p>
                    <h2 class="text-lg font-bold text-slate-900 mt-0.5">{{ $space->space_number }}</h2>
                    <p class="text-sm text-slate-500">{{ str($space->space_type)->replace('_', ' ')->title() }} · {{ $space->bedrooms }} bed · {{ $space->bathrooms }} bath · capacity {{ $space->total_capacity }}</p>

                    <dl class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Monthly rent</dt><dd class="font-bold text-slate-900">₱{{ number_format($space->monthly_rent) }}</dd></div>
                        @if ($space->security_deposit)
                            <div class="flex justify-between"><dt class="text-slate-500">Security deposit</dt><dd class="text-slate-900">₱{{ number_format($space->security_deposit) }}</dd></div>
                        @endif
                        @if ($space->advance_payment)
                            <div class="flex justify-between"><dt class="text-slate-500">Advance payment</dt><dd class="text-slate-900">₱{{ number_format($space->advance_payment) }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <p class="text-sm font-bold text-slate-900 mb-3">What happens next</p>
                <ol class="space-y-3 text-sm text-slate-600">
                    <li class="flex gap-3"><span class="grid place-items-center w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-bold shrink-0 mt-0.5">1</span>The owner reviews your application and documents.</li>
                    <li class="flex gap-3"><span class="grid place-items-center w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-bold shrink-0 mt-0.5">2</span>You get a notification when it is approved or declined.</li>
                    <li class="flex gap-3"><span class="grid place-items-center w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-bold shrink-0 mt-0.5">3</span>If approved, you receive the contract and payment details.</li>
                </ol>
            </div>
        </aside>
    </div>
@endsection
