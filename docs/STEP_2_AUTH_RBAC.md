# STEP 2 — Authentication & RBAC

Builds directly on Step 1's `roles`/`permissions`/`users` schema.

## What's included

**Auth flow** (`app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/`, `routes/auth.php`)
- Register (`RegisteredUserController`) — self-registration is limited to `tenant`
  or `owner`; `super_admin` and `manager` accounts are provisioned by an admin
  in a later step, never by public signup.
- Login (`AuthenticatedSessionController`) with rate limiting (5 attempts) via
  `LoginRequest::authenticate()`.
- Forgot / reset password (`PasswordResetLinkController`, `NewPasswordController`).
- Email verification (`EmailVerificationPromptController`, `VerifyEmailController`,
  `EmailVerificationNotificationController`) using signed URLs.
- Password confirmation for sensitive actions (`ConfirmablePasswordController`).
- Profile edit + password change + account deletion (`ProfileController`,
  `app/Http/Requests/ProfileUpdateRequest.php`) — deletion soft-deletes, per
  Step 1's `SoftDeletes` on `users`.

**RBAC enforcement**
- `app/Http/Middleware/EnsureUserHasRole.php` — route-level role gate:
  `Route::middleware('role:owner,manager')`.
- `app/Http/Middleware/EnsureUserHasPermission.php` — finer-grained gate that
  checks the `permission_role` pivot: `Route::middleware('permission:properties.verify')`.
  This is what makes DSS weights, property verification, etc. configurable by
  role without hard-coding role names into every controller.
- `app/Policies/PropertyPolicy.php`, `app/Policies/RentalApplicationPolicy.php` —
  object-level authorization (e.g. "can *this* owner edit *this* property"),
  registered in `app/Providers/AppServiceProvider.php`. These are the pattern
  every future policy (RentalContract, Payment, MaintenanceRequest…) will follow.
- `bootstrap/app.php` — registers the `role` and `permission` middleware
  aliases (Laravel 12's middleware config lives here, not `Kernel.php`).

**Seeders** (`database/seeders/`)
- `RoleSeeder` — the 4 roles from the brief.
- `PermissionSeeder` — a starter permission set per module, mapped to roles
  (editable later from an admin screen since it's all DB-driven).
- `AdminUserSeeder` — one Super Admin account: `admin@caviterentals.test` /
  `ChangeMe123!` (change this password immediately after first login).
- `LocationSeeder` — the 20 Cavite municipalities from the brief, as data.
- `DatabaseSeeder` — calls all of the above in order.

**Views** — plain Blade + Tailwind (via CDN for now; Vite/compiled Tailwind
gets wired in when we build the public listing pages in Step 3, since that's
where real frontend polish matters):
- `resources/views/layouts/guest.blade.php`, `layouts/app.blade.php`
- `resources/views/auth/*.blade.php` — login, register, forgot/reset password,
  verify email, confirm password
- `resources/views/profile/edit.blade.php`
- `resources/views/dashboard/{admin,owner,manager,tenant}.blade.php` —
  intentionally thin placeholders. They exist to prove RBAC routing sends each
  role to the right place; the real widgets from sections 20–22 of the brief
  land once Properties, Applications, Payments, and DSS exist to power them.

---

## Install / test this step

1. Copy `app/Http/`, `app/Policies/`, `app/Providers/AppServiceProvider.php`,
   `database/seeders/`, `routes/`, `bootstrap/app.php`, and `resources/views/`
   into your project — **overwrite** the existing `routes/web.php`,
   `bootstrap/app.php`, and `app/Providers/AppServiceProvider.php`.
2. Run the seeders:
   ```bash
   php artisan db:seed
   ```
   You should see no errors, and a `super_admin` row with email
   `admin@caviterentals.test` in your `users` table.
3. Start the app:
   ```bash
   php artisan serve
   ```
4. Test the flow:
   - Visit `/register`, sign up as a **Tenant**, confirm you land on
     `/dashboard` and see the tenant placeholder.
   - Log out, log back in via `/login`.
   - Visit `/profile`, update your name, change your password.
   - Log in as `admin@caviterentals.test` / `ChangeMe123!` and confirm you
     land on the **admin** dashboard placeholder instead.
   - Try `/forgot-password` — since mail isn't configured yet, check
     `storage/logs/laravel.log` for the reset link (or set `MAIL_MAILER=log`
     in `.env`, which is the default).

If all four role placeholders render correctly for the right accounts, RBAC is
wired end-to-end and Step 3 (Property & Rental-Space management) can build
real authorized CRUD on top of it.

---

## Next step

**STEP 3 — Property & rental-space management**: Form Requests, Policies-backed
controllers, and Livewire/Blade views for owners to create properties, upload
images, and manage `rental_spaces` (condo units / boarding rooms / dorm beds),
plus the public property-listing pages.
