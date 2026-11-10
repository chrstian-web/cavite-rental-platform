# Owner subscription plans

Plans live in `config/subscriptions.php` (Free ₱0/2, Pro ₱500/6, Pro+ ₱1,000/13, VIP ₱1,500/20 properties).

- **Pop-up**: `partials/subscription-popup.blade.php`, included by `layouts/app.blade.php` for any owner who has not chosen a plan yet (new owners land on it after registering; existing owners see it on their next page). "Maybe later" hides it for the browser session only.
- **Recommendation**: `SubscriptionService::recommend()` picks the smallest plan that covers the owner's property count.
- **Choosing Free**: the owner's 2 oldest properties stay visible; the rest get `properties.subscription_hidden = true` and disappear from search, the landing page, comparison, recommendations, the API and their public page (owner and admins can still open them). They remain in the owner's "My Properties" list, marked hidden.
- **Boost**: owners on a paid plan rank first in the default search order (VIP, then Pro+, then Pro) and get a "Featured" badge. Explicit sorts (price, views, rating) are not overridden.
- **Billing**: paid plans use the same gateway setting as rental payments. Locally (`PAYMENT_GATEWAY=fake`) a demo checkout page confirms payment. In production, PayMongo's `checkout_session.payment.paid` webhook (already registered) activates the plan once the paid amount matches.
- **Expiry**: each paid period is 30 days. `php artisan subscriptions:expire` (scheduled daily at 00:10) returns lapsed owners to Free and re-applies the limit. The scheduler must be running (`php artisan schedule:work` locally, a cron entry for `schedule:run` in production).

Deploy: `php artisan migrate`.
