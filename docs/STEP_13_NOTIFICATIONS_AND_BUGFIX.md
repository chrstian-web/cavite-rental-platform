# STEP 13 — Fix Document Download Bug + Full Notification Center

## Bug fix: every document download was failing

`ApplicationDocumentController::download()` declared its return type as
`Illuminate\Http\Response`, but `Storage::disk('local')->download(...)`
actually returns a Symfony `BinaryFileResponse` — a different class that
doesn't satisfy that exact type hint. PHP throws a fatal `TypeError` the
instant a method's declared return type doesn't match what it actually
returns, which is exactly the "error when clicking any document" you hit.
Fixed by widening the return type to `Symfony\Component\HttpFoundation\Response`
— the actual shared base class both `Illuminate\Http\Response` and
`BinaryFileResponse` inherit from. A regression test
(`ApplicationDocumentDownloadTest`) now asserts a real download succeeds, so
this specific class of bug can't silently come back.

## New: notification center (bell + full inbox) for every role

You asked for a way to actually *see* the notifications that were being
created in the database this whole time but had no page to view them on
(previously only reachable via the Step 9 API). Now:

**`resources/views/partials/notification-bell.blade.php`** — a bell icon in
the nav for every authenticated role, with an unread-count badge, an Alpine.js
dropdown showing the 8 most recent notifications, a "Mark all read" action,
and a link to the full inbox.

**`resources/views/notifications/index.blade.php`** — the full paginated
notification history, same design.

**`resources/views/partials/notification-item.blade.php`** — translates a
notification's stored `type` into a human-readable sentence and the correct
link to click through to (e.g. `new_rental_application` → "New application
from [name] for [property]" → links straight to that application's review page).

**Per-section "light" badges** — exactly what you described: a small red dot
above specific nav links (not just the bell) when there's something new *in
that section specifically*:

| Nav link | Lights up when |
|---|---|
| Owner/Manager "Applications" | A new rental application came in |
| Owner/Manager "Viewing Requests" | A new viewing request came in |
| Owner/Manager "Maintenance" | A new maintenance request came in |
| Tenant "My Applications" | An application status changed |
| Tenant "My Viewings" | A viewing status changed |
| Tenant "My Contracts" | A contract was activated |
| Tenant "Maintenance" | A maintenance request status changed |
| Super Admin "Verify Properties" | A new property is awaiting verification |

`User::hasUnreadNotificationOfType()` (new model method) drives this —
checks the current user's unread notifications for a matching `type` key,
reused by both the bell and every per-link badge via the new
`partials/nav-link.blade.php` helper.

## New notification classes (closing real gaps, not cosmetic)

Three notification types existed in name only — nothing actually triggered
them, meaning **Super Admin never got notified about anything**, and
maintenance/contract events were silent:

- `NewPropertySubmittedNotification` — now fires to every Super Admin the
  moment `PropertyService::create()` runs. This is the fix for the specific
  "light above Verify Properties" behavior you asked for.
- `NewMaintenanceRequestNotification` / `MaintenanceRequestStatusNotification`
  — owner/manager notified on submission, tenant notified on status change.
- `ContractActivatedNotification` — tenant notified when their contract goes live.

## Install this step

1. Copy `app/`, `resources/views/`, `routes/web.php`, and `tests/` in
   (merge/overwrite).
2. No new migrations.
3. Run tests:
   ```bash
   php artisan test
   ```
   Should now show 61 passing (56 + 3 new notification tests + 2 document
   download tests).
4. Manual check:
   - As **owner**, create a new property. Log in as **Super Admin** — you
     should see a red dot on the bell *and* above "Verify Properties."
   - Click the bell, click that notification — it should take you straight
     to `/admin/properties`, and the dot should be gone after "Mark all read."
   - Go back to an approved rental application, download a document — it
     should download cleanly with no error page.
   - As **owner**, activate a tenant's contract. Log in as that **tenant** —
     bell and "My Contracts" should both light up.

## What's left

With this fix and the notification center in place, every functional area
raised across this whole build has been addressed. The honest remaining
notes are the same as before Step 12: a full static-analysis pass
(PHPStan/Larastan) as its own dedicated step if "provably no errors" matters
for the defense, and owner/tenant-side report exports if you want reporting
beyond the admin-only scope the brief specifically named.
