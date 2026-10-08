ONLINE PAYMENT (test mode) - install

1) Copy the "app", "config", "resources" and "routes" folders from this zip into your project root
   (C:\laravel-projects\cavite-rental-platform) and choose "Replace the files".
   - This zip's app\Models\Payment.php replaces the one from the down-payment zip (it contains both changes).
   - routes\web.php already includes every route we added before (payments.review, payments.received, ...).

2) Optional: add this line to .env (fake is already the default):
        PAYMENT_GATEWAY=fake
   The fake checkout only works when APP_ENV=local in .env.

3) Run:
        php artisan optimize:clear
        php artisan route:list --name=fake-checkout
        php artisan route:list --name=pay-online

No database migration is needed (the columns already exist).
