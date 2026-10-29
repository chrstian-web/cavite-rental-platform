# Cavite Rentals — Defense & Deployment Guide

Two parts: **A** is the safe plan for defense day (runs on your own laptop).
**B** puts the same app online on Railway if your panel wants a public link.
Do A first. Do B only if you need it.

---

## A. Defense day, from your own laptop (free, most reliable)

### One week before
1. `git pull` and make sure everything you want to demo is **committed and pushed** (backup).
2. Fresh, clean demo data:
   ```
   php artisan migrate:fresh --seed
   ```
   This recreates the roles, permissions, locations, amenities, DSS criteria and the admin account.
3. Create demo accounts by hand and keep them in a note: one **tenant**, one **owner**
   (get the owner through the verification flow), plus the **admin**.
4. Add 5–10 realistic listings with photos, one with a virtual tour, one rental application,
   one viewing request, one contract with payments. An empty system demos badly.
5. Test every demo path end to end twice (register, search, apply, verify owner, approve,
   contract, payment, maintenance request, reports/DSS).

### The day before
- `php artisan optimize:clear` then `php artisan serve` (or your usual XAMPP/Laragon start).
- Test on your **phone** through ngrok: `ngrok http 8000`, then put that https URL in `.env` as `APP_URL`.
  The free ngrok URL changes each time you restart it, so update `APP_URL` after every restart.
- Charge the laptop, bring the charger, and keep a phone hotspot ready as a backup connection.
- **Record a screen video** of the full demo flow. If anything breaks live, play the video.
- Change the admin password from the default `ChangeMe123!`.

### On the day
- Start MySQL, `php artisan serve`, and ngrok **before** the panel enters.
- Close everything you don't need. Turn off notifications. Set browser zoom so text is readable.

---

## B. Put it online on Railway (optional)

> Railway detects Laravel automatically, installs Composer and Node dependencies, builds the Vite
> assets and serves the app. Costs: a one-time $5 trial credit, then about $5/month on the Hobby plan.
> A MySQL database adds to the usage, so plan for roughly $5–15 for the month of your defense.
> Check railway.com/pricing, because prices change.

This repo already contains what Railway needs: `railway.json` (runs `php artisan migrate --force`
before each deploy), a trusted-proxy switch, an admin seeder that reads its credentials from
environment variables, and automatic creation of the `public/storage` link.

### 1. Create the project
1. railway.com, sign in with GitHub, **New Project, Deploy from GitHub repo**, pick `cavite-rental-platform`.
2. In the same project click **+ New, Database, Add MySQL**.

### 2. Variables (app service, Variables tab)
Replace `MySQL` with the exact name of your database service if it differs.

```
APP_NAME=Cavite Rentals
APP_ENV=production
APP_DEBUG=false
APP_KEY=<paste the output of:  php artisan key:generate --show>
APP_URL=https://<your-app>.up.railway.app
TRUST_PROXIES=true

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stderr
MAIL_MAILER=log

ADMIN_EMAIL=<your admin email>
ADMIN_PASSWORD=<a strong password, NOT ChangeMe123!>
```

Never copy your local `.env` into Railway, because it holds your local database password.
`APP_URL` must be set after Railway gives you the domain (Settings, Networking, Generate Domain).

### 3. Keep uploads (important)
Railway wipes the container's disk on every deploy, which would delete property photos, payment
proofs and owner documents. Attach a **Volume** to the app service and mount it at:

```
/app/storage/app
```

That one mount covers both the public images and the private documents.

### 4. Seed once
After the first successful deploy, run the seeders **one time** (roles, permissions, locations,
amenities, DSS criteria, admin). Use the Railway CLI shell into the app service:

```
railway ssh
php artisan db:seed --force
```

Then log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

### 5. Check it
- `https://<your-app>.up.railway.app/up` returns OK.
- Log in, upload a property photo, redeploy, and confirm the photo is still there.

### Known limits
- Password-reset emails go to the log (`MAIL_MAILER=log`) and are **not delivered**. For a demo, create accounts
  directly. For real email use a provider (Mailtrap for testing, Resend, Brevo) and set the `MAIL_*` variables.
- No queue worker or scheduler is needed: the project has no queued jobs or scheduled tasks.
  `php artisan occupancy:reconcile` (if you use it) must be run by hand.
- Do not run `migrate:fresh` on the live database. It deletes everything.
