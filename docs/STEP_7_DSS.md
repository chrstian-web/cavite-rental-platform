# STEP 7 — Decision Support System

The centerpiece feature named in the thesis title. Builds on Steps 1–6, using
the `dss_criteria` / `dss_weights` / `dss_scores` tables from Step 1.

## What's included

**`app/Services/DssScoringService.php`** — the actual scoring engine.

Implements the exact formula from the brief:

```
Recommendation Score =
    Budget Score        × 30%
  + Location Score      × 20%
  + Amenities Score      × 15%
  + Property Type Score  × 10%
  + Room Capacity Score  × 10%
  + Distance Score       × 10%
  + Furnishing Score     × 5%
```

Except the weights aren't hard-coded as `0.30`, `0.20`, etc. — they're read
from `dss_weights` at request time via `activeWeights()`, so the Super Admin
screen actually controls the math, not just a display label.

Each of the seven criteria has its own scorer method (`scoreBudget()`,
`scoreLocation()`, …), each returning a transparent 0–100 value from a simple,
explainable rule — not a black box:
- **Budget**: 100 if within budget; otherwise decreases proportionally to how far over.
- **Location**: 100 if it matches the preferred city/municipality, 30 partial credit otherwise, 100 (neutral) if no preference given.
- **Amenities**: percentage of the renter's required amenities actually present.
- **Property type**: 100 on exact match, 0 otherwise, 100 if no preference.
- **Capacity**: 100 if both capacity and bedroom needs are met, 60 if only capacity is met, 0 if neither.
- **Distance**: haversine great-circle distance between a renter-supplied reference point and the property, scored like budget (100 within range, decreasing beyond it). Neutral (100) if either party is missing coordinates — this criterion never *penalizes* incomplete data, it just contributes nothing.
- **Furnishing**: 100 on match, 40 partial otherwise, 100 if no preference.

`buildReasons()` generates the plain-language explanation directly from the
same breakdown numbers that produced the score — so the "why recommended"
text can never drift out of sync with the actual math, unlike a separately
hard-coded explanation string would.

Every computed score is persisted to `dss_scores` with the full weighted
breakdown and the exact preferences snapshot used, so a score from last week
stays explainable even after an admin changes the weights.

**Admin configuration** (`Admin\DssController`, `admin/dss/index.blade.php`)
- Lists all 7 criteria with their current active weight.
- Saving **deactivates** the old weight row and **inserts a new one**, rather
  than editing in place — this is what makes historical `dss_scores` stay
  tied to the configuration that was actually active when they were computed
  (a core "transparent scoring" requirement from the brief).
- Shows a running total with a visual check for whether weights sum to 100%
  (not hard-enforced, since a thesis panel may want to see what happens with
  a deliberately unbalanced configuration — but it's clearly flagged).

**Tenant-facing recommendations** (`Tenant\RecommendationController`,
`tenant/recommendations/{create,results}.blade.php`)
- A preference form: budget, location, property type, occupants, bedrooms,
  required amenities, furnishing, and an optional advanced distance
  preference (lat/lng + max km — collapsed by default since most renters
  won't have coordinates handy).
- Results are ranked highest-score-first, each showing:
  - A circular score badge (green ≥80%, amber ≥50%, gray below).
  - The plain-language reasons list.
  - An expandable **full breakdown table** (raw score / weight / weighted
    contribution per criterion) — this is what makes the scoring "transparent"
    rather than a trust-me percentage, directly satisfying the brief's
    explainability requirement.

**Seeder**
- `database/seeders/DssCriteriaSeeder.php` — seeds the 7 criteria with the
  brief's default weights (30/20/15/10/10/10/5). Re-running it is safe: it
  only inserts a weight if the criterion has no active one yet, so it never
  clobbers an admin's saved configuration.

## Install this step

1. Copy `app/`, `database/seeders/`, `resources/views/`, and `routes/web.php`
   in (merge/overwrite).
2. Run the new seeder:
   ```bash
   php artisan db:seed --class=DssCriteriaSeeder
   ```
3. Test the flow:
   - As **tenant**, click **Recommended for You** in the nav.
   - Fill in a budget that's *higher* than your test unit's rent, matching
     location, matching property type. Submit.
   - You should see your test property with a high score (≥80%) and reasons
     like "Within your budget," "Located in your preferred city."
   - Click **See full score breakdown** — confirm the weighted numbers add
     up to the total score shown.
   - Log in as **Super Admin**, go to **DSS Configuration**. Change Budget's
     weight from 30 to 50 and Amenities from 15 to 0 (adjust others so it's
     still sensible), save.
   - Back as tenant, run the recommendation again with the *same*
     preferences — the score and breakdown should visibly shift to reflect
     the new weights.

If changing the admin weights actually changes the tenant-facing scores (not
just cosmetically, but in the real math), the DSS is correctly wired
end-to-end — data-driven, not hard-coded, exactly as the brief requires.

## Next step

**STEP 8 — Property Comparison & Favorites**: side-by-side comparison of up
to 3–4 properties (including their DSS score), a wishlist/favorites system
with duplicate prevention, and the admin/owner analytics dashboards (sections
20–22 of the brief) that finally have enough real data behind them
(applications, contracts, payments, reviews) to be meaningful.
