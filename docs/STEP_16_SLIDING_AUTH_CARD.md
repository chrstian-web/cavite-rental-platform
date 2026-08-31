# STEP 16 — Sliding Sign In / Sign Up Card

Replaces the plain login/register pages with a single animated, two-panel
sliding card component, matching your design spec. No new migrations.

## What's included

**`public/css/auth-card.css`** — the external stylesheet (loaded via a plain
`<link>` tag, no build step needed). Implements every spec point: white card,
`border-radius: 30px`, `box-shadow: 0 14px 28px rgba(0,0,0,0.25)`, lavender
page background, deep purple (`#512da8`/`#4c2889`) overlay and buttons,
Poppins font (via Google Fonts `@import`), curved overlay corners
(`border-top-right-radius: 150px` / `border-bottom-right-radius: 100px`,
swapped to the opposite side when toggled), and the `0.6s cubic-bezier`
sliding/cross-fade transition on both the overlay and the forms.

**`resources/views/auth/card.blade.php`** — one shared view that both
`/login` and `/register` now render. Both forms exist in the DOM at once;
clicking the overlay's "SIGN IN"/"SIGN UP" buttons just toggles a CSS class
(`right-panel-active`) — no page reload, matching the spec's animation
requirement.

**Wired to your real backend, not just a mockup**:
- Both forms post to the actual `route('login')` / `route('register')` with
  `@csrf`, and show real Laravel validation errors under each field.
- The sign-up form keeps the fields your backend actually requires
  (First/Last name, Email, Password, Password confirmation, and a Tenant/Owner
  role picker) rather than the spec's minimal Name/Email/Password — a real
  account needs those regardless of the visual design.
- **A subtle bug I caught while building this and fixed before shipping it**:
  since both forms share one page and the visual toggle doesn't reload,
  submitting the sign-up form while visually sitting on `/login` (or vice
  versa) could redirect back to the wrong panel on a validation error. Fixed
  with a hidden `_form` field that tracks which form was *actually* submitted,
  so the correct panel always re-opens with its errors after a failed attempt.
- Failed validation and success messages show as **SweetAlert2 toasts**
  (matching Step 15), in addition to the inline red field errors.

## Honest simplifications from the original spec

- **Social login icons (Google/Facebook/GitHub/LinkedIn)** are rendered but
  intentionally non-functional (`cursor: not-allowed`, a "not available yet"
  tooltip) — wiring real OAuth is a substantial separate feature (registering
  apps with each provider, handling callback flows), not something to fake
  as if it works.
- **"Remember me"** was on the old login form but isn't in your spec's field
  list, so it's dropped here. Easy to add back as a checkbox if you want it.
- **Mobile/narrow screens**: a true side-by-side sliding animation doesn't
  work below ~680px width, so on small screens the overlay hides and a plain
  "Don't have an account? Sign up" text link switches between the two forms
  instead — still fully functional, just without the slide animation.

## Install this step

1. Copy `public/css/auth-card.css`, `resources/views/auth/card.blade.php`,
   and the two updated controllers
   (`app/Http/Controllers/Auth/RegisteredUserController.php`,
   `AuthenticatedSessionController.php`) in — merge/overwrite.
2. No migrations, no new packages.
3. Test:
   - Visit `/login` — should show the purple "Welcome Back!" panel on the
     right, sign-in form on the left.
   - Click "SIGN UP" in the overlay — panel should slide smoothly to the
     left with the curved corners flipping sides, sign-up form fades in.
   - Try submitting the sign-up form with a password that doesn't match its
     confirmation — should land back on the sign-up panel (not sign-in) with
     the error shown, both inline and as a toast.
   - Complete a real registration — should log you in and redirect to
     `/dashboard`, same as before.
   - Try logging in with a wrong password — same panel-correctness check.
   - Resize your browser below ~680px wide — confirm the mobile fallback
     (plain text link instead of the slide) works.
