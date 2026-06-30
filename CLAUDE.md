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

### Changelog entries: keep them general, not technical
Same format as always (subject line + bullets), but keep each bullet general and plain — explain what changed and why it matters, properly enough to understand, without diving into implementation detail. Skip file paths, method/class names, migration names, and multi-clause technical breakdowns.

## Things to never do

- **Never deploy without running `php artisan route:list` AND loading every changed route in the browser first.** A `view:cache` success does NOT guarantee runtime correctness — it only checks Blade syntax, not PHP fatal errors in controllers.
- Never use PHP array destructuring with default values (`[$a, $b = false]`) — PHP does not support this. Use explicit index access (`$field[2] ?? false`) instead.
- Never hardcode office/department names or codes — use DB flags
- Never infer hospital status from tracking-code text or current division FK
- Never skip version-number collision check
- Never add seeders to deploy.ps1
- Never use `@can('accounting.manage')` for Accounting Setup nav — use `@role('Super Admin')`
- Never use `.input` class on `<button>` elements — use `.input-btn`
- **Never nest a `<button>` inside another `<button>`** (e.g. a clear/"×" control inside a dropdown trigger). This is invalid HTML — the browser silently closes the outer button early and hoists the inner one out of the DOM, breaking layout (the classic symptom: a clear/caret icon rendering misaligned or below its trigger instead of inside it). Always make the clear button an absolutely-positioned **sibling** of the trigger button inside a shared `position: relative` wrapper, never a child.
- Never introduce features, abstractions, or cleanup beyond what was asked
- Never let "small fixes" compromise data accuracy — get calculations right the first time
- **Never trust that custom Alpine/Blade UI "looks right" just because it compiles.** `view:cache` and `npm run build` only catch syntax errors, not broken layouts (invalid HTML nesting, misaligned flex children, etc.). For any new or edited dropdown/picker/form component, mentally trace the rendered DOM (or actually load the page) before calling it done.

## Dropdown / picker conventions

- **Searchable by default.** Any dropdown selecting from a list of users, departments, or divisions (system-wide, not just per-office short lists) must be searchable, not a plain `<select>` — these lists grow over time.
- **Shared pattern.** Use `<x-search-select>` for a flat single-value picker, `<x-rc-picker>`-style inline Alpine blocks for dependent/cascading pairs (e.g. Department → Division). Trigger button: `.input-btn flex items-center justify-between text-left pr-8` with a `relative` wrapper. Chevron icon sits inside the trigger (decorative `<svg>`, not interactive) and rotates via `:class="open && 'rotate-180'"`. The clear ("×") button is always a sibling, absolutely positioned at `right-7 top-1/2 -translate-y-1/2` — never nested inside the trigger (see "Never nest a button" above).
- **Always offer a way to clear a selection** once something is picked, unless the field is genuinely mandatory and has no "none" state.
- **Optional fields look optional.** Use `<span class="text-gray-400 text-xs font-normal">(optional)</span>` next to the label instead of a red asterisk when a field isn't required.

## Design principles

- Clean, modern, organized UI
- Full-width layouts for forms and reports
- Auto-fitting grids with `[grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]`
- Super-Admin toggles for optional features
- Mandatory reason + audit log for leave/undertime entries (anti-circumvention)
- **Audit trail detail.** Every `ActivityLog::record()` call must include the most specific details possible — names, titles, and IDs (e.g. `"Deleted a holiday: Independence Day on 2026-06-12 (#23)"`), never just IDs or generic labels. For settings changes, log exact diffs (`"Title "X" → "Y"; Show totals ON → OFF"`). Action labels must be plain English readable by non-technical staff. Log inline from controllers (not the generic middleware) when detail matters.
