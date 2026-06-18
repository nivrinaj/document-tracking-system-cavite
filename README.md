# PGC Document Tracking System (DTS)

A QR-code–based document tracking system for a department with four divisions
(**ISDA**, **Administrative**, **ICT**, **ETD**) plus a Department Head and
Assistant Department Head.

Built with **Laravel 12 + MySQL + Tailwind CSS**. Mobile and desktop friendly,
with dark mode, theming, role-based access control, PDF reports, and an in-app
documentation module.

---

## ✨ Features

- **Dashboard** — role-aware queues (to receive / in your hands / drafts to release), stats, activity feed.
- **Document Tracking** — encode incoming documents, auto-generate a unique tracking code + **QR code**, assign to any staff, **Release**, and **print** the QR slip.
- **Voucher support** — pick the *Voucher* type and the voucher number becomes the tail of the code (`PGC-2026-{voucher}`) for at-a-glance tracking; other types use a random suffix.
- **QR scan flow** — staff scan with their phone camera, log in, and **Receive** → then **Forward** (reason required) or **Archive/Complete** (details required). Wrong user sees *“QR code not found.”* Desktop receiving is **off by default** (mobile-first) and can be enabled in System Settings.
- **Elapsed-time tracking** — document age, idle time (colour-coded), turnaround, and per-action timestamps.
- **Charts** — incoming trend, status and priority breakdowns on the dashboard; summary charts on reports.
- **Full history / audit trail** for every document; all concerned staff (and the Heads) can track where it is.
- **Users**, **Divisions**, **Roles & Permissions** modules (full CRUD).
- **Logs & History**, **Reports** (with **PDF** export and recommended report types).
- **System Settings** — change theme color, upload a logo, app name, footer; plus a global **dark mode** toggle.
- **In-app Documentation** module (Markdown) — add/update/delete guides yourself.
- Runs on **IIS** (a `web.config` is included) or any standard Laravel host.

---

## 🚀 Quick Start (Development)

```bash
# 1. Install dependencies (already done once)
composer install
npm install

# 2. Configure your database in .env  (defaults shown)
#    DB_DATABASE=pgc_dts   DB_USERNAME=root   DB_PASSWORD=

# 3. Create the database, then build everything
php artisan migrate:fresh --seed
php artisan storage:link
npm run build         # or `npm run dev` while developing

# 4. Run it
php artisan serve
# open http://localhost:8000
```

### Demo accounts (password: `password`)

| Email | Role |
|-------|------|
| superadmin@pgc.test | Super Admin |
| head@pgc.test | Department Head |
| asst.head@pgc.test | Assistant Department Head |
| receiving@pgc.test | Receiving Staff |
| isda.staff@pgc.test / ict.staff@pgc.test / etd.staff@pgc.test / admin.staff@pgc.test | Staff |

> ⚠️ Change these passwords before going live (edit `database/seeders/UserSeeder.php`).

---

## 🖥️ Deploying on IIS

A ready-to-use `public/web.config` is included. Summary:

1. Enable **IIS** with **CGI**, and install the **URL Rewrite** module.
2. Install/register **PHP** for Windows (PHP Manager is easiest) and **point the IIS site's physical path at the `public/` folder**.
3. Give the app-pool user write access to `storage/` and `bootstrap/cache/`.
4. In `.env` set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL=http://<server-name-or-IP>`.
   The `APP_URL` **must be reachable by phones** because the QR encodes `APP_URL/track/...`.
5. Run `php artisan migrate --force`, `php artisan storage:link`, `php artisan config:cache`, `php artisan route:cache`.
6. Run `npm run build` so `public/build/` exists.

Full, beginner-friendly instructions are also inside the app under
**Documentation → Deployment**, including a complete step-by-step guide for
**transferring to a fresh Windows server and publishing it via a Cloudflare Tunnel**
(e.g. `https://dts.cavite.gov.ph`). The app already trusts the Cloudflare proxy and
forces HTTPS links, so the printed QR codes point at the public HTTPS hostname.

---

## 🧩 Tech stack

| Concern | Package |
|---|---|
| Framework | Laravel 12 (PHP 8.3) |
| Auth scaffolding | laravel/breeze (Blade + Alpine.js) |
| Roles & permissions | spatie/laravel-permission |
| QR codes | simplesoftwareio/simple-qrcode (SVG — no imagick needed) |
| PDF | barryvdh/laravel-dompdf |
| CSS | Tailwind CSS 3 |

---

## 📁 Where things live

```
app/Http/Controllers/   One controller per module
app/Models/             Document, Division, User, DocumentLog, Setting, DocumentationPage
app/Policies/           DocumentPolicy — the "who can do what to a document" rules
app/Services/           DocumentService — the workflow (assign/release/receive/forward/archive)
database/migrations/    Table definitions
database/seeders/       Roles, divisions, demo users, demo documents, in-app docs
resources/views/        Blade templates (one folder per module)
resources/views/layouts/app.blade.php   Main dashboard layout (sidebar, dark mode, theming)
resources/views/components/             Reusable UI (btn, card, badge, nav-item, stat-card)
routes/web.php          All routes
public/web.config       IIS configuration
```

> New to Laravel? Open **Documentation** inside the app — it has step-by-step guides
> for adding a field, adding a module, and understanding roles & permissions.

---

## 🔮 Roadmap (planned)

- Each division can run its own copy of the system.
- Interconnect departments so documents can be tracked across the whole organization.
