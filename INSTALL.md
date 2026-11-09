# Cavite Rentals landing redesign

Copy these files over your project (same paths):

- app/Services/LandingData.php            (new)
- app/Http/Controllers/Public/PropertyController.php   (adds count() method)
- routes/web.php                          (adds the properties.count route above properties/{slug})
- resources/views/welcome.blade.php
- public/css/landing.css
- public/js/landing.js
- resources/views/layouts/public.blade.php   (landing nav: logo, Log in, Sign up)
- public/images/cavite-rentals-logo.svg   (new; swap in your own logo file if you have one)

If your routes/web.php, PropertyController.php, or layouts/public.blade.php changed since you made the zip, don't overwrite them.
Add just these two pieces instead:

1. In PropertyController (and `use Illuminate\Http\JsonResponse;`):

    public function count(Request $request): JsonResponse
    {
        $count = $this->applyPropertyFilters($this->baseVerifiedAvailableQuery(), $request)->count();
        return response()->json(['count' => $count]);
    }

2. In routes/web.php, ABOVE the properties/{slug} route:

    Route::get('/properties/count', [PublicPropertyController::class, 'count'])
        ->middleware('throttle:60,1')->name('properties.count');

Then run:  php artisan route:clear && php artisan cache:clear && php artisan view:clear

Notes
- The 3D robot is gone. landing-mascots.blade.php is untouched (auth pages and dashboards still use it).
- Home page numbers are cached for 10 minutes (LandingData). Call LandingData::forget() after a property is verified if you want instant updates.
- Map tiles come from OpenStreetMap (no API key). For heavy traffic, change the TILES constant at the top of landing.js to a keyed provider such as MapTiler or Stadia.
- Map pins use the latitude/longitude columns of `locations` when filled; otherwise approximate city centers built into LandingData.
