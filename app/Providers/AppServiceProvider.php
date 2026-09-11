<?php

namespace App\Providers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Models\ViewingRequest;
use App\Policies\MaintenanceRequestPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\RentalApplicationPolicy;
use App\Policies\RentalContractPolicy;
use App\Policies\ViewingRequestPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(RentalApplication::class, RentalApplicationPolicy::class);
        Gate::policy(ViewingRequest::class, ViewingRequestPolicy::class);
        Gate::policy(RentalContract::class, RentalContractPolicy::class);
        Gate::policy(MaintenanceRequest::class, MaintenanceRequestPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        // Mobile API rate limit: 60 requests/minute per authenticated user,
        // or per IP for unauthenticated (public property browsing) requests.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
