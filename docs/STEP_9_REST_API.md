# STEP 9 — REST API for the Mobile App

Builds on every previous step — the API reuses the same Services
(`PropertyService`, `RentalApplicationService`, `ViewingRequestService`,
`MaintenanceRequestService`, `DssScoringService`) and Policies as the web
app, so business rules never diverge between the two front ends. No new
migrations (Sanctum's token table was already created back in Step 1).

## What's included

**Consistent envelope** (`app/Http/Controllers/Api/ApiController.php`)

Every response — success or error — follows the exact shape from the brief:

```json
{
  "success": true,
  "message": "Properties retrieved successfully.",
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 34 }
}
```

**API Resources** (`app/Http/Resources/`) — the only thing that ever leaves
the server. `PropertyResource` (list) vs `PropertyDetailResource` (single
property) deliberately differ: the list view stays lean for mobile bandwidth
(no description, amenities, or unit list), the detail view has everything.
No resource ever includes a password hash, internal foreign keys the client
doesn't need, or soft-delete timestamps — matching "do not expose sensitive
database fields through the API."

**Auth** (`Api\V1\Auth\AuthController`) — Sanctum token issuing:
- `POST /api/v1/auth/register` — tenant-only self-registration from mobile
  (owners still register via the web app, matching the web `RegisterRequest`
  restriction from Step 2).
- `POST /api/v1/auth/login` — accepts an optional `device_name`, returns a
  plain-text bearer token tied to that device (so a user can be logged in on
  phone + tablet simultaneously, each revocable independently).
- `POST /api/v1/auth/logout` — revokes only the *current* token, not all of
  the user's devices.
- `GET/PATCH /api/v1/auth/profile`.

**Endpoints**, matching the structure from the brief:

| Endpoint | Notes |
|---|---|
| `GET /properties`, `GET /properties/{slug}` | Public, same filters as the web listing |
| `POST/DELETE /favorites/{property}` | Toggle-style, same duplicate prevention as web |
| `POST /comparisons` | `{"property_ids":[1,2,3]}`, 2–4 properties |
| `POST /recommendations` | Same `DssScoringService` as the web "Recommended for You" — identical scoring, identical weights |
| `GET /applications`, `POST /rental-spaces/{space}/applications` | Multipart document upload supported |
| `GET /viewings`, `POST /properties/{property}/viewings` | |
| `GET /contracts`, `GET /contracts/{contract}` | Includes a `pdf_url` pointing at the same authenticated web download route |
| `GET /payments` | |
| `GET /maintenance`, `POST /contracts/{contract}/maintenance` | |
| `GET /notifications`, `PATCH /notifications/{id}/read`, `PATCH /notifications/read-all` | Same `database` notifications created by Step 5/6's Notification classes |

**Security**
- `auth:sanctum` middleware on every endpoint except register/login/public
  property browsing.
- Every `show`/`store` re-checks the same Policy the web controllers use
  (`$request->user()->can('view', $application)`, etc.) — an API token
  can never see or act on data the same user couldn't reach through the
  website.
- Rate limiting: 60 requests/minute per authenticated user (or per IP for
  public endpoints), registered as the `api` limiter in `AppServiceProvider`.
- `bootstrap/app.php` forces JSON error responses (validation errors, 404s,
  401s) for every `/api/*` request — a mobile client never has to parse an
  HTML error page.

## Install this step

1. Copy `app/`, `resources/views/` (Resources live under `app/`, not views —
   no new Blade files this step), `routes/api.php`, and `bootstrap/app.php`
   in (merge/overwrite `bootstrap/app.php` and `AppServiceProvider.php`).
2. No new migrations/seeders.
3. **Test without a mobile app**, using `curl` (or import into Postman/Insomnia
   if you prefer a GUI):

   ```bash
   # Register a test mobile user
   curl -X POST http://127.0.0.1:8000/api/v1/auth/register ^
     -H "Accept: application/json" ^
     -d "first_name=Mobile&last_name=Tester&email=mobile@test.com&password=Password123!&password_confirmation=Password123!"

   # Copy the "token" from the response, then:
   curl http://127.0.0.1:8000/api/v1/auth/profile ^
     -H "Accept: application/json" ^
     -H "Authorization: Bearer PASTE_TOKEN_HERE"

   # Browse properties (no auth needed)
   curl http://127.0.0.1:8000/api/v1/properties -H "Accept: application/json"

   # Get recommendations (auth needed)
   curl -X POST http://127.0.0.1:8000/api/v1/recommendations ^
     -H "Accept: application/json" -H "Authorization: Bearer PASTE_TOKEN_HERE" ^
     -d "max_budget=15000&property_type=boarding_house"
   ```
   (On Windows CMD/PowerShell, `^` line continuation may not work directly in
   PowerShell — either paste each `curl` command as one line, or use
   Postman, which handles this more comfortably for repeated testing.)

4. Confirm: every response is valid JSON with `success`/`message`/`data`/`meta`
   keys; hitting an authenticated endpoint *without* a token returns a 401
   JSON error, not an HTML login redirect page (that would mean a mobile app
   trying to parse it would crash — this is the most important thing to
   verify from this step).

## Configuring the mobile app's API base URL

Whatever mobile framework is chosen later (Flutter/React Native/native), it
points at `http://YOUR_MACHINE_IP:8000/api/v1` during development — not
`127.0.0.1`, since that resolves to the phone/emulator itself, not your
computer, when testing on a physical device or most emulators. For a real
deployment, this becomes `https://yourdomain.com/api/v1`.

## Next step

Given how much of the original brief is now covered, worth deciding next:
**Step 10 (automated tests)**, **Step 11 (reporting/export module)**, or
polishing/hardening what already exists (e.g. adding the missing
sort-by-distance/most-viewed options to the public search from Step 3).
