@extends('layouts.app')

@section('title', 'Review Application')

@section('content')
    <a href="{{ route('owner.applications.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>Applications</span></a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6">
            <h1 class="text-lg font-semibold text-slate-900 mb-1">
                {{ $application->user->first_name }} {{ $application->user->last_name }}
            </h1>
            <p class="text-sm text-slate-500 mb-4">
                Applying for {{ $application->property->name }} — {{ $application->rentalSpace->space_number }}
            </p>

            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $application->user->email }}</dd>
                <dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ $application->user->phone ?? '—' }}</dd>
                <dt class="text-slate-500">Desired move-in</dt><dd class="text-slate-900">{{ $application->desired_move_in_date->format('M j, Y') }}</dd>
                <dt class="text-slate-500">Requested lease end</dt><dd class="text-slate-900">{{ $application->lease_end_date?->format('M j, Y') ?? '—' }}</dd>
                <dt class="text-slate-500">Occupants</dt><dd class="text-slate-900">{{ $application->number_of_occupants }}</dd>
                <dt class="text-slate-500">Employment</dt><dd class="text-slate-900">{{ $application->employment_status ? str($application->employment_status)->replace('_',' ')->title() : '—' }}</dd>
                <dt class="text-slate-500">Monthly income</dt><dd class="text-slate-900">{{ $application->monthly_income ? '₱'.number_format($application->monthly_income) : '—' }}</dd>
                <dt class="text-slate-500">Emergency contact</dt>
                <dd class="text-slate-900">
                    {{ $application->emergency_contact_name ?? '—' }}
                    @if ($application->emergency_contact_number) ({{ $application->emergency_contact_number }}) @endif
                </dd>
            </dl>

            @if ($application->notes)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-1">Notes from applicant</p>
                    <p class="text-sm text-slate-600">{{ $application->notes }}</p>
                </div>
            @endif

            @if ($application->documents->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-2">Submitted documents</p>
                    @include('partials.document-viewer', ['items' => \App\Support\DocumentViewerItems::fromApplication($application->documents)])
                </div>
            @else
                <p class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-400">No documents submitted.</p>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <p class="text-sm font-medium text-slate-700 mb-1">Current status</p>
            <span class="text-xs px-2 py-1 rounded-full
                {{ match($application->status) {
                    'approved' => 'bg-green-50 text-green-700',
                    'rejected', 'cancelled' => 'bg-red-50 text-red-700',
                    'under_review' => 'bg-blue-50 text-blue-700',
                    default => 'bg-amber-50 text-amber-700',
                } }}">
                {{ str($application->status)->replace('_', ' ')->title() }}
            </span>

            @if (! in_array($application->status, ['approved', 'rejected', 'cancelled']))
                <form method="POST" action="{{ route('owner.applications.review', $application) }}" class="mt-4 space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Decision</label>
                        <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="under_review">Mark as Under Review</option>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Note to applicant (optional)</label>
                        <textarea name="decision_reason" rows="3" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Reservation fee (₱, required when approving)</label>
                        <input type="number" name="reservation_fee" min="0.01" max="99999999.99" step="0.01"
                               value="{{ old('reservation_fee', $application->reservation_fee ?? $application->property->reservation_fee) }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                        <p class="text-xs text-slate-400 mt-1">Pre-filled from the property's reservation fee; you can adjust it before approving.</p>
                        @error('reservation_fee') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Reservation terms and conditions (required when approving)</label>
                        <textarea name="reservation_terms" rows="5" maxlength="10000" class="w-full rounded-lg border-slate-300 text-sm"
                                  placeholder="Write the reservation fee terms the renter must accept before paying.">{{ old('reservation_terms') }}</textarea>
                        @error('reservation_terms') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2">
                        Save decision
                    </button>
                </form>
            @elseif ($application->status === 'approved')
                <div class="mt-4">
                    @if ($application->reservationFeePayment)
                        @php
                            $reservationPayment = $application->reservationFeePayment;
                            $paymentStatus = str($reservationPayment->status)->replace('_', ' ')->title();
                            $paymentStatusClass = match ($reservationPayment->status) {
                                'paid' => 'border-green-200 bg-green-50 text-green-800',
                                'failed', 'rejected' => 'border-red-200 bg-red-50 text-red-800',
                                default => 'border-amber-200 bg-amber-50 text-amber-900',
                            };
                        @endphp
                        <div class="mb-4 rounded-lg border p-3 text-sm {{ $paymentStatusClass }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold">Reservation fee payment</p>
                                <span class="rounded-full bg-white/70 px-2 py-1 text-xs font-medium">{{ $paymentStatus }}</span>
                            </div>
                            <p class="mt-2 text-base font-semibold">₱{{ number_format((float) $reservationPayment->amount, 2) }}</p>
                            @if ($reservationPayment->status === 'paid')
                                <p class="mt-1">Payment received{{ $reservationPayment->payment_date ? ' on '.$reservationPayment->payment_date->format('M j, Y') : '' }}{{ $reservationPayment->payment_method ? ' via '.str($reservationPayment->payment_method)->replace('_', ' ')->title() : '' }}.</p>
                            @elseif ($reservationPayment->status === 'failed' || $reservationPayment->status === 'rejected')
                                <p class="mt-1">The reservation fee payment needs attention before the contract can be created.</p>
                            @else
                                <p class="mt-1">The reservation fee payment is still being processed.</p>
                            @endif
                        </div>
                    @endif

                    @if ($application->contract)
                        <a href="{{ route('owner.contracts.show', $application->contract) }}"
                           class="block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg py-2.5">
                            View contract
                        </a>
                    @elseif ($application->reservationFeePayment?->status === 'paid')
                        <a href="{{ route('owner.applications.contract.create', $application) }}"
                           class="block text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2.5">
                            Create rental contract
                        </a>
                    @else
                        @if ($application->reservation_offer_sent_at)
                            <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                                <p class="font-medium">Reservation fee: ₱{{ number_format((float) $application->reservation_fee, 2) }}</p>
                                <p class="mt-2 whitespace-pre-wrap">{{ $application->reservation_terms }}</p>
                            </div>
                        @endif
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                            @if ($application->reservation_offer_sent_at)
                                <p class="mt-1">Waiting for the renter to accept the terms and complete payment before you can create the contract.</p>
                            @else
                                <p>No reservation-fee offer has been sent for this approved application yet.</p>
                            @endif
                        </div>
                        @if (! $application->reservation_terms_accepted_at)
                            <form method="POST" action="{{ route('owner.applications.review', $application) }}" class="mt-4 space-y-3">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Reservation fee (₱)</label>
                                    <input type="number" name="reservation_fee" min="0.01" max="99999999.99" step="0.01" required
                                           value="{{ old('reservation_fee', $application->reservation_fee ?? $application->property->reservation_fee) }}"
                                           class="w-full rounded-lg border-slate-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Terms and conditions</label>
                                    <textarea name="reservation_terms" rows="5" maxlength="10000" required class="w-full rounded-lg border-slate-300 text-sm">{{ old('reservation_terms', $application->reservation_terms) }}</textarea>
                                </div>
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2">
                                    {{ $application->reservation_offer_sent_at ? 'Update and resend fee offer' : 'Send reservation fee offer' }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
