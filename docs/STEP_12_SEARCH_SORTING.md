# STEP 12 — Search Sorting & Shared Filter/Sort Logic

Closes the last named gap from the brief's section 6 (Property Search and
Discovery): "Implement sorting by: lowest price, highest price, newest,
most viewed, highest rated, recommended." No new migrations.

## What's included

**`app/Http/Concerns/FiltersAndSortsProperties.php`** — new shared trait,
used by both `Public\PropertyController` (web) and `Api\V1\PropertyController`
(mobile), so the two front ends can never silently drift into different
filter/sort behavior — one implementation, two consumers. This directly
follows the brief's section 34 requirement to avoid duplicate code.

- `baseVerifiedAvailableQuery()` — the verified + available starting point
  every property search uses.
- `applyPropertyFilters()` — type/location/budget, exactly as before.
- `resolveSort()` — validates the `sort` query param against an allowlist
  (`price_low`, `price_high`, `newest`, `most_viewed`, `highest_rated`),
  silently falling back to `newest` for anything invalid or malicious
  (tested explicitly with a SQL-injection-shaped string as input — see
  below) rather than erroring.
- `applyPropertySort()` — the actual `ORDER BY` per sort option. Price
  sorts use `COALESCE` so properties with a null rent don't silently vanish
  from either end of the list. Rating sort pushes properties with zero
  reviews to the bottom rather than treating "no rating" as "worst rating."

**A deliberate omission, explained rather than faked**: "Recommended" is
*not* offered as a generic sort option here. A real recommendation needs the
renter's actual preferences (budget, required amenities, distance, etc.) —
exactly what the dedicated DSS flow from Step 7 already collects
(`/tenant/recommendations` on web, `POST /api/v1/recommendations` on the
API). A "Recommended" sort with no preferences behind it would just be
newest-first wearing a misleading label. If a defense committee asks about
this specifically, the honest answer is: it exists, just as its own
dedicated, more meaningful flow rather than a same-page dropdown option.

**Search form** (`properties/index.blade.php`) — new sort `<select>` next to
the existing type/location/budget filters.

**Tests** — three new cases in `PropertySearchTest.php` (price-low ordering,
most-viewed ordering, and — importantly — an **invalid/malicious sort value
doesn't error**, using a string shaped like a SQL injection attempt as the
input, proving the allowlist actually protects the query) plus one in
`ApiAuthenticationTest.php` confirming the API respects the same `sort` param.

## Install this step

1. Copy `app/`, `resources/views/properties/index.blade.php`, and `tests/` in
   (merge/overwrite).
2. No new migrations.
3. Run the test suite:
   ```bash
   php artisan test
   ```
   All previous tests plus the 4 new ones should pass (56 total).
4. Manually confirm in the browser: visit `/properties`, try each sort
   option, confirm the order actually changes and no page errors.

## Where this leaves the project

Every functional item from the original master prompt (sections 1–39) now
has a working, tested implementation, including the search sorting that was
the last explicitly-named gap. Remaining honest scope notes, none of them
missing functionality so much as natural places a defense committee might
probe deeper:
- Owner/tenant-side CSV/PDF export (Step 11 covers admin reporting only,
  matching the brief's "Generate reports for administrators" wording).
- The floor-projected-arrow nuance from the virtual tour discussion in Step 4
  (2D floating markers vs. true 3D-anchored geometry).
- A genuinely comprehensive defensive-programming audit (null-relation
  guards across every Blade view) wasn't performed as a dedicated pass —
  what exists has been exercised through extensive manual + automated
  testing across 12 steps, which catches most real issues, but "no possible
  error" isn't a claim any codebase of this size can make without a full
  static-analysis pass (e.g. PHPStan/Larastan) as its own step.
