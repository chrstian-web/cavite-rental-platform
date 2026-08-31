# STEP 6 — Rental Contracts, Payments & Maintenance Requests

Builds on Steps 1–5. Uses the `rental_contracts` / `payments` /
`maintenance_requests` / `maintenance_request_images` / `reviews` tables from
Step 1 — no new migrations.

## New dependency: PDF generation

Rental contracts need a downloadable PDF (brief section 15: "Allow authorized
users to download a PDF copy"). This uses **barryvdh/laravel-dompdf**, a
well-established Laravel wrapper around DomPDF. Install it once:

```bash
composer require barryvdh/laravel-dompdf
```

No `.env` or config changes needed — it auto-registers.

## What's included

**Services**
- `app/Services/RentalContractService.php`:
  - `createFromApplication()` — refuses to create a contract unless the
    source application is `approved`, and refuses a second contract for the
    same application. Starts as `draft`.
  - `activate()` — the single source of truth for "is this unit actually
    occupied right now." Increments `rental_spaces.occupied_capacity` and
    flips the space to `occupied` once full — this is what makes an occupied
    unit stop showing as biddable/available elsewhere in the app.
  - `end()` — terminating or expiring a contract frees the space's capacity
    back up automatically.
- `app/Services/MaintenanceRequestService.php` — submit with photo uploads,
  update status.

**Policies**
- `RentalContractPolicy`, `MaintenanceRequestPolicy` — same
  tenant-sees-own / owner-or-manager-sees-their-property pattern as every
  other policy so far, registered in `AppServiceProvider`.
- `StoreReviewRequest::authorize()` enforces the brief's "prevent unlimited
  duplicate reviews" rule at the request layer (only the tenant on that
  contract, only once it's `expired`/`terminated`, only if no review exists
  yet) — backed by the DB-level `unique(['user_id', 'rental_contract_id'])`
  constraint from Step 1 as a second line of defense.

**Controllers**
- `Owner\RentalContractController` — create a contract from an approved
  application, list/view, activate, terminate, download PDF.
- `Owner\PaymentController` — record a payment against an active contract.
- `Owner\MaintenanceRequestController` — inbox with status filter, update status.
- `Tenant\RentalContractController` — view your own contracts, download PDF.
- `Tenant\PaymentController` — view your own payment history across all contracts.
- `Tenant\MaintenanceRequestController` — submit a request (only while your
  contract is `active`), view your own list.
- `Tenant\ReviewController` — leave a review once a contract has ended.

**Views** — full owner + tenant sides for contracts, payments, maintenance,
plus `resources/views/pdf/rental-contract.blade.php` (a clean, printable
contract with a signature-line layout).

**Nav** updated with Contracts / Maintenance (owner) and My Contracts / My
Payments / Maintenance (tenant). The owner's application review page now
shows a **Create rental contract** button once an application is approved.

## Install this step

1. `composer require barryvdh/laravel-dompdf` (one-time).
2. Copy `app/`, `resources/views/`, and `routes/web.php` in (merge/overwrite).
3. No new migrations/seeders.
4. Test the flow — continuing from Step 5's approved application:
   - As **owner**, open the approved application, click **Create rental
     contract**, fill in dates/terms, submit. You land on the contract page,
     status **Draft**.
   - Click **Activate contract**. Go check the unit in **My Properties →
     Units** — its status should now show **Occupied**.
   - Record a payment (mark it **Paid**).
   - Download the PDF — confirm it opens and shows correct tenant/owner/terms.
   - As **tenant**, go to **My Contracts**, open it, confirm the payment
     shows, download the same PDF.
   - While the contract is active, click **Submit maintenance request** and
     send one with a photo.
   - Back as **owner**, go to **Maintenance**, mark it **In Progress** then
     **Resolved**.
   - As **owner**, terminate the contract — recheck the unit's status flips
     back to **Available**.
   - As **tenant**, on the now-terminated contract, click **Leave a review**,
     submit a rating. Try submitting a second review for the same contract —
     it should be blocked.

If the unit's availability status correctly flips occupied → available as
contracts activate/terminate, the core business logic is solid.

## Next step

**STEP 7 — Decision Support System**: the DSS scoring engine
(`DssScoringService`), the admin weight-configuration screen, and the
tenant-facing "Recommended for you" results with a plain-language explanation
of *why* each property scored the way it did — the centerpiece feature named
in the thesis title.
