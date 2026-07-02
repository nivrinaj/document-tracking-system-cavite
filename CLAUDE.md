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

- **HARD RULE — never hardcode a name/label for conditional logic or gating, anywhere in the system.** Every office, division, document type, and role is connected via primary key / foreign key / a stable unique identifier — never by matching on `name` or `code` strings. Renaming something in its admin GUI must never break functionality that depends on it. This is why: `is_accounting`/`is_hospital`/`deadline_enabled` DB flags (not office name matches) gate special office behavior; `department_id`/`division_id` FKs (not name matches) resolve heads/scopes; and **`roles.system_key`** (not `roles.name`) is the stable identity for the handful of "system" roles the app's logic depends on (Super Admin, Department Head, Assistant Department Head, Division Head, Staff) — `name` is a free-text label an admin can rename anytime from Roles & Permissions without breaking anything. Always check system roles via `User::hasSystemRole(User::SYS_*)` (or the `isDeptHeadRole()`/`isDivisionHead()`/`isHead()` helpers) — **never** `hasRole('Some Name')`, `hasAnyRole([...])`, or the `@role('Some Name')` Blade directive. When a setting stores a *reference* to a role (e.g. `messaging_excluded_roles`), store the role **ID** (a real FK value), never its name — matched via `whereIn('id', ...)`.
- **No hardcoded office names/codes.** Identify special offices via DB flags (`is_accounting` on departments, `is_hospital` on divisions). Never match strings like 'PACCO' or 'OPAcc' in code.
- **Dropdown placeholders.** Every `<select>` gets a `"— Select X —"` disabled first option.
- **Tailwind classes.** Use `.input` for text inputs, `.input-btn` for button triggers (with explicit padding), `.label`, `.field-k`. Custom components: `<x-search-select>`, `<x-file-drop>`, `<x-btn>`, `<x-card>`, `<x-badge>`, `<x-stat-card>`, `<x-toggle>`. Use `<x-toggle>` (a modern sliding switch) for on/off settings instead of bare checkboxes; it works as a normal form field (`name=` → hidden 0 + checkbox 1) and with Alpine (`x-model`). Pass checked state as `:checked="expr"` (never `@checked(...)` — that directive breaks inside a component tag).
- **Per-fund annual sequences.** Each fund has its own sequence (starts at 1, resets yearly). Hospital funds run separately (`F{id}H`). Atomic via DB transaction + `lockForUpdate` on `TrackingSequence`.
- **Working-hours engine.** `BusinessHours::secondsBetween()` for all time calculations (holding, idle, turnaround). Age stays calendar time. **Always compute document timing via `$document->elapsedSeconds($start, $end, $possessor)`**, never call `BusinessHours::secondsBetween()` directly on a document's dates — `elapsedSeconds()` is the single place that also honors a department's calendar-days override (see below).
- **Calendar-days tracking: department gate + per-document choice.** `departments.time_tracking_mode` (`working_hours` default, or `calendar_days`) + `departments.calendar_days_include_weekends` only **make the option available** to that office and set the *default* shown at encode time — they do **not** force every document in that department into calendar days. Each document can independently override via `documents.time_tracking_mode` / `documents.calendar_days_include_weekends` (both nullable — null means "inherit the department's default"). Always read the effective mode via `Document::timeTrackingMode()` / `Document::calendarDaysIncludesWeekends()`, never the raw department columns directly. The shared `BusinessHours` engine is untouched for every other document — this is purely a display-layer branch inside `Document::elapsedSeconds()`. The "Daily Working Time" panel on the document details page is a working-hours-only visualization, so it's **hidden** (not recalculated) whenever a document's effective mode is `calendar_days`.
- **Forward to Department Head.** Opt-in per office via `departments.forward_to_head_enabled`. `Document::departmentHead()` / the static `Document::departmentHeadFor(?int $departmentId)` resolve the head by role + `department_id` FK (never by name) — the static form lets the encode page resolve a head before a document exists yet. Available as a "Send as" choice at encode time (alongside assigning to a specific staff member) as well as a show-page action once the document has been received. `forwardToHead()` hands the document to the head (status `forwarded`, `forwarded_to_head_at` timestamp set) and — unlike a regular `forward()`, where the clock stays with the sender until the recipient scans/receives — **immediately opens a new possession segment for the head**, since the sender is done with it the moment it's routed to the queue. Any other active staff in the same department may `claimFromHead()` instead of waiting on the head specifically — first to act becomes the holder; the head still sees the ordinary Receive button. `Document::isAwaitingHeadClaim()` gates both the `claimFromHead` ability and a `view`/`scopeVisibleTo` carve-out so uninvolved staff in that department can find and see the document before they've ever touched it (they aren't otherwise "concerned" until they claim it).
- **Total time per staff.** `Document::userHoldingSummary()` sums every possession-ledger segment per holder (a document can bounce back to the same person more than once) via `elapsedSeconds()`, so it automatically respects that document's own time-tracking mode. Shown on the document details page, right after Daily Working Time, only once the document `isClosed()` — the number isn't meaningful mid-flight.
- **Hospital flag.** `documents.is_hospital` is set at encode time from the encoder's division. Never inferred from tracking-code text or mutable division FK.
- **Document types.** All types are global. Offices can be restricted to a subset via `restricted_doc_types` JSON on `departments`. Accounting offices auto-limited to Voucher/Payroll.
- **Extra accounting fields** (Amount, Fund, OBR, Responsibility Center, Nature) gated by `is_accounting` toggle on the department, not the document type.
- **User names.** Users have structured `first_name`, `last_name` (required) and optional `middle_name`; the full `name` column is composed from them on save (`User::composeName()`) and stays the display value everywhere. `User::formalName()` gives "Surname, First M." for tables/reports, falling back to `name` when parts are missing. Existing users were best-effort backfilled (last word → surname).
- **Employment status.** Optional `users.employment_status`, limited to `User::EMPLOYMENT_STATUSES` (Permanent/Regular, Casual, Co-Terminus, Job Order).
- **Broadcast acknowledgment layout.** Opt-in per office via `departments.broadcast_ack_layout` (DB flag, never matched by office name/code). When the *broadcasting* document's office has it on, the Concerned-staff panel for broadcasts renders as tabs by employment status → tables grouped by division, sorted not-yet-received first then by surname (columns: formal name, position, date/time received = `acknowledged_at`). Otherwise the default chip list is used.
- **Transmittal / batch quantity.** Opt-in per document type via `document_types.allows_transmittal` (DB flag, never a name match), further scoped per office the same way as desktop receive: `transmittal_scope` (`all`/`selected`) + `transmittal_departments` (CSV) — always check via `$documentType->transmittalAllowedFor(?int $departmentId)`, checked both client-side (gates the toggle) and server-side (store/update, defense in depth). When allowed, a toggle appears — "This is a transmittal of multiple [type]" — revealing a required `transmittal_quantity` field via `<x-qty-stepper>` (a compact +/- stepper, not a bare number input — a bare input in a full-width panel looks like a stray half-width field). Shown as a 📄 badge on the tracking table, document details page, and summarized on the dashboard (only when transmittals are present in view).
- **Desktop receive scope.** `allow_desktop_receive` is the master on/off switch; `desktop_receive_scope` (`all`/`selected`) + `desktop_receive_departments` (CSV of department IDs) narrow it to specific offices. Always check via `Setting::desktopReceiveAllowedFor(?int $departmentId)` — never re-read the raw settings inline.
- **Reusable multi-select.** `<x-reports._multi-select />` + the `multiSelect()` Alpine factory (registered globally in `resources/js/app.js`) is the searchable checkbox-list-with-chips picker — reuse it for any "pick several of these" UI (report office access, desktop-receive department scope, transmittal department scope, etc.) rather than building a new one.
- **Document Tracking filter dropdowns are role-gated, never client-trusted.** Department dropdown: Super Admin only. Division dropdown: Super Admin or Dept/Assistant Dept Head only. Staff dropdown (filters by `current_holder_id`): Super Admin (unrestricted), Dept/Assistant Dept Head (own department, cascades with division), Division Head (own division) — everyone else gets none of these three. Every submitted `department_id`/`division_id`/`user_id` is re-validated server-side against the requester's own department/division in `DocumentController::index()`; a non-privileged role's filters are always forced to their own scope regardless of what's submitted.
- **User account deletion toggle.** `Setting::enable_user_delete` (default on) gates BOTH the admin "Delete" button on the Users page (`UserController::destroy()`) AND the self-service "Delete Account" section on a user's own Profile page (`ProfileController::destroy()`) — one toggle, two surfaces. Check server-side in both controllers, not just hidden in the view.
- **New-user default password + forced change.** Add User has a "use default password" toggle (`User::DEFAULT_PASSWORD`) that sets `users.must_change_password = true`; the `password.changed` middleware (`EnsurePasswordChanged`) redirects anyone with that flag to `password.mustChange` before anything else in the app, until they set their own password (lower + upper + number, min 8 — kept simple with a plain-language checklist, not a rules paragraph, since this has to work for every skill level).
- **Deadlines.** The optional per-document `deadline` (a date) appears at encode/edit only when the encoder's office has `departments.deadline_enabled` AND the chosen type has `document_types.requires_deadline` — never by matching type/office name strings (same principle as `is_accounting`/`is_hospital`). The tracking-list Deadline column shows only for users whose office is deadline-enabled (Super Admin always). Deadline highlighting takes precedence over SLA aging on a row.
- **Deadline highlight colors are fully configurable, never hardcoded.** `Document::deadlineHighlight()` returns `['color' => '#hex', 'label' => ...]` computed from a list of `{days, color}` rules + a separate overdue color. Global defaults live in `Setting` (`deadline_highlight_rules` JSON, `deadline_overdue_color`); any office with `deadline_enabled` can override both via `departments.deadline_highlight_rules` / `deadline_overdue_color` (falls back to global when empty). Edited via the shared `<x-deadline-rules-editor>` component (System Settings for the global defaults, Departments → edit for a per-office override) — reuse it, don't rebuild a rules editor. Rendered as an inline `style="background-color: {hex}1a"` / `style="color: {hex}"`, never a fixed Tailwind color class, since the color is admin-chosen.
- **Accounting Setup** visible to Super Admin only via `@if(auth()->user()->hasSystemRole(App\Models\User::SYS_SUPER_ADMIN))`, not `@can`, and never the `@role()` Blade directive (which matches by name).
- **Version bumping.** Always check `git log --oneline` and `git tag --list` BEFORE choosing a version number. Never reuse a version *on the active line*. The active pre-launch line is **v1.x** — follow `config/version.php` + the newest `CHANGELOG.md` entry and increment from there. The `v2.x` tags in the repo are **historical/abandoned** from a previous versioning scheme; ignore them (they are not collisions). At deployment the project **re-baselines to v1.0.0** (and the pre-launch changelog collapses into an accordion) — until then pre-launch numbers are just sequential and don't need to be "correct".
- **Seeders.** `deploy.ps1` runs migrations only, never seeders. Seeders are one-time manual runs.
- **Route-level Super Admin gating.** Use the `system-role:super_admin` middleware alias (`App\Http\Middleware\EnsureSystemRole`), never Spatie's own `role:Super Admin` middleware — that one matches by `roles.name` and breaks the moment the role is renamed, same as `hasRole()` in application code.
- **Backups.** Super-Admin-only module (`/backups`) — no third-party backup package, just `App\Services\BackupService` (`mysqldump` via `Illuminate\Support\Facades\Process`, zipped together with every attachment via `ZipArchive`). Requires the PHP `zip` extension enabled and a working `mysqldump` binary; if the binary isn't on the web server process's PATH (common on IIS app pools), it can be set from the GUI — the Backups page has a "Configuration" card (`backups.config` route → `BackupController::saveConfig()`) that saves a `backup_mysqldump_path` Setting, validated with `is_file()` before saving. `BackupService::mysqldumpPath()` is the single source of truth: the GUI-set Setting wins when present, otherwise it falls back to `BACKUP_MYSQLDUMP_PATH` in `.env` (`config('backup.mysqldump_path')`) — never call `config('backup.mysqldump_path')` directly elsewhere, always go through `mysqldumpPath()`. Backups are stored on a dedicated private `backups` disk (`storage/app/backups`, config in `config/filesystems.php`) — **never** the `public` disk, since that's web-accessible via the `/storage` symlink. Filenames from requests are always validated with `basename()` against the real file list before download/delete (defense against path traversal) — see `BackupService::path()`/`delete()`. **Deliberately no restore endpoint** — restoring a dump is destructive/irreversible and stays a manual, deliberate server-side action (`mysql < backup.sql`), never a one-click web action. File sizes are formatted via `BackupService::formatBytes()`, never `Illuminate\Support\Number::fileSize()` — that helper requires the `intl` PHP extension, which isn't guaranteed to be enabled on every environment (already broke once in production for exactly this reason).

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
mysqldump -u root -p --host=127.0.0.1 pgc_dts > C:\backups\pgc_dts_before-X.X.X.sql
powershell -ExecutionPolicy Bypass -File .\deploy.ps1
```

**Always run mysqldump before deploy.ps1. No exceptions.**

**Always include `--host=127.0.0.1` (match the server's actual `DB_HOST` from `.env`) on manual `mysqldump` commands.** Without it, `mysqldump` connects via a named pipe/socket on Windows, which MySQL treats as a *different account* (`'root'@'localhost'`) than the TCP connection (`'root'@'127.0.0.1'`) the app itself uses — even with the correct password typed at the prompt, this produces a misleading `Access denied ... (using password: NO)` error. This bit a real deploy once; don't give a bare `mysqldump -u root -p dbname` command again.

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
- Never use `@can('accounting.manage')` for Accounting Setup nav — use `@if(auth()->user()->hasSystemRole(App\Models\User::SYS_SUPER_ADMIN))`
- Never use `hasRole('Some Name')`, `hasAnyRole([...])`, or `@role('Some Name')` for logic the app depends on — these match Spatie's free-text `roles.name`, which breaks the moment an admin renames the role. Always use `hasSystemRole()` / `isDeptHeadRole()` / `isDivisionHead()` / `isHead()`, which match on the stable `roles.system_key`.
- Never use Spatie's `role:` route middleware — use `system-role:super_admin` (same reasoning as above, applied at the route level).
- **Never add a restore endpoint/button to the Backups module**, and never store backups on the `public` disk. Never pass a request-supplied backup filename straight to a filesystem call — always resolve it through `BackupService::path()`/`delete()`, which validate with `basename()` against the real file list first.
- Never use `Illuminate\Support\Number::fileSize()`/`format()`/`currency()` — they require the `intl` PHP extension, which already broke the Backups page once because it wasn't enabled in production. Format byte sizes with `BackupService::formatBytes()`; write plain PHP for anything else that would otherwise reach for `Number::`.
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
