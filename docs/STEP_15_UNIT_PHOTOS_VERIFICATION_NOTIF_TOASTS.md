# STEP 15 — Per-Unit Photos, Property Verification Notification & SweetAlert Toasts

One new migration (`rental_space_images`) plus feature additions across
several files.

## 1. Per-unit photos

Previously only the property as a whole had photos (Step 3) — individual
units/rooms had none, so a renter couldn't see what *their specific room*
actually looks like, only the building/common areas.

- **New table**: `rental_space_images` (mirrors `property_images` from
  Step 1 — same `is_cover`/`sort_order` pattern).
- **New model**: `RentalSpaceImage`, plus `RentalSpace::images()`.
- **`Owner\RentalSpaceController`** now accepts an `images[]` upload on both
  create and edit, storing them under
  `storage/app/public/properties/{property}/units/{space}/`.
- **`Owner\RentalSpaceImageController`** (new) — delete a unit photo or set
  a different one as cover, same pattern as property images.
- **Owner's unit list** now shows a thumbnail per row.
- **Public property page** now shows each unit's cover photo (or "No photo"
  placeholder) right next to its details — this is the actual feature you
  asked for: a renter who can't visit in person can now see the specific
  room before applying.

## 2. Owner notified when their property is verified/rejected

Previously the loop was one-directional — Super Admin got notified when a
property was submitted, but the owner never heard back once a decision was
made.

- **New notification**: `PropertyVerificationNotification`, sent to the
  owner from `Admin\PropertyController::verify()`.
- **"My Properties"** nav link now gets the same red "light" badge pattern
  as your other sections when there's an unread verification update.
- Wired into the shared `notification-item.blade.php` translator so it shows
  a proper message either way: *"[Property] has been verified and is now
  live"* or *"[Property] was not approved for listing."*

## 3. SweetAlert2 toasts, site-wide

Every flash message (`session('status')` — "Property created," "Application
approved," "Payment recorded," login/registration confirmations, etc.) now
shows as a proper toast notification (top-right corner, auto-dismissing,
with a progress bar) instead of a plain static colored box. Validation error
summaries get the same treatment (red toast showing the first error, plus
the full list still shown inline below for anything with multiple errors).
Added to both `layouts/app.blade.php` (the authenticated experience) and
`layouts/public.blade.php` (guest-facing pages) via the
[SweetAlert2](https://sweetalert2.github.io/) CDN.

## Install this step

1. Copy `app/`, `resources/views/`, `routes/web.php`, and `tests/` in
   (merge/overwrite).
2. Run the new migration:
   ```bash
   php artisan migrate
   ```
3. Run tests:
   ```bash
   php artisan test
   ```
4. Test the flow:
   - As **owner**, edit an existing unit (or add a new one), upload a photo.
     Confirm it shows as a thumbnail in the units list.
   - Visit that property's **public** page — confirm the unit's photo shows
     next to its details.
   - As **Super Admin**, verify (or reject) a pending property. Log in as
     that property's **owner** — confirm you see a toast, a red dot on
     "My Properties," and the notification bell shows the update.
   - Do literally anything that shows a flash message (create a property,
     submit an application, etc.) — confirm it now appears as a toast in
     the top-right corner instead of a plain box.
