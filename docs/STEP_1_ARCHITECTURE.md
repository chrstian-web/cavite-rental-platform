# STEP 1 — Laravel Project Architecture & Database Schema

Cavite Rental Management Platform (Condominium / Boarding House / Dormitory)
Laravel 12 · PHP 8.3 · MySQL 8

This is the first of several incremental delivery steps (per the master prompt's
own "do not build everything in one response" rule). It establishes the
**database schema and Eloquent models** that every later module (auth, search,
virtual tours, DSS, applications, contracts, payments, API) will build on top of.

---

## 1. Key architectural decisions

### 1.1 One unified `rental_spaces` table, not three
Condominium units, boarding-house rooms, and dormitory beds are all just
"a space inside a property that can be rented out." Rather than three parallel
schemas, `rental_spaces` holds every common, *searchable* attribute as a real
column (bedrooms, bathrooms, capacity, monthly rent, furnished, status), and
pushes type-specific extras (floor, gender restriction, curfew, unit type,
utilities) into a JSON `attributes` column. This means:

- Search/filter/sort queries stay simple SQL on indexed columns.
- Adding a new property type or a new type-specific field later never requires
  a schema migration or touching unrelated code.
- One `RentalSpace` model, one `RentalSpaceController`, one set of policies —
  no code duplication across three parallel modules (matches the DRY
  requirement in the brief).

### 1.2 RBAC via `roles` + `permissions` (not hard-coded role strings)
`users.role_id` points at a `roles` row (`super_admin`, `owner`, `tenant`,
`manager`). A `permission_role` pivot lets an admin grant/revoke granular
capabilities per role without redeploying code. `Property Manager` access to
specific properties is modeled separately via `property_manager_assignments`
(many-to-many), since a manager is assigned to a subset of properties, not
"all properties."

### 1.3 DSS is data-driven, not hard-coded
`dss_criteria` lists the scoring dimensions (budget, location, amenities,
property type, capacity, distance, furnishing). `dss_weights` stores the
*current* percentage weight and scoring rules (min requirement, preferred
value) per criterion — editable by Super Admin, versioned (old rows just get
`is_active = false` instead of being deleted, so a score computed last month
stays explainable). `dss_scores` persists a computed score per user/property
along with a JSON breakdown and human-readable `reasons`, so the "why was
this recommended" explanation is stored, not regenerated from guesswork.

### 1.4 Locations are data, not code
`locations` (province + city/municipality) and `barangays` are plain tables,
seeded with the 20+ Cavite municipalities from the brief — nothing about
Cavite is hard-coded into controllers or validation rules, so the platform
could expand to another province by adding rows.

### 1.5 Soft deletes on high-value records
`users`, `properties`, and `rental_spaces` use soft deletes so historical
contracts/payments/reviews tied to a since-removed listing or account remain
intact for reporting.

### 1.6 Every "who owns this" relationship is server-verifiable
`properties.owner_id`, `rental_contracts.owner_id`/`user_id`,
`payments.user_id`, etc. are always foreign keys resolved server-side in
later steps (Policies) — never trusted from client input, per the security
requirements in the brief.

---

## 2. Entity-relationship summary

```
roles ─┬─< users ─┬─< properties (owner_id) ──┬─< rental_spaces ──┬─< rental_applications
       │          │                           │                   ├─< rental_contracts ──< payments
       └─< permissions (via permission_role)   │                   ├─< viewing_requests
                   │                           │                   └─< maintenance_requests
                   ├─< favorites               ├─< property_images
                   ├─< comparisons ──< comparison_items
                   ├─< rental_applications      ├─< amenities (via amenity_property)
                   ├─< viewing_requests          ├─< virtual_tours ──< virtual_tour_scenes ──< scene_hotspots
                   ├─< rental_contracts          └─< reviews
                   ├─< payments
                   ├─< maintenance_requests
                   ├─< reviews
                   └─< dss_scores

locations ─┬─< barangays
           └─< properties

dss_criteria ─< dss_weights          dss_scores (user_id, property_id, rental_space_id)
```

---

## 3. Tables created in this step

| Table | Purpose |
|---|---|
| `roles`, `permissions`, `permission_role` | RBAC |
| `users`, `password_reset_tokens`, `sessions` | Auth + accounts |
| `personal_access_tokens` | Sanctum tokens for the mobile API |
| `locations`, `barangays` | Cavite municipalities/barangays (data-driven) |
| `amenities` | Reusable amenity catalog |
| `properties`, `property_images`, `amenity_property` | Property Management module |
| `property_manager_assignments` | Property Manager ↔ Property scoping |
| `rental_spaces` | Unified condo unit / boarding room / dorm room/bed |
| `virtual_tours`, `virtual_tour_scenes`, `scene_hotspots` | Virtual Tour module |
| `favorites`, `comparisons`, `comparison_items` | Wishlist & comparison |
| `rental_applications`, `application_documents`, `viewing_requests` | Rental process |
| `rental_contracts`, `payments` | Contracts & payment tracking |
| `maintenance_requests`, `maintenance_request_images` | Maintenance module |
| `reviews` | Ratings & reviews (unique per completed contract) |
| `notifications` | Laravel's built-in notifications table |
| `dss_criteria`, `dss_weights`, `dss_scores` | Decision Support System |

All migrations live in `database/migrations/`, timestamped in dependency
order so `php artisan migrate` runs cleanly. All 26 Eloquent models live in
`app/Models/`, each with the relationships listed in section 24 of the brief
(and the additional ones needed for a working app, e.g. `Property::images()`,
`RentalSpace::rentalContracts()`).

---

## 4. What's intentionally deferred to later steps

- Form Requests, Policies, Controllers, Livewire components, Blade views —
  **Step 3 onward** (Property & Rental-Space management), per the phased
  build order in the brief.
- Seeders/Factories with realistic Cavite data — will ship alongside the
  modules that need them so seed data and business logic stay in sync.
- `DssScoringService` — **Step 7**, once `dss_criteria`/`dss_weights` have
  real seeded rows to compute against.
- `routes/web.php`, `routes/api.php` — introduced as each module lands.

---

## 5. How to install this step into a fresh Laravel 12 project

```bash
composer create-project laravel/laravel cavite-rental-platform
cd cavite-rental-platform
composer require laravel/sanctum
```

Then copy the contents of this delivery into the new project:

```bash
cp database/migrations/*.php  path/to/cavite-rental-platform/database/migrations/
cp app/Models/*.php           path/to/cavite-rental-platform/app/Models/
```

Configure `.env` with your MySQL credentials, then:

```bash
php artisan migrate
```

You should see all 20 tables (plus Laravel's default `cache`/`jobs` tables if
you keep those migrations) created with no foreign-key errors — the
migration filenames are ordered so every `constrained()` call always points
at a table that already exists.

---

## Next step

**STEP 2 — Authentication and RBAC**: registration/login/logout, email
verification, password reset, Form Requests, the `RoleMiddleware`/Policies
that enforce Super Admin / Owner / Tenant / Manager access, and the seeders
for the four roles + a default Super Admin account.

Reply "continue" or "step 2" when you're ready and I'll build it on top of
this schema.
