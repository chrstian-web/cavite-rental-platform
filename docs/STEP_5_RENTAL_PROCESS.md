# STEP 5 — Rental Process: Applications, Documents & Viewing Requests

Builds on Steps 1–4. Uses the `rental_applications` / `application_documents` /
`viewing_requests` tables from Step 1 — no new migrations.

## What's included

**Services**
- `app/Services/RentalApplicationService.php` — submits an application +
  uploads documents in one transaction, then notifies the property's
  owner *and* any assigned managers. `review()` updates status and notifies
  the tenant only on a real decision (approved/rejected), not on every touch.
- `app/Services/ViewingRequestService.php` — same notify-owner-and-managers
  pattern for new viewing requests, and notifies the tenant on any status change.

**Notifications** (`app/Notifications/`) — each sends both `mail` and `database`
channels, so they show up in Laravel's built-in notifications table (ready for
an in-app notification bell later) and as an email (logged to
`storage/logs/laravel.log` by default until real SMTP is configured):
- `NewRentalApplicationNotification`, `RentalApplicationStatusNotification`
- `NewViewingRequestNotification`, `ViewingRequestStatusNotification`

**Security**
- `app/Http/Controllers/ApplicationDocumentController.php` — the *only* way to
  reach an uploaded document. Every download re-checks `RentalApplicationPolicy::view`
  (applicant, the property's owner/manager, or a super admin) before serving
  the file from Laravel's private `local` disk. Documents are never stored on
  the public disk and never have a guessable public URL — matches the "never
  expose private files directly" requirement from the brief.
- `ViewingRequestPolicy` (new) — same ownership-chain pattern as `PropertyPolicy`
  and `RentalApplicationPolicy` from Step 2/3, registered in `AppServiceProvider`.
- `StoreHotspotRequest`-style chain-checking is mirrored here too: an owner can
  only review applications/viewings for properties they actually own or manage,
  re-verified server-side on every request — never trusted from a URL parameter.

**Controllers**
- `Tenant\RentalApplicationController` — apply for a specific rental space,
  view your own application list/detail.
- `Tenant\ViewingRequestController` — request a viewing on a property, view
  your own list.
- `Owner\RentalApplicationController` — inbox of applications for properties
  you own/manage, with a status filter; review screen to approve/reject with
  an optional note back to the applicant.
- `Owner\ViewingRequestController` — inbox with inline status updates
  (confirm / reschedule / complete / cancel).

**Views**
- `resources/views/tenant/applications/{create,index,show}.blade.php`
- `resources/views/tenant/viewings/{create,index}.blade.php`
- `resources/views/owner/applications/{index,show}.blade.php`
- `resources/views/owner/viewings/index.blade.php`
- Public property page (`properties/show.blade.php`) now has an **Apply**
  link per available unit and a **Request a Viewing** button, both hidden
  unless you're logged in as a tenant.
- Nav updated with **My Applications** / **My Viewings** (tenant) and
  **Applications** / **Viewing Requests** (owner/manager).

## Install this step

1. Copy `app/`, `resources/views/`, and `routes/web.php` in (merge/overwrite).
2. No new migrations/seeders.
3. Test the flow:
   - As a **tenant**, visit a verified property, click **Apply** on an
     available unit. Fill the form, optionally attach a test file (any
     small image or PDF) as a document, submit.
   - Check `storage/logs/laravel.log` for the "New rental application
     received" email — confirms the notification fired.
   - Log in as the **owner**, go to **Applications**, open the one you just
     submitted. Click a document link to confirm it downloads (this proves
     the private-storage authorization path works, not just that a file
     exists).
   - Approve it with a note. Check the log again for the tenant's
     "Update on your rental application" email.
   - Back as the tenant, go to **My Applications** — status should show
     **Approved** with your note visible.
   - Repeat a shorter loop for **Request a Viewing** → **Viewing Requests**
     → **Confirm**.

If documents download correctly *only* when logged in as an authorized
party (try logging in as a different tenant and hitting the same document
URL directly — it should 403), the private-storage security is solid.

## Next step

**STEP 6 — Tenant management**: payment recording/tracking, maintenance
requests, and reviews — what happens *after* an application is approved
and a contract exists. (Rental contracts themselves, section 15 of the
brief, will ship at the start of this step since payments and reviews both
depend on a contract existing.)
