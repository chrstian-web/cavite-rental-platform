# STEP 10 — Automated Tests

Builds on every previous step. Uses PHPUnit (already bundled with Laravel's
default `composer create-project` install — no new package needed) with
Laravel's Feature/Unit test conventions and `RefreshDatabase`, which runs
every migration fresh in an isolated in-memory SQLite database for each test
run. **This never touches your real MySQL dev database** — safe to run any
time without losing your manually-tested data (kristine, war, dags, etc.).

## Important honesty note

I don't have a PHP interpreter in my environment, so I could not actually
*execute* these tests to confirm they pass — I wrote them by carefully
cross-referencing the exact routes, field names, and model relationships from
every step we already built together. There's a real chance one or two have
a small mismatch (a field name, a status code) that only running them will
reveal. That's expected and normal for a first test-writing pass — please run
them and paste me the output, the same way we've debugged everything else in
this build. I'd rather tell you this upfront than have you discover it and
wonder if something's wrong on your end.

## What's included

**Factories** (`database/factories/`) — new: `RoleFactory`, `LocationFactory`,
`AmenityFactory`, `PropertyFactory`, `RentalSpaceFactory`. `UserFactory` is
extended with role helper states (`->tenant()`, `->owner()`, `->manager()`,
`->superAdmin()`) since our custom `User` model requires a `role_id` that
Laravel's default factory doesn't know about.

**Feature tests** (`tests/Feature/`)
- `Auth/RegistrationTest.php`, `Auth/LoginTest.php` — registration,
  role-allowlist enforcement (can't self-register as `super_admin`),
  password confirmation, email uniqueness, login/logout.
- `Authorization/RoleMiddlewareTest.php` — the `role:` middleware from
  Step 2 actually blocks the wrong roles and allows the right ones.
- `Property/PropertyManagementTest.php` — an owner can create a property; a
  tenant cannot; **an owner cannot edit another owner's property** (the core
  ownership-check test); Super Admin verification.
- `Property/PropertySearchTest.php` — unverified/unavailable properties never
  appear publicly; type/location/budget filters work; view count increments.
- `RentalApplication/RentalApplicationTest.php` — submission, cross-tenant
  privacy (tenant A can't view tenant B's application), approval, and
  **an owner cannot approve an application for a property they don't own**.
- `Favorite/FavoriteTest.php` — add, toggle-off, and the DB-level unique
  constraint actually throwing when bypassed at the model layer directly.
- `ViewingRequest/ViewingRequestTest.php` — request + owner confirmation.
- `Payment/PaymentTest.php` — owner records a payment; **an owner cannot
  record a payment against another owner's contract**; tenant sees their
  own history.
- `MaintenanceRequest/MaintenanceRequestTest.php` — submission on an active
  contract succeeds; submission on a **draft** contract is correctly
  rejected (422); owner status updates.
- `Api/ApiAuthenticationTest.php` — register/login return a token; wrong
  password fails cleanly; a protected endpoint 401s without a token and
  succeeds with one; public property browsing needs no token.

**Unit test** (`tests/Unit/DssScoringServiceTest.php`) — the most important
test in the suite, since it verifies the thesis's centerpiece math directly,
independent of HTTP/routing:
- A property matching every preference and within budget scores exactly **100**.
- A property at double the budget scores **0** on the budget criterion.
- A property 20% over budget scores exactly **80** (`100 - 20`), proving the
  proportional-penalty formula, not just a pass/fail threshold.
- Preferences left blank stay **neutral (100)** rather than penalizing —
  proving the "don't punish incomplete input" design decision from Step 7.
- **Changing the admin-configured weight actually changes the computed
  score** — the same thing we manually verified in the browser, now locked
  in as a regression test so it can never silently break again.
- Amenity matching returns the correct percentage (2 of 3 required = 66.67%).
- Only verified + available units are ever scored.

## Install and run this step

1. Copy `app/Models/Location.php`, `app/Models/Amenity.php` (both gained a
   `HasFactory` trait), `database/factories/`, and `tests/` in
   (merge/overwrite).
2. No migrations/seeders to run — `RefreshDatabase` handles schema per test run.
3. Run the whole suite:
   ```bash
   php artisan test
   ```
   or the equivalent:
   ```bash
   vendor\bin\phpunit
   ```
4. **Paste me the full output.** Some tests may fail on the first run — that's
   normal and expected per the honesty note above. Common first-pass issues
   to watch for:
   - A route name or URL that doesn't quite match — easy one-line fix.
   - A field the `RentalApplication`/`ViewingRequest`/`RentalContract`
     `$fillable` array doesn't include — I'll add it.
   - Rate limiting from `LoginRequest` interfering between test methods
     (unlikely with a fresh cache per test, but possible).

We'll debug together exactly like we did every error earlier in this build —
paste the red output, I'll trace it against the actual code, and we fix it
one at a time until the suite is green.

## Next step

With tests in place, the remaining honest gaps from the original brief are:
**Step 11 (reporting/export module — PDF/CSV)** and general polish (search
sorting options, distance-based search). Worth deciding priority once the
test suite is confirmed passing.
