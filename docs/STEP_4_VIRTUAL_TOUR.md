# STEP 4 — Virtual Tour Module

Builds on Steps 1–3. Uses the `virtual_tours` / `virtual_tour_scenes` /
`scene_hotspots` tables from Step 1 — no new migrations needed.

## What's included

**Viewer**: [Pannellum](https://pannellum.org/) (lightweight, free, MIT-licensed
JS 360° panorama viewer built on WebGL — a real panorama renderer, not a
slideshow), loaded via CDN only on pages that need it
(`@push('head')` / `@stack('head')`), not globally.

**Immersive Street-View-style experience** (`resources/views/partials/virtual-tour-viewer.blade.php`)
Shared by both the owner's preview and the public property page, so what an
owner previews is exactly what a renter sees. Includes:
- An inline embedded viewer plus a **"View Fullscreen Tour"** button that
  opens a true full-viewport immersive overlay (not just Pannellum's native
  fullscreen toggle, which just maximizes the existing box — this covers the
  whole screen, dims everything else, and disables page scroll).
- A clear **"✕ Exit Virtual Tour"** button in the overlay, plus `Esc` key support.
- Built-in **zoom controls** (buttons + scroll wheel + pinch-to-zoom on mobile —
  Pannellum handles touch natively, no extra code needed).
- A **room/scene selector** — a row of buttons (Entrance, Living Room, Kitchen…)
  that jump straight to any scene, so navigation isn't hotspot-only.
- A **room name overlay** in the fullscreen view that updates on every scene change.
- Navigation hotspots styled as **blue arrow markers** instead of Pannellum's
  default plain dot, so moving between scenes visually reads like Street View's
  directional arrows.
- A built-in **loading indicator** (Pannellum shows one automatically while a
  panorama loads) and **smooth cross-fade transitions** between scenes
  (`sceneFadeDuration`).

**Scope note on the floor-projected arrows**: a literal arrow *rendered onto
the 3D floor plane* (exactly matching Google Street View's look) requires
custom Three.js geometry — placing a textured plane in 3D space at the
hotspot's world position. That's meaningfully more engineering than a
CDN-based panorama viewer gives you out of the box, and most real-world
rental/real-estate tour products (Matterport, Zillow 3D Home, etc.) actually
use a floating 2D marker over the 3D view, same as what's implemented here.
If floor-projected arrows matter for the thesis defense specifically, that's
a scoped follow-up using `three.js` directly instead of Pannellum — worth
noting in your documentation as a deliberate trade-off, not an oversight.

**Services**
- `app/Services/VirtualTourService.php` — tour/scene/hotspot creation, image
  storage, and a `publish()` guard that refuses to publish a tour with zero
  scenes. When a scene is deleted, any hotspot in *other* scenes that pointed
  at it gets its `target_scene_id` nulled out instead of leaving a dangling
  reference (so the viewer never tries to jump to a scene that no longer
  exists).

**Form Requests**
- `StoreVirtualTourRequest`, `StoreSceneRequest` (panorama image required on
  create, optional on edit — keeps the existing image if you don't replace
  it), `StoreHotspotRequest` (validates yaw `-180..180` / pitch `-90..90`,
  matching Pannellum's coordinate system 1:1 with the `position_x`/`position_y`
  columns from Step 1).

**Controllers**
- `Owner\VirtualTourController` — create the tour shell for a property, and
  publish it (only once ≥1 scene exists).
- `Owner\VirtualTourSceneController` — add/update/delete scenes, each
  double-checked against both the property *and* the tour it claims to
  belong to (`abort_unless($tour->property_id === $property->id && ...)`),
  so a manager can't attach a scene to someone else's tour by editing a URL.
- `Owner\SceneHotspotController` — add/delete hotspots on a scene, same
  ownership chain-checking.

**Views**
- `resources/views/partials/virtual-tour-viewer.blade.php` — the shared
  immersive viewer described above.
- `resources/views/owner/properties/tour/index.blade.php` — create the tour,
  upload scenes, add hotspots (numeric yaw/pitch — see note below), and
  preview using the same shared viewer renters will see.
- `resources/views/properties/show.blade.php` — renders the same viewer
  publicly, but **only if the tour's status is `published`**. A tour with
  scenes still in `draft` never appears to renters.

## A deliberate simplification (documented, not hidden)

Placing hotspots is done by typing yaw/pitch numbers rather than clicking
directly on the 360° preview to drop a pin. This keeps Step 4's JS footprint
small and framework-free. If you want click-to-place for the finished thesis
system, that's a contained upgrade: Pannellum fires a `mousedown` event with
`.mouse2coord(event)` that returns `{pitch, yaw}` — you'd wire that to fill
the two number inputs automatically instead of typing them. Worth flagging in
your thesis documentation as a known enhancement path either way.

## Install this step

1. Copy `app/`, `resources/views/`, and `routes/web.php` in (merge/overwrite).
2. No new migrations or seeders this step.
3. Test the flow:
   - As an **owner**, go to **My Properties → Virtual Tour** for your test
     property.
   - Create the tour (title/description).
   - Add a scene — any wide/panoramic photo works for testing, even if it's
     not a true 360° equirectangular image (it'll just look stretched).
   - Add a second scene, then add a hotspot on scene 1 pointing to scene 2
     (try yaw `0`, pitch `0` to start — dead center of the view).
   - You should see a **Preview** pannellum viewer appear above the scene
     list. Click and drag inside it, and click the hotspot icon to jump
     scenes.
   - Click **Publish tour**.
   - Visit the property's **public** page (`/properties/{slug}`) — the same
     viewer should now appear there, right under the photo gallery.

If the public viewer loads and scene-jumping works, Step 4 is fully wired.

## Next step

**STEP 5 — Rental process**: rental applications, document uploads, and
viewing/appointment requests — the first place a tenant actually *acts* on
a listing rather than just browsing it.
