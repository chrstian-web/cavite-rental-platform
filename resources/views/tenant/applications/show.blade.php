@extends('layouts.app')

@section('title', 'Application — '.$application->property->name)

@section('content')
    <a href="{{ route('tenant.applications.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>My Applications</span></a>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mt-4 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold text-slate-900">{{ $application->property->name }} — {{ $application->rentalSpace->space_number }}</h1>
            <span class="text-xs px-2 py-1 rounded-full
                {{ match($application->status) {
                    'approved' => 'bg-green-50 text-green-700',
                    'rejected', 'cancelled' => 'bg-red-50 text-red-700',
                    'under_review' => 'bg-blue-50 text-blue-700',
                    default => 'bg-amber-50 text-amber-700',
                } }}">
                {{ str($application->status)->replace('_', ' ')->title() }}
            </span>
        </div>

        @if ($application->decision_reason)
            <div class="mb-4 text-sm bg-slate-50 border border-slate-200 rounded-lg p-3 text-slate-600">
                <strong>Note from the owner:</strong> {{ $application->decision_reason }}
            </div>
        @endif

        @if (request('checkout') === 'success')
            <div class="mb-4 text-sm bg-blue-50 border border-blue-200 rounded-lg p-3 text-blue-800">
                Checkout returned successfully. Your payment will be confirmed here once the payment provider verifies it.
            </div>
        @elseif (request('checkout') === 'cancelled')
            <div class="mb-4 text-sm bg-amber-50 border border-amber-200 rounded-lg p-3 text-amber-800">
                Checkout was cancelled. You can review the terms and try again whenever you are ready.
            </div>
        @endif

        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-slate-500">Desired move-in</dt>
            <dd class="text-slate-900">{{ $application->desired_move_in_date->format('M j, Y') }}</dd>
            <dt class="text-slate-500">Requested lease end</dt>
            <dd class="text-slate-900">{{ $application->lease_end_date?->format('M j, Y') ?? '—' }}</dd>
            <dt class="text-slate-500">Occupants</dt>
            <dd class="text-slate-900">{{ $application->number_of_occupants }}</dd>
            <dt class="text-slate-500">Employment</dt>
            <dd class="text-slate-900">{{ $application->employment_status ? str($application->employment_status)->replace('_',' ')->title() : '—' }}</dd>
        </dl>

        @if ($application->status === 'approved' && $application->reservation_offer_sent_at)
            <section class="mt-5 border-t border-slate-100 pt-5">
                <h2 class="text-base font-semibold text-slate-900">Reservation fee and owner’s terms</h2>
                <p class="mt-2 text-sm text-slate-600">Reservation fee: <strong>₱{{ number_format((float) $application->reservation_fee, 2) }}</strong></p>
                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 whitespace-pre-wrap">{{ $application->reservation_terms }}</div>

                @if ($application->reservationFeePayment?->status === 'paid')
                    @if ($application->reservationFeePayment->gateway === 'fake')
                        <p class="mt-4 rounded-lg bg-amber-50 border border-amber-300 p-3 text-sm text-amber-900">
                             {{ str($application->reservationFeePayment->payment_method)->replace('_', ' ') }}.
                        </p>
                    @else
                        <p class="mt-4 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                            Reservation fee paid and confirmed by the payment provider. The owner has been notified and can proceed with the rental contract.
                        </p>
                    @endif
                    @if ($application->reservationFeePayment->receipt_number)
                        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            <p class="font-medium text-slate-900">Receipt {{ $application->reservationFeePayment->receipt_number }}</p>
                            <p class="mt-1">Please download and keep this receipt as your proof of payment. You can send it to the property owner to confirm your reservation.</p>
                            <a href="{{ route('tenant.payments.receipt', $application->reservationFeePayment) }}"
                               class="mt-3 inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Download receipt (PDF)
                            </a>
                        </div>
                    @endif
                @else
                    @if ($application->reservationFeePayment?->status === 'pending')
                        <p class="mt-4 text-sm text-amber-700">Reservation fee payment is awaiting confirmation. Do not pay again if your provider is still processing it.</p>
                    @endif
                    @include('partials.paymongo-payment-methods')
                    @if ((config('services.payment_gateway') === 'fake' && app()->environment(['local', 'testing'])) || (config('services.payment_gateway') === 'paymongo' && filled(config('services.paymongo.secret_key')) && filled(config('services.paymongo.webhook_secret')) && count(config('services.paymongo.payment_methods', [])) > 0))
                        <form method="POST" action="{{ route('tenant.applications.reservation-fee.pay', $application) }}" class="mt-4 space-y-3">
                            @csrf
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="terms_accepted" value="1" required class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span>I confirm that I have read and agree to the reservation terms and conditions above.</span>
                            </label>
                            @error('terms_accepted') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('online') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="submit" class="min-h-10 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                                Agree and pay ₱{{ number_format((float) $application->reservation_fee, 2) }}
                            </button>
                        </form>
                    @endif
                @endif
            </section>
        @elseif ($application->status === 'approved')
            <p class="mt-5 rounded-lg bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800">Your application is approved. The owner will send the reservation fee and terms separately.</p>
        @endif

        @if ($application->documents->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm font-medium text-slate-700 mb-2">Your documents</p>
                @include('partials.document-viewer', ['items' => \App\Support\DocumentViewerItems::fromApplication($application->documents)])
            </div>
        @endif
    </div>
@endsection
