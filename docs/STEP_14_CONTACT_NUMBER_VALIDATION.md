# STEP 14 — Contact Number Validation (11 digits)

Small, focused change across every place a phone/contact number is
collected. No new migrations.

## What's included

**Server-side validation** — `digits:11` (Laravel's built-in rule: must be
numeric and exactly 11 characters long, so `09171234567` passes but
`0917123456` — one short — or `0917-123-4567` — has dashes — both fail)
applied to:

| Field | Form Request |
|---|---|
| `phone` | `Auth\RegisterRequest` (registration) |
| `phone` | `ProfileUpdateRequest` (profile edit) |
| `contact_number` | `Property\StorePropertyRequest` / `UpdatePropertyRequest` |
| `emergency_contact_number` | `RentalApplication\StoreRentalApplicationRequest` |

This is the actual enforcement — even if someone bypasses the browser
entirely (a direct API call, a modified form), the server rejects anything
that isn't exactly 11 digits.

**Matching HTML input hints** (not a substitute for the server validation
above, just better UX): `inputmode="numeric"` (brings up the number pad on
mobile), `maxlength="11"`, `pattern="[0-9]{11}"`, a placeholder showing the
expected format (`09171234567`), and a small helper line under each field.
Updated in: registration, profile edit, the property create/edit form, and
the rental application form's emergency contact field.

**Tests** — three new cases in `RegistrationTest.php`: a 10-digit number is
rejected, a number with dashes is rejected, and a correctly-formatted
11-digit number is accepted and actually saved.

## Install this step

1. Copy `app/Http/Requests/`, `resources/views/`, and `tests/` in
   (merge/overwrite the five specific files listed above — no need to
   replace anything else).
2. No new migrations.
3. Run tests:
   ```bash
   php artisan test
   ```
   Should show 65 passing (62 + 3 new).
4. Manual check: try registering with a phone number that's too short, or
   has letters/dashes in it — should show a validation error. Try
   `09171234567` — should go through cleanly.

## Scope note

This applies to `phone`, `contact_number`, and `emergency_contact_number` —
the three explicit "contact number"-type fields in the app. It does **not**
touch other free-text fields (like `contact_person`) or the tenant's
emergency contact *relationship* field, which are correctly left as
unrestricted text.
