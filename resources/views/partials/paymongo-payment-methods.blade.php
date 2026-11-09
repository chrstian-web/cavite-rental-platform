@php
    $methodLabels = [
        'card' => 'Debit or credit card',
        'gcash' => 'GCash',
        'qrph' => 'QR Ph',
    ];
    $availableMethods = collect(config('services.paymongo.payment_methods', []))
        ->map(fn ($method) => $methodLabels[$method] ?? null)
        ->filter()
        ->values();
    $paymongoReady = config('services.payment_gateway') === 'paymongo'
        && filled(config('services.paymongo.secret_key'))
        && filled(config('services.paymongo.webhook_secret'));
@endphp

@if ($paymongoReady && $availableMethods->isNotEmpty())
    <div class="mb-4">
        <p class="text-xs text-slate-600 mb-2">Available on PayMongo secure checkout:</p>
        <div class="flex flex-wrap gap-2" aria-label="Available online payment methods">
            @foreach ($availableMethods as $method)
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">{{ $method }}</span>
            @endforeach
        </div>
        <p class="text-xs text-slate-500 mt-2">Choose your method on the secure checkout page. Payment is confirmed by the provider.</p>
    </div>
@else
    <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
        Secure online payment is unavailable until PayMongo and its payment-confirmation webhook are configured.
    </p>
@endif
