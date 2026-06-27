# PGC Document Tracking System (DTS)

QR-based document tracking for the Provincial Government of Cavite.
Production URL: dts.cavite.gov.ph

## Tech stack

- **Laravel 12** (PHP 8.x) — Blade + Alpine.js + Tailwind CSS 3
- **MySQL** database `pgc_dts` (Laragon dev, IIS + Cloudflare Tunnel production)
- **spatie/laravel-permission** — roles + direct per-user permissions; `Gate::before` for Super Admin bypass
- **barryvdh/laravel-dompdf** — PDF generation (QR images, E-Record report)
- **simplesoftwareio/simple-qrcode** — QR code generation
- Vite for frontend build

## Folder structure

```
app/
  Http/Controllers/    — DocumentController, ReportController, AccountingController, WorkCalendarController, etc.
  Models/              — Document, Department, Division, Fund, CalendarDay, TrackingSequence, etc.
  Services/            — BusinessHours (working-time engine), DocumentService (encode/receive/transfer)
config/version.php     — version number + release date (shown in sidebar + changelog)
database/migrations/   — 000001–000040 (sequential, never renumber)
database/seeders/      — PhilippineHolidaySeeder (run once manually, not in deploy.ps1)
resources/views/       — Blade templates (layouts/, documents/, reports/, work-calendar/, departments/, etc.)
routes/web.php         — all routes
deploy.ps1             — production deploy script (git pull → composer → npm build → migrate → cache → iisreset)
```

## Key conventions

- **No hardcoded office names/codes.** Identify special offices via DB flags (`is_accounting` on departments, `is_hospital` on divisions). Never match strings like 'PACCO' or 'OPAcc' in code.
- **Dropdown placeholders.** Every `<select>` gets a `"— Select X —"` disabled first option.
- **Tailwind classes.** Use `.input` for text inputs, `.input-btn` for button triggers (with explicit padding), `.label`, `.field-k`. Custom components: `<x-search-select>`, `<x-file-drop>`, `<x-btn>`, `<x-card>`, `<x-badge>`, `<x-stat-card>`.
- **Per-fund annual sequences.** Each fund has its own sequence (starts at 1, resets yearly). Hospital funds run separately (`F{id}H`). Atomic via DB transaction + `lockForUpdate` on `TrackingSequence`.
- **Working-hours engine.** `BusinessHours::secondsBetween()` for all time calculations (holding, idle, turnaround). Age stays calendar time.
- **Hospital flag.** `documents.is_hospital` is set at encode time from the encoder's division. Never inferred from tracking-code text or mutable division FK.
- **Document types.** All types are global. Offices can be restricted to a subset via `restricted_doc_types` JSON on `departments`. Accounting offices auto-limited to Voucher/Payroll.
- **Extra accounting fields** (Amount, Fund, OBR, Responsibility Center, Nature) gated by `is_accounting` toggle on the department, not the document type.
- **Accounting Setup** visible to Super Admin only (`@role('Super Admin')`), not `@can`.
- **Version bumping.** Always check `git log --oneline` and `git tag --list` BEFORE choosing a version number. Never reuse a version.
- **Seeders.** `deploy.ps1` runs migrations only, never seeders. Seeders are one-time manual runs.

## Deployment

### Dev machine (D:\PGC)
```cmd
cd /d D:\PGC
git add -A
git commit -m "vX.X.X - <description>"
git push origin main
git tag -f vX.X.X
git push origin vX.X.X --force
```

### Server (C:\inetpub\wwwroot\dts)
```cmd
cd /d C:\inetpub\wwwroot\dts
mysqldump -u root -p pgc_dts > C:\backups\pgc_dts_before-X.X.X.sql
powershell -ExecutionPolicy Bypass -File .\deploy.ps1
```

**Always run mysqldump before deploy.ps1. No exceptions.**

deploy.ps1 does: git pull → composer install → npm install → npm run build → php artisan migrate --force → optimize:clear → config/route/view cache → iisreset

### After every completed version bump
1. Bump `config/version.php` and append to `CHANGELOG.md` FIRST — before giving deploy commands.
2. Then output the exact deployment commands for both machines with the real version number and commit message filled in.
3. Never give deploy commands before the version bump commit is ready — the user will push what's on disk.

## Things to never do

- **Never deploy without running `php artisan route:list` AND loading every changed route in the browser first.** A `view:cache` success does NOT guarantee runtime correctness — it only checks Blade syntax, not PHP fatal errors in controllers.
- Never use PHP array destructuring with default values (`[$a, $b = false]`) — PHP does not support this. Use explicit index access (`$field[2] ?? false`) instead.
- Never hardcode office/department names or codes — use DB flags
- Never infer hospital status from tracking-code text or current division FK
- Never skip version-number collision check
- Never add seeders to deploy.ps1
- Never use `@can('accounting.manage')` for Accounting Setup nav — use `@role('Super Admin')`
- Never use `.input` class on `<button>` elements — use `.input-btn`
- Never introduce features, abstractions, or cleanup beyond what was asked
- Never let "small fixes" compromise data accuracy — get calculations right the first time

## Design principles

- Clean, modern, organized UI
- Full-width layouts for forms and reports
- Auto-fitting grids with `[grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]`
- Super-Admin toggles for optional features
- Mandatory reason + audit log for leave/undertime entries (anti-circumvention)
