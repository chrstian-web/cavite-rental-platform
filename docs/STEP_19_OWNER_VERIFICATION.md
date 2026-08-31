# STEP 19 — Owner Verification Workflow

Builds on Steps 1–18. Two new tables (`owner_verifications`,
`verification_documents`) plus one new column on `users`.

## The honesty point, upfront

Your spec explicitly says: *"Do not claim that AI can guarantee that a
government document is authentic... If automated verification cannot
confidently determine authenticity, send the application to an
administrator for manual review."*

I took that seriously. `OwnerVerificationService::runAutomatedChecks()`
only checks things a computer genuinely *can* verify:
- every **required** document type was actually submitted (defense in depth
  — the Form Request already enforces this before the service even runs)
- if an expiration date was provided, it isn't already expired

That's it. There is no OCR, no "AI reviews your ID," no confidence score on
whether a document looks real — because none of that would be honest to
claim. The practical result: every submission that passes the structural
checks lands on **`needs_review`**, meaning a human Super Admin always makes
the actual approve/reject call. The only things automation *can* fully
resolve on its own are the negative cases (missing required doc, expired
document) — routed to **`needs_additional_documents`** without waiting on
an admin, since there's nothing for a human to judge there.

## What's included

**Schema**
- `users.owner_verification_status` — fast-access cache of where an owner is
  in the pipeline (`not_submitted` → `needs_review` / `needs_additional_documents`
  / `rejected` ⇄ resubmit, or → `approved` → *(Step 20: OTP)* → `verified`).
  Null for every non-owner role.
- `owner_verifications` — one row **per submission attempt** (not
  overwritten), so a rejected-then-resubmitted owner has a real audit trail
  — matches your spec's "Previous verification attempts" requirement.
- `verification_documents` — per-document type, file, status, and optional
  expiration date.

**Access control**
- `EnsureOwnerIsVerified` middleware (alias `owner.verified`), now stacked
  onto the *existing* owner property-management route group from Step 3 —
  nothing duplicated. Only blocks accounts with the `owner` role; managers
  and Super Admin (who share some of those routes) are untouched.
- The verification page itself lives in a **separate, ungated** route group,
  since an unverified owner obviously still needs to reach it.

**Registration flow change**
- Registering as `owner` now sets `owner_verification_status = 'not_submitted'`
  and redirects straight to `/owner/verification` instead of the dashboard —
  matching your spec's flow diagram exactly. Tenant registration is
  unaffected.

**Owner-facing page** (`owner/verification/show.blade.php`)
- The progress checklist from your spec (Account Created / Documents
  Submitted / Verification Review / Email OTP / Account Activated).
- Upload form for all 7 document types from your spec, each labeled
  Required or Optional, with per-document expiration date fields.
- Status-specific messaging (including showing the admin's actual note when
  documents were rejected or more were requested).
- A dashboard banner (amber/red) reminding an unverified owner what's
  locked and linking back to this page.

**Admin review** (`admin/owner-verifications/`)
- Filterable list, detail page showing owner info, every submitted document
  (secure download link), and the full history of past attempts.
- **Approve** / **Reject** (reason required) / **Request Additional
  Documents** (note required) — exactly the three actions your spec names.

**Security**
- Documents stored on the private `local` disk, never a public URL.
- `VerificationDocumentController::download()` checks the requester is
  either the submitting owner or a Super Admin — tested explicitly with a
  *different* owner attempting to download someone else's document (403).
- File type/size limits enforced server-side (`mimes:jpg,jpeg,png,pdf`, 5MB)
  regardless of what the browser's `accept` attribute suggests.

**Tests** — 12 new cases covering the registration gate, the access-control
middleware (both directions), missing-required-document rejection, the
automated-expiry check, all three admin actions, the 403 on non-admin
review attempts, and the document-download authorization boundary.

## Install this step

1. Copy `app/`, `resources/views/`, `routes/web.php`, `bootstrap/app.php`,
   and `tests/` in (merge/overwrite).
2. Run the new migration:
   ```bash
   php artisan migrate
   ```
3. Run tests:
   ```bash
   php artisan test
   ```
4. Test the flow:
   - Register a **new** owner account — confirm you land on
     `/owner/verification` immediately, not the dashboard.
   - Try accessing `/owner/properties` directly — confirm you're bounced
     back to the verification page.
   - Submit only a Government ID (skip Proof of Ownership) — confirm a
     validation error, since it's required.
   - Submit both required documents — confirm the status becomes
     "Needs Review."
   - Log in as **Super Admin**, go to **Manage → Owner Verifications** —
     confirm the submission appears, click into it, view a document.
   - Click **Approve** — log back in as the owner, confirm the dashboard
     banner now says documents were approved, but `/owner/properties` is
     **still** blocked (correct — only `verified`, which Step 20's OTP
     produces, unlocks it).
   - Try the **Reject** and **Request Additional Documents** paths on a
     fresh submission too, and confirm the owner sees your note/reason.

## Next step

**Step 20 — OTP email verification**: the final link in the chain. Once an
admin approves (`owner_verification_status = 'approved'`), generate a
hashed, expiring, attempt-limited OTP, email it, and only flip the status
to `verified` — unlocking owner features — once the correct code is entered.
