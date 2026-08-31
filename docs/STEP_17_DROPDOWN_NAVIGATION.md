# STEP 17 — Dropdown Navigation & Profile Menu

First of several steps addressing your navigation/verification/OTP request.
This step is self-contained: no new tables, no existing functionality
removed — every link from the old flat nav still exists, just organized.

## What's included

**Nothing was deleted.** Per requirement #18 ("do not remove or break
existing functionality"), every single nav link from before still works —
they're just grouped into dropdowns instead of a long flat row.

**`partials/nav-dropdown.blade.php`** (new) — a reusable grouped-link
dropdown. Used for:
- **"Rentals"** (owner/manager/admin): Applications, Viewing Requests,
  Contracts, Maintenance
- **"My Rentals"** (tenant): My Applications, My Viewings, My Contracts,
  My Payments, Maintenance
- **"Manage"** (Super Admin): Verify Properties, DSS Configuration, Reports

Each dropdown shows the same red "unread" dot pattern from Step 15/16, both
on the dropdown trigger itself (if *anything* inside is unread) and on the
individual item.

**`partials/profile-dropdown.blade.php`** (new) — replaces the old plain
"Profile" link + separate logout button, matching your reference image:
initials avatar, name + role + email header, then **My Profile**,
**Account Settings**, **Notifications** (with an unread count badge), and
**Log out**. Closes on outside click (Alpine `@click.outside`).

**`partials/nav-search.blade.php`** (new) — replaces the plain "Browse
Properties" text link, on both the authenticated nav and the public/guest
nav. A search icon opens a small dropdown with Location, Property Type, and
Min/Max rent — submitting goes straight to the existing `/properties`
listing with those filters pre-applied. A "Browse all properties" link at
the bottom still gets you the full unfiltered list.

**Mobile**: rather than trying to nest dropdown-within-dropdown on a phone
screen (awkward and easy to get wrong), a hamburger icon on the right opens
a simple flat stacked list of every link — same links, no animation
complexity, guaranteed to work on any screen size.

## Install this step

1. Copy `resources/views/partials/` (the 3 new files) and
   `resources/views/layouts/` (both `app.blade.php` and `public.blade.php`,
   updated) in — merge/overwrite.
2. No migrations, no new packages.
3. Test:
   - Log in as **owner** — confirm "My Properties," "Rentals" dropdown
     (with Applications/Viewings/Contracts/Maintenance inside), the search
     icon, notification bell, and the new profile avatar dropdown all work.
   - Click your avatar — confirm the dropdown shows your name, role, email,
     and closes when you click outside it.
   - Click the search icon — set a location/budget, submit — confirm it
     lands on `/properties` with those filters already applied.
   - Shrink your browser window below ~768px — confirm the hamburger icon
     appears and opens the flat mobile menu.
   - Repeat for **tenant** and **Super Admin** accounts to confirm their
     respective dropdown groups show the right links.
   - Visit the site logged **out** — confirm the guest nav also has the
     search icon instead of a plain "Browse Properties" link.

## What's next

This request has three more substantial pieces, each big enough to warrant
its own step (new tables, file security, email delivery):

- **Step 18 — Property browsing page redesign**: sidebar filters (not just
  the nav quick-search), the card-grid layout from your spec, and the
  mobile filter-drawer pattern.
- **Step 19 — Owner verification workflow**: `owner_verifications` and
  `verification_documents` tables, the document upload page, the honest
  version of "automated verification" (structural checks only — file
  exists, correct format/size — real document *authenticity* verification
  isn't something any system can claim to do reliably, so per your own
  spec's instruction, anything not clearly resolvable routes to admin
  review), and the admin review interface.
- **Step 20 — OTP email verification**: `otp_verifications` table
  (hashed codes, expiry, attempt limits, resend cooldown), the email
  template, and the verification status page.

Let me know which to build next, or if you'd like to test Step 17 first.
