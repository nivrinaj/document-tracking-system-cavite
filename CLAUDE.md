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
- **Tailwind classes.** Use `.input` for text inputs, `.input-btn` for button triggers (with explicit padding), `.label`, `.field-k`. Custom components: `<x-search-select>`, `<x-file-drop>`, `<x-btn>`, `<x-card>`, `<x-badge>`, `<x-stat-card>`, `<x-toggle>`. Use `<x-toggle>` (a modern sliding switch) for on/off settings instead of bare checkboxes; it works as a normal form field (`name=` → hidden 0 + checkbox 1) and with Alpine (`x-model`). Pass checked state as `:checked="expr"` (never `@checked(...)` — that directive breaks inside a component tag).
- **Per-fund annual sequences.** Each fund has its own sequence (starts at 1, resets yearly). Hospital funds run separately (`F{id}H`). Atomic via DB transaction + `lockForUpdate` on `TrackingSequence`.
- **Working-hours engine.** `BusinessHours::secondsBetween()` for all time calculations (holding, idle, turnaround). Age stays calendar time.
- **Hospital flag.** `documents.is_hospital` is set at encode time from the encoder's division. Never inferred from tracking-code text or mutable division FK.
- **Document types.** All types are global. Offices can be restricted to a subset via `restricted_doc_types` JSON on `departments`. Accounting offices auto-limited to Voucher/Payroll.
- **Extra accounting fields** (Amount, Fund, OBR, Responsibility Center, Nature) gated by `is_accounting` toggle on the department, not the document type.
- **User names.** Users have structured `first_name`, `last_name` (required) and optional `middle_name`; the full `name` column is composed from them on save (`User::composeName()`) and stays the display value everywhere. `User::formalName()` gives "Surname, First M." for tables/reports, falling back to `name` when parts are missing. Existing users were best-effort backfilled (last word → surname).
- **Employment status.** Optional `users.employment_status`, limited to `User::EMPLOYMENT_STATUSES` (Permanent/Regular, Casual, Co-Terminus, Job Order).
- **Broadcast acknowledgment layout.** Opt-in per office via `departments.broadcast_ack_layout` (DB flag, never matched by office name/code). When the *broadcasting* document's office has it on, the Concerned-staff panel for broadcasts renders as tabs by employment status → tables grouped by division, sorted not-yet-received first then by surname (columns: formal name, position, date/time received = `acknowledged_at`). Otherwise the default chip list is used.
- **Deadlines.** The optional per-document `deadline` (a date) appears at encode/edit only when the encoder's office has `departments.deadline_enabled` AND the chosen type has `document_types.requires_deadline` — never by matching type/office name strings (same principle as `is_accounting`/`is_hospital`). The tracking-list Deadline column shows only for users whose office is deadline-enabled (Super Admin always). Countdown uses `BusinessHours` from now to `work_end` (5 PM) of the due date: light **orange** ≤ 16 working hours left, light **red** ≤ 8, plus an Overdue badge past due. Deadline highlighting takes precedence over SLA aging on a row.
- **Accounting Setup** visible to Super Admin only (`@role('Super Admin')`), not `@can`.
- **Version bumping.** Always check `git log --oneline` and `git tag --list` BEFORE choosing a version number. Never reuse a version *on the active line*. The active pre-launch line is **v1.x** — follow `config/version.php` + the newest `CHANGELOG.md` entry and increment from there. The `v2.x` tags in the repo are **historical/abandoned** from a previous versioning scheme; ignore them (they are not collisions). At deployment the project **re-baselines to v1.0.0** (and the pre-launch changelog collapses into an accordion) — until then pre-launch numbers are just sequential and don't need to be "correct".
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
2. **Always output the exact `cmd` commands for BOTH machines** (dev machine push block AND server deploy block), with the real version number and commit message filled in — every batch, without being asked. Use the two `cmd` blocks above verbatim (dev = add/commit/push/tag; server = mysqldump then deploy.ps1).
3. Never give deploy commands before the version bump commit is ready — the user will push what's on disk.
4. `CHANGELOG.md` history stays **intact and append-only** — never rewrite or delete past entries; just add the newest version on top following the strict bullet format. The v1.0.0 re-baseline happens only at deployment.

### Changelog entries: strict format, no prose summary
No bold summary line under the version heading. Every line is a bullet: `- **Short subject** — short plain description.` One bullet per distinct thing done. Keep each description general and plain (what changed and why it matters), not technical — skip file paths, method/class names, migration names, and multi-clause technical breakdowns.

Example:
```
## 1.2.0 — 2026-01-01
- **Forward dropdown clearable** — added a way to remove a wrongly-picked staff member instead of being stuck.
- **Dark mode contrast fixed** — selected names were unreadable on dark backgrounds in a few places.
```

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
- **Never position two small icons (e.g. clear-× and chevron) with two independent `right-X` absolute offsets.** This was tried, the offsets were miscalculated, and the icons overlapped each other — a second, related failure on top of the nested-button bug above, in the same UI element, in the same night. Put both icons in **one** flex wrapper (`flex items-center gap-N`) and absolutely-position only that wrapper. See "Dropdown / picker conventions" for the exact markup to copy.
- Never introduce features, abstractions, or cleanup beyond what was asked
- Never let "small fixes" compromise data accuracy — get calculations right the first time
- **Never trust that custom Alpine/Blade UI "looks right" just because it compiles.** `view:cache` and `npm run build` only catch syntax errors, not broken layouts (invalid HTML nesting, misaligned flex children, overlapping absolutely-positioned elements, etc.). For any new or edited dropdown/picker/form component, mentally trace the rendered DOM (or actually load the page) before calling it done.
- **When the user reports the same category of bug a second time, the first fix was not actually verified — do not re-patch with more of the same approach.** Stop, identify why the previous fix didn't hold (often: the fix addressed the symptom, not the structural cause), and switch to an approach that makes the bug class impossible by construction (e.g. flex layout instead of manual offset math) rather than one that is merely "more carefully calculated."

## Dropdown / picker conventions

This pattern went through three broken iterations in one night before landing on something correct — see "Things to never do" for the full postmortem. The rules below are the *current, correct* implementation. **Copy this exact structure for any new searchable dropdown — do not improvise the icon positioning.**

- **Searchable by default.** Any dropdown selecting from a list of users, departments, or divisions (system-wide, not just per-office short lists) must be searchable, not a plain `<select>` — these lists grow over time.
- **Shared pattern.** Use `<x-search-select>` for a flat single-value picker, `<x-rc-picker>`-style inline Alpine blocks for dependent/cascading pairs (e.g. Department → Division).
- **Icon positioning — copy this exactly, do not invent your own offsets:**
  ```html
  <div class="relative">
      <button type="button" class="input-btn text-left pr-14 block">
          <span class="truncate block">...label...</span>
      </button>
      <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
          <button type="button" x-show="val" @click.stop="val = ''" class="w-4 h-4 ...">×-icon</button>
          <svg class="w-4 h-4 ... pointer-events-none">chevron</svg>
      </div>
  </div>
  ```
  Both icons (clear-× and chevron) live **inside one flex wrapper** that is itself the single absolutely-positioned element (`right-3`). The wrapper's `flex items-center gap-1.5` lays the two icons out side by side automatically. **Never position the × and the chevron independently with two separate `right-X` values** — that requires hand-calculating that the offsets don't collide, which has already gone wrong twice. A single flex wrapper makes overlap structurally impossible, so there is no math to get wrong.
  The trigger `<button>` itself contains only the label `<span>` — no icons inside it — with `pr-14` to reserve room for the icon wrapper. The chevron `<svg>` is decorative only (`pointer-events-none`, never interactive) and must never be a `<button>`.
- **Always offer a way to clear a selection** once something is picked, unless the field is genuinely mandatory and has no "none" state.
- **Optional fields look optional.** Use `<span class="text-gray-400 text-xs font-normal">(optional)</span>` next to the label instead of a red asterisk when a field isn't required.

## Design principles

- Clean, modern, organized UI
- Full-width layouts for forms and reports
- Auto-fitting grids with `[grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]`
- Super-Admin toggles for optional features
- Mandatory reason + audit log for leave/undertime entries (anti-circumvention)
- **Audit trail detail.** Every `ActivityLog::record()` call must include the most specific details possible — names, titles, and IDs (e.g. `"Deleted a holiday: Independence Day on 2026-06-12 (#23)"`), never just IDs or generic labels. For settings changes, log exact diffs (`"Title "X" → "Y"; Show totals ON → OFF"`). Action labels must be plain English readable by non-technical staff. Log inline from controllers (not the generic middleware) when detail matters.
