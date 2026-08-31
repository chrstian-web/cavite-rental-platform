# STEP 3 — Property & Rental-Space Management

Builds on Step 1 (schema) and Step 2 (auth/RBAC).

## What's included

**Services**
- `app/Services/PropertyService.php` — all property create/update/delete logic
  lives here, not in the controller. Wraps property + amenity sync + image
  upload in a DB transaction so a failed image upload never leaves a
  half-created property. Also generates a unique slug (`Str::slug`, checked
  against soft-deleted rows too) instead of trusting client input.

**Form Requests**
- `StorePropertyRequest` / `UpdatePropertyRequest` — validates property fields;
  `authorize()` calls the `PropertyPolicy` so ownership is checked server-side,
  never trusted from the request.
- `StoreRentalSpaceRequest` / `UpdateRentalSpaceRequest` — validates the common
  `rental_spaces` columns plus the type-specific `attributes.*` fields (floor,
  unit type, room type, gender restriction, curfew) using Laravel's dot-notation
  nested validation. `authorize()` checks the *property's* owner/manager, since
  a rental space's permissions are inherited from its parent property.

**Controllers**
- `Public\PropertyController` — `index` (filterable listing: type, location,
  budget) and `show` (full details + view counter). No auth required.
- `Owner\PropertyController` — CRUD scoped to properties the user owns or
  manages (`Property::where('owner_id', ...)` / `whereHas('managers', ...)`).
- `Owner\PropertyImageController` — delete an image, set a new cover photo.
- `Owner\RentalSpaceController` — full CRUD for units/rooms nested under a
  property, always re-checking `space->property_id === property->id` so a
  manager can't edit another owner's unit by guessing an ID.
- `Admin\PropertyController` — lists all properties with a status filter and
  lets a Super Admin verify/reject a listing (`PropertyPolicy::verify`).

**Views** (Blade + Tailwind, Alpine.js added for the one place it earns its
keep: toggling condo/boarding/dorm-specific fields on the rental-space form)
- `resources/views/properties/{index,show}.blade.php` — public listing/detail.
- `resources/views/owner/properties/{index,create,edit,_form}.blade.php`
- `resources/views/owner/properties/spaces/{index,create,edit,_form}.blade.php`
- `resources/views/admin/properties/index.blade.php`
- `resources/views/layouts/public.blade.php` — new layout for the public site
  (nav is different from the authenticated dashboard chrome).
- `layouts/app.blade.php` updated with nav links to "Browse Properties",
  "My Properties" (owner/manager), and "Verify Properties" (admin).

**Seeder**
- `database/seeders/AmenitySeeder.php` — a starter amenity catalog (Wifi, CCTV,
  Aircon, Parking, etc.) so the property form has real options immediately.

## Routes added

```
GET   /properties                          public listing
GET   /properties/{slug}                   public detail

# owner/manager/super_admin only:
GET|POST      /owner/properties            list / create
GET|PUT       /owner/properties/{id}/edit  edit
DELETE        /owner/properties/{id}
DELETE        /owner/properties/{id}/images/{image}
PATCH         /owner/properties/{id}/images/{image}/cover
GET|POST      /owner/properties/{id}/spaces
GET|PUT       /owner/properties/{id}/spaces/{space}/edit
DELETE        /owner/properties/{id}/spaces/{space}

# super_admin only:
GET     /admin/properties
PATCH   /admin/properties/{id}/verify
```

## Install this step

1. Copy `app/`, `database/seeders/`, `routes/web.php`, and `resources/views/`
   into your project (merge/overwrite).
2. Run the storage symlink (needed once, so uploaded images are web-accessible):
   ```bash
   php artisan storage:link
   ```
3. Seed the new amenity data:
   ```bash
   php artisan db:seed --class=AmenitySeeder
   ```
4. Test the flow:
   - Log in as your **owner** test account (register a new one with role
     "Property Owner" if you only have a tenant so far).
   - Go to **My Properties → + Add Property**, fill it in, upload a photo or two.
   - After saving you're redirected to **Units/Rooms** — add at least one unit.
   - Visit `/properties` while logged out — your new property won't appear
     yet (verification_status defaults to `pending`).
   - Log in as the Super Admin, go to **Verify Properties**, click **Verify**.
   - Log out and visit `/properties` again — it should now appear, and
     `/properties/{slug}` should show the full detail page with your photos,
     amenities, and unit list.

If that full loop works, property management + RBAC scoping is solid.

## Next step

**STEP 4 — Virtual Tour module**: 360°/panorama scene upload, a JS-based
viewer, and hotspot navigation between scenes, attached to a property (or a
specific rental space).
