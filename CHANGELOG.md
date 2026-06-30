# Changelog

All notable changes to the **PGC Document Tracking System** are recorded here.
Each version corresponds to a git tag so any release can be reviewed or rolled back.
Format based on [Keep a Changelog](https://keepachangelog.com).

---

## 1.14.0 — 2026-07-01
- **Idle Time explained, not changed** — verified the "0 idle time" some documents show is mathematically correct, not a bug: idle time only counts official working hours, so an action taken after 5 PM shows little or no idle time until the next business day begins. Added a footnote to the report and confirmed the Document Details page uses the exact same calculation, so both are consistent.
- **Date Encoded column and row numbering added** — the report was missing both; every row now shows its actual encoded date/time and a running number.
- **Sort filter added** — Document Aging Report can now be sorted by Date Encoded, by highest Idle Time, by oldest Age, or by Status, with the active sort shown in the printed header.
- **Department and Division filters added with cascading** — the Document Aging Report now has a searchable Department dropdown that gates the Division dropdown (Division stays locked until a department is chosen), and the Staff dropdown narrows automatically to match whichever department and/or division are selected.

## 1.13.0 — 2026-06-28
- **New report: Document Aging Report** — tracks how long a document has been open, when it last moved, and how much working-hours idle time has passed since then, alongside type, title, origin office/division, and current office/division.
- **Available to any office, not just Accounting** — Super Admin picks which offices can run it from Report Settings, same toggle pattern as the other reports.
- **Filters for department heads and staff** — by division, by specific staff member, by document type, by status, by hospital scope, and by date (with an optional time range).
- **Printed header shows who generated it** — organization, office, and the generating staff member's name are printed on every page.
- **Summary line on the last page** — total documents, how many are still open, and average age/idle time, so an office can see at a glance whether documents are moving efficiently.
- **Configurable like the other reports** — title, paper size, orientation, page number toggle, and renameable/alignable columns, all from Report Settings.

## 1.12.0 — 2026-06-28
- **Dropdown icon overlap fixed for real** — the clear and caret icons on every searchable dropdown were overlapping each other. Rebuilt with a layout that can't overlap by construction, not just hand-tuned spacing.
- **Hospital RC required/optional toggle** — Super Admin can now choose whether Hospital-division staff must pick a Responsibility Center when encoding, instead of it always being optional.
- **Changelog format fixed** — entries are now plain bulleted lists (subject + short description), no summary paragraph.
- **Logo/Favicon/Login background upload redesigned** — modern preview cards with drag-and-drop style pickers instead of plain file inputs.
- **Fund editing restored to table view** — kept the original table layout, fixed the broken edit row, and added a Cancel button so a mistyped edit can be backed out without saving.
- **Cancel buttons added** — Responsibility Center, Project, Hospital RC, and Nature of Transaction edit forms can now be cancelled the same way.
- **Documents filter layout fixed** — every filter field now has a label, so the row lines up cleanly instead of the date range field sitting lower than its neighbors.

## 1.11.0 — 2026-06-28
- **Dropdown clear button misaligned** — fixed the "×" rendering below the field instead of inside it, on the Forward-to-staff picker and the Responsibility Center pickers.
- **Responsibility Center now optional** — office/unit and project are no longer required when encoding documents. Reports show "N/A" when nothing was picked, or just the office's own code/name when a project wasn't chosen.
- **Hospital RC display fixed** — centers without a code now show just their name instead of an odd leading slash.
- **Fund editing layout fixed** — editing a fund in Accounting Setup no longer breaks the table layout.
- **Missing icon added** — "Assign to a staff in my office" now has an icon like every other option in that dropdown.
- **Audit log detail improved** — encoding a document, adding a responsibility center, and similar actions now log the actual name/code instead of a generic label.
- **Search filters added** — user, department, and division dropdowns on the most-used pages (user accounts, document filters) are now searchable.

## 1.10.0 — 2026-06-28
**Responsibility Center rework (Office/Unit → Project cascading dropdowns + dedicated Hospital RC list), QR Slip design settings**
- **New `responsibility_center_projects` table** — Responsibility Centers (existing flat table) now act as the parent "Office/Unit" level; a new child table holds Projects under each office/unit. New `is_hospital` flag on `responsibility_centers` repurposes the same table for a separate, flat Hospital RC list (same pattern as `funds.hospital_available`). New `documents.responsibility_center_project_id` FK added.
- **Encode form — two flows.** Non-hospital encoders (most offices): two searchable, dependent dropdowns — pick Office/Unit, then Project (filtered live by the chosen office). Hospital-division encoders: a single searchable dropdown sourced only from the Hospital RC list, no office/project levels. New `<x-rc-picker>` Blade component handles both modes and is reusable for future offices beyond Accounting. The old free-text "Resp. Center — Code" field is removed from the encode form (column kept in the DB for historical records).
- **Reports & document detail show the combined RC label** — `Document::rcLabel()` renders `"{office code}/{office name} - {project code} - {project name}"` (e.g. `1011/OPG - SPA - Project 1`), used in E-Record, Transmittal, and the document detail page. Hospital documents show just `{code}/{name}` since there's no project level.
- **Accounting Setup reworked** — Responsibility Centers card now shows expandable Office/Unit rows with nested Project management (add/edit/delete inline), plus a "Hospital RC" toggle per row and a new separate "Hospital Responsibility Centers" card for the flat hospital-only list.
- **New QR Slip Settings page** (`/qr-slip/settings`, Super Admin only, linked from System Settings) — configurable header/badge color (with theme-default option and preset swatches), badge text, footer text, footer/tracking-URL visibility toggles, and a per-field toggle for every detail row on the slip (Type, Voucher No., Reference No., Fund, Amount, OBR No., Source/Origin, Encoded date). All settings flow through the existing global `$settings` view-composer so `documents/print.blade.php` picks them up without per-controller wiring.
- **Detailed audit logging** — Accounting Setup and QR Slip settings saves both log human-readable diffs (consistent with the rest of the system's audit trail convention).

## 1.9.0 — 2026-06-28
**Working-time progress bar fix, "In transit" renamed, clearable search-selects, dark mode contrast pass**
- **Daily working time progress bar fixed** — previously computed as `seconds worked ÷ flat 8 hours`, making the bar always look like it started near zero. Now positions the filled segment proportionally within that day's actual configured working window (e.g. 8:00 AM–5:00 PM), so "2:52 PM – 5:00 PM" renders as a segment near the end of the bar, not a short fill from the left edge. `BusinessHours::dayDetail()` now also returns `day_start`/`day_end` per day.
- **"In transit" renamed to "Awaiting Receipt"** — across the dashboard stat card, document detail "Currently with" panel, and the documents list "Current Holder" column. Internal `in_transit` stage key/URL param (`?stage=in_transit`) left unchanged to avoid breaking links — only the visible label text changed.
- **`<x-search-select>` is now clearable** — added an "×" button inside the trigger (visible only when a value is selected) that clears the selection without reopening the dropdown. Fixes "Forward to another staff" and "Transfer to office" pickers, which previously had no way to deselect a wrong pick.
- **Dark mode contrast pass** — fixed unreadable blue-on-dark text across 8 locations using the un-shaded `text-[color:var(--color-primary)]` on a tinted background with no `dark:` override: distribute-for-acknowledgement selected-people chips, encode-form recipient chips, digital-copy icon badge, dashboard "needs your action" badge, messages widget avatars/buttons, messages index avatars/buttons, and the top-bar Messages icon active state. All now use the existing `--color-primary-light` variable under `dark:` (the same pattern already used correctly elsewhere in the codebase).

## 1.8.8 — 2026-06-28
**Paper size & orientation in Options panel for all reports**
- **Transmittal paper & orientation** — Transmittal report now has configurable paper size (A4/Letter/Legal) and orientation (Landscape/Portrait), stored as `transmittal_paper` and `transmittal_orientation` settings. Previously hardcoded to A4 Landscape.
- **Options panel consolidation** — for both E-Record and Transmittal, paper size and orientation are now inside the Options card alongside the subtotal/grand total and page number toggles. Paper and orientation use inline dropdowns on the right side matching the toggle layout. Removed the separate grid from the E-Record card.
- **Audit logging** — paper and orientation changes are included in the settings diff log for both report types.

## 1.8.7 — 2026-06-27
**Audit log fixes: holiday detail, real client IP via Cloudflare, district-level geolocation**
- **Holiday log detail** — `storeHoliday` and `destroyHoliday` in `WorkCalendarController` now log inline with full detail: "Deleted a holiday: Independence Day on 2026-06-12 (#23)" instead of the generic middleware format. Both routes added to middleware ignore list.
- **Real client IP via Cloudflare** — `ActivityLog::clientIp()` reads `CF-Connecting-IP` header first (set by Cloudflare with the real visitor IP), falling back to `X-Forwarded-For`, then `request()->ip()`. Previously logged the Cloudflare edge server's IPv6 address, which geolocated to the wrong city.
- **District-level geolocation** — ip-api.com query now includes `district` field. Location string shows district → city → region → country when available, e.g. "Rosario, Cavite City, Calabarzon, Philippines" instead of just "Makati City, Metro Manila, Philippines".
- **Note on precision** — IP geolocation is inherently limited to city-level accuracy at best. ISPs in the Philippines often route through Manila POPs, so the city may not match the user's physical location. Barangay/municipality precision is not achievable from IP alone.

## 1.8.6 — 2026-06-27
**Audit log overhaul: location tracking, human-readable labels, named subjects**
- **Login/logout location** — auth events now resolve the user's IP to city/region/country via ip-api.com (2-second timeout, graceful fallback). Log entries read e.g. "Logged in — Windows PC / Chrome — Imus, Cavite, Philippines". Private/local IPs skip the lookup.
- **Human-readable action labels** — every action in `ActivityLog::actionLabel()` and `LogActivity` middleware now has a plain-English label. Covers all 40+ routes: documents, attachments, departments, divisions, document types, accounting setup (funds, centers, natures), roles, settings, calendar, holidays, help pages, messages, and data resets. No more "Work-calendar Holidays Destroy" — now reads "Deleted Holiday".
- **Named subjects in details** — middleware now logs `Name (#{id})` instead of just `#id`. Falls back through `tracking_code → name → title → label → #id`. E.g. "Deleted a holiday: Independence Day (#23)" instead of "Work-calendar holidays destroy: #23".
- **Expanded filter dropdown** — Logs & History filter now includes all action categories: Attachments, Department changes, Document type changes, Accounting setup, Report settings, Calendar entries, Holidays, Help pages, Messages, Data resets.
- **Calendar routes deduplicated** — `work-calendar.holidays.store`, `work-calendar.team.store`, `work-calendar.team.destroy` added to middleware ignore list since `WorkCalendarController` already logs inline with full detail.
- **Device info on failed logins** — failed login attempts now also log device/browser/location alongside the attempted username.

## 1.8.5 — 2026-06-27
**Full report title in filename, compressed tracking columns, browser tab title, device info in auth logs**
- **Full report title in PDF filename** — Transmittal PDF now uses the full configured title (e.g. `Transmittal-of-Reviewed-Disbursement-GF-20260627-103903.pdf`) instead of just `Transmittal-GF-...`. Spaces in the title are replaced with dashes.
- **Browser tab shows report title** — added `<title>` tags to both `transmittal.blade.php` and `erecord.blade.php` so the browser tab displays the report name + fund code instead of a generic "transmittal".
- **Compressed last 5 columns** — Transmittal tracking columns (secretary, releasing, days, date_in, date_out) reduced from 5%/5%/3%/3.5%/4% to 4%/4%/2.5%/3%/3%. Freed 4% redistributed to payee (14→16%) and particulars (17→19%).
- **Device info in auth logs** — login, logout, and failed login audit entries now include device and browser, e.g. "Logged in — Windows PC / Chrome" or "Logged in — iPhone / Safari". Parsed from user-agent in `AppServiceProvider`.

## 1.8.4 — 2026-06-27
**Hotfix: PHP fatal error on reports page (array destructuring with defaults)**
- **Root cause** — `ReportController::diffSettings()` used PHP array destructuring with default values (`[$key, $newVal, $isBool = false, $lookup = null]`), which PHP does not support. This caused a fatal error on every page load that touched the reports route.
- **Fix** — replaced with explicit index access (`$field[0]`, `$field[1]`, `$field[2] ?? false`, `$field[3] ?? null`).
- **CLAUDE.md updated** — added two new "never do" rules: (1) never use PHP array destructuring with defaults, (2) never deploy without hitting changed routes at runtime — `view:cache` only checks Blade syntax, not controller PHP.
- **Lesson** — `php artisan view:cache` and `npm run build` passing does NOT mean the code works. Must verify changed routes actually load before deploying.

## 1.8.3 — 2026-06-27
**Multi-select pill redesign + detailed audit logging for settings & user changes**
- **Multi-select pill styling** — redesigned selected-item chips with indigo background, ring border, and semibold text. Clearly visible in both light and dark mode. Remove button has a hover state.
- **Dropdown list highlight** — selected items in the dropdown use indigo tint with solid indigo checkboxes for clear contrast.
- **Detailed audit logs for report settings** — saves now log exactly what changed, e.g. `Report settings (E-Record): Title "E-Record" → "My Title"; Paper a4 → letter; Show totals ON → OFF; Offices [OPAcc] → [OPAcc, PACCO]`. If nothing changed, logs "(no changes)".
- **Detailed audit logs for system settings** — same diff format: `System settings: App name "DTS" → "PGC DTS"; Desktop receive OFF → ON; Logo uploaded`.
- **Detailed audit logs for user create/update** — user creation logs name, username, role, and department. User update logs every field that changed: name, username, email, department, division, role, active status, capabilities, and password (as "Password changed").
- **Middleware skip for detailed routes** — `settings.update`, `reports.settings.save`, `users.store`, `users.update` are now logged inline by their controllers (with diffs) instead of the generic middleware.

## 1.8.2 — 2026-06-27
**Report Settings UI polish: proper multi-select, toggle switches for all reports, container fix**
- **Container width restored** — settings page back to `max-w-3xl` centered (matches the user-edit card width), with inputs filling the full card interior instead of capping at `sm:max-w-sm`.
- **Multi-select dropdown redesigned** — trigger now uses `.input-btn` for proper borders in light and dark mode. Selected items render as compact rounded pills with clear remove buttons and a rotating chevron. Search and checkbox-style dropdown unchanged.
- **E-Record Options toggles** — added the same iOS-style "Options" card to E-Record with two toggles: "Show page subtotal & grand total" and "Show page number in footer". Both default on and persist to settings. E-Record template now conditionally renders subtotals and the page-number/generated-at footer.
- **Toggle design** — all report toggles (E-Record + Transmittal) use the same pattern as the "Access & capabilities" panel on the user edit page: title + description on the left, iOS-style switch on the right, in a bordered rounded container with dividers.
- **Dependent divisions** — Transmittal divisions multi-select filters to only show divisions from selected offices. Auto-prunes invalid selections when offices change.

## 1.8.1 — 2026-06-27
**Report Settings UX overhaul: searchable multi-select, toggle switches, subtotal toggle**
- **Searchable multi-select dropdowns** — replaced the checkbox grid for offices (both E-Record and Transmittal) with a searchable dropdown that shows selected items as removable pills. Scales to 30+ departments without a wall of checkboxes.
- **Dependent divisions dropdown** — Transmittal's divisions multi-select now filters automatically based on the selected offices, showing only divisions that belong to those offices. Changing offices prunes invalid divisions.
- **Subtotal & grand total toggle** — new setting for the Transmittal report to show/hide per-page subtotals and the grand total row. Uses the same iOS-style toggle design.
- **Toggle switches** — page number and subtotal settings now use the same iOS-style toggle design as the "Access & capabilities" panel on the user edit page, with title + description layout.

## 1.8.0 — 2026-06-27
**New report: Transmittal of Reviewed Disbursement (ISO layout)**
- **New report type** — "Transmittal of Reviewed Disbursement" with an ISO-registered layout (`transmittal.blade.php`). A4 landscape, 15 columns: 9 data columns (Date Received JEV, DV No., OBR No., RC, Fund, Payee, Nature, Particulars/Explanation, Amount) + Date Received Review (same date) + 5 blank tracking columns (Secretary, Releasing Staff, No. of Days, Date In, Date Out) at minimal width.
- **Header matches ISO format:** Organization – Office Name – Division Name (all from DB IDs, not hardcoded), then Report Title + ISO Code (both configurable), then FUND NAME – FUND CODE YEAR YYYY.
- **DB-gated access** — only offices and divisions configured in Report Settings can generate. Department heads of configured offices are always allowed. Uses `transmittal_offices` and `transmittal_divisions` settings (DB IDs), never hardcoded names.
- **Date source dropdown** — choose between "Date received by the configured division" (looks up `document_logs` for the first `received` action by a user in the division) or "Date encoded/created". Configurable in Report Settings, overridable per-report in the filter panel.
- **Configurable column labels & alignment** — same pattern as E-Record. All 15 columns can be renamed and aligned in Report Settings.
- **Optional page number** — toggle in Report Settings to show/hide centered "Page N" in the footer. No other footer content.
- **Report Settings** now has a report-type selector (E-Record / Transmittal), each with its own form. Transmittal has: title, ISO code, date source, page number toggle, offices, divisions, column labels & alignment.
- **Reports index** updated — the Alpine component handles both report types independently with separate filter panels and preview.
- **PDF filename:** `Transmittal-{FundCode}(-H if hospital)-{datetime}.pdf`.

## 1.7.8 — 2026-06-27
**Encode placeholder, Voucher badge, renameable report columns, redistributed column widths**
- **Document Type placeholder** on the encode form — dropdown now starts with "— Select document type —" (disabled) instead of auto-selecting the first type.
- **Voucher/Payroll badge** on the encode form — the doc-type chip in the Accounting details panel is now a subtle indigo pill with a ring border instead of faded gray, so the selected type stands out clearly.
- **Renameable report columns** — Report Settings now shows an editable label input beside each column's alignment selector. Leave blank to keep the default name; type a custom label to override it on the preview and PDF.
- **Redistributed column widths** — OBR (10→7%) and RC (13→10%) narrowed; freed space goes to Particulars (25→28%) and Payee (19→22%), giving long-content columns more room.

## 1.7.7 — 2026-06-27
**Sequence reset on data wipe, RC column rename, PDF blank page fix, descriptive filename**
- **Deleting all documents now resets tracking sequences** — the Danger Zone wipe clears the `tracking_sequences` table so the next encoded document starts at sequence 1, not where the deleted data left off.
- **Renamed "Responsibility Center" → "RC"** in the E-Record report column header (both preview and PDF) and in the Report Settings alignment labels.
- **Fixed blank trailing page in PDF** — single-page (or last-page) reports no longer emit a page break. Only intermediate pages get `page-break-after: always`; the last page has no break class at all.
- **Descriptive PDF filename** — downloads are now named `E-Record-{FundCode}(-H if hospital)-{datetime}.pdf` (e.g. `E-Record-GF-H-20260627-093000.pdf`).

## 1.7.6 — 2026-06-27
**E-Record PDF fixes: blank page, column widths, sort order**
- **Fixed blank trailing page** — single-page reports no longer generate an extra empty page at the end (changed `page-break-after` from `auto` to `avoid` on the last page).
- **PDF column widths now match the preview** — explicit `width` styles on `<th>` cells reinforce the `<colgroup>` percentages so dompdf respects the same fixed layout the browser renders.
- **Sort order: date received → DV #** — rows are sorted by date (day only), then by DV number (numeric) within the same date, so the report reads in natural document order.

## 1.7.5 — 2026-06-27
**Date range always visible; time range is opt-in via checkbox**
- **Split the date & time filter** on the E-Record report page. **Date range** (date inputs) is always visible. **Time range** only appears when the "Include time range" checkbox is ticked — cleaner default for date-only filtering.
- When only dates are provided (no time), the filter automatically covers the **full day** (start of day → end of day) so no records are missed.
- Added `CLAUDE.md` to the project root with architecture, conventions, and deployment instructions.

## 1.7.4 — 2026-06-26
**Hospital classification persisted on the document (correct exclude/only)**
- A document's **hospital status is now recorded on the document itself at encode time** (`is_hospital`, set from the encoding/owning division) and queried as a real column. Previously it was inferred from the *current* division, which changes as a document moves — so a hospital `-H` voucher wrongly appeared under “Exclude hospital”. Fixed.
- **Existing documents are backfilled** (one-time) from their historical `-H` codes, so old hospital records classify correctly too.
- Date Received remains the **encoded date** (when the owning division encoded it).

## 1.7.3 — 2026-06-26
**E-Record fixes: hospital filter, pagination, totals, true column widths**
- **Hospital filter now uses the division relationship** (a document's `division_id` → `is_hospital`), not the tracking-code text — so Exclude/Include/Only are correct. *(Requires the hospital division to have the “Hospital transactions division” toggle on.)*
- **Per-page subtotal + grand total** — each page shows its subtotal; the last page shows the grand total.
- **Fixed “Page 1 of 0”** — page numbering now renders correctly (Page X of Y).
- **Preview now matches the PDF widths** — fixed table layout with set column widths: **Particulars widest, then Payee**, the rest sized to fit. No more columns collapsing in the PDF.
- **Report Settings has a report-type selector** so its settings are dedicated to the chosen report (ready for more report types).

## 1.7.2 — 2026-06-26
**E-Record refinements**
- **Compact Report selector** moved into the filters column (no more empty hanging row).
- **Placeholder options** on the report/document-type/fund dropdowns (“— Select … —”).
- **Hospital Division filter** — Exclude (default) / Include / Hospital-only for the encoded transactions.
- **Office name in the header** — the generating office's full name prints between “Provincial Government of Cavite” and the report title.
- **Column alignment** set per your spec (Date/DV#/Fund/Nature centered, OBR/RC/Payee/Particulars left, Amount right; Payee widest, Particulars next) — and now **customizable in Report Settings** (per-column left/center/right).

## 1.7.1 — 2026-06-26
**Reports module: full-width layout, live preview, date-range filter, Super-Admin report settings**
- **Full-width Reports page** with a live **preview pane** that refreshes as you change filters (so you can see the report before printing). Added a “Filters” heading and fixed the floating report dropdown.
- **Report list is office-aware** — a report only appears for offices allowed to run it (E-Record shows for accounting offices / configured offices / Super Admin; hidden otherwise).
- **Date & time range filter** (From / To, either or both) replacing the fixed month/day/year.
- **Report title & paper size are no longer set by staff.** A new **Super Admin → Report settings** page configures the E-Record title, paper size, orientation, and which offices may run it.
- **Accounting Setup is now Super-Admin only** (it was wrongly visible to all department heads).

## 1.7.0 — 2026-06-26
**E-Record report + reports module reorganized; UI polish**
- **New Reports module:** pick a report type, set its filters, generate. Older reports are hidden for now (kept in code).
- **E-Record report (accounting):** filter by **Document Type → Fund → Month / Day / Year**, with an editable **report title**; prints **A4 landscape**. Columns exactly: Date Received (e.g. 6-Jun) · DV # (the fund sequence) · OBR No. · Responsibility Center (code/name) · Fund · Payee (title) · Nature · Particulars (description) · Amount, with a grand total. Includes all encoded documents matching the filters, **any status**. Available to accounting offices (extendable to others later).
- **Configurable report codes:** each **Fund** (GF/SEF/TF/GFDF) and **Nature** (Payt./Reimb.) has an editable “report code” in Accounting Setup that drives how it prints.
- **Prominent tracking code** on the Document Details page — labeled bordered chip, bold monospace, one-click copy.
- **Renamed the “Accounting office” toggle to “Voucher & Payroll office.”**
- **Header shows the office code** (e.g. PICTO, OPAcc · division) beneath the user's name.

## 1.6.2 — 2026-06-26
**Per-office toggle for the Voucher/Payroll extra fields**
- The Amount / Fund / OBR / Responsibility Center / Nature fields now appear **only for offices with the “Accounting office” toggle on** (e.g. OPAcc). An office without it (e.g. PICTO) encoding a Voucher/Payroll sees just the regular fields. Set it per office in Departments → edit.

## 1.6.1 — 2026-06-26
**Document types reworked to per-office; type-driven accounting fields; staff dropdown fix**
- **Per-office limits, not per-type.** All document types are global again; an office is limited to a subset via Departments → edit (Accounting offices auto-limit to Voucher/Payroll). Result: **OPAcc sees only Voucher/Payroll; PICTO sees every type including Voucher/Payroll** — which the 1.6.0 approach got wrong.
- **Accounting fields follow the document type, not the office.** Any office encoding a **Voucher** or **Payroll** now gets the Amount / Fund / OBR / Responsibility Center / Nature fields and the fund-based tracking code.
- **Fixed staff dropdown** on the Department Work Calendar — it had no padding (used the wrong helper class).

## 1.6.0 — 2026-06-26
**Single per-office document types, searchable staff picker, overdue tracking**
- **One document type, office-restricted.** No more duplicate “Voucher”. Each type is a single record with an availability setting — **All offices** or **Only selected offices** (multi-select). Accounting offices still see only their Voucher/Payroll; this is separate from the amount-fields behaviour (which follows the office's Accounting flag). Existing duplicates are consolidated automatically on upgrade — documents are unaffected (they store the type name).
- **Searchable, grouped staff picker** on the Department Work Calendar — type to filter; staff grouped by division and ordered **Dept Head → Assistant Head → each division's Division Head → staff** (alphabetical).
- **Overdue tracking (per office).** In Accounting Setup, set the **overdue limit in working days** and which document types to track. The document tracking list then highlights rows **red when overdue** and **orange within 2 working days** of the limit, counting working hours only. Built to work for any office (each with its own tracked types and limit).

## 1.5.3 — 2026-06-26
**Clarity & readability: removed redundant fund flag, cleaner leave form, bolder labels**
- **Removed the confusing “GF 20%” checkbox** from the Funds panel. It no longer does anything now that every fund has its own sequence — the 20% Development Fund is simply its own fund (its name says so), distinguished behind the scenes by its record, even though it shares code 101.
- **Redesigned the Department Work Calendar form** — full-width fields; choosing “Staff on leave” or “Staff undertime” now slides the staff dropdown (and hours) in cleanly instead of the cramped 3-column layout.
- **More readable labels** — detail-page captions (Type, Amount, Received, …) and table headers are darker and slightly larger in both light and dark mode, for easier reading.

## 1.5.2 — 2026-06-26
**Working-time accuracy + fund-sequence and label fixes**
- **Every fund now has its own annual sequence** (starts at 1, resets each year) — including each hospital fund, which runs on its own separate sequence. No more shared counters.
- **Renamed “Dev” → “GF 20%”** across the Accounting funds panel (column, checkboxes, hints), now that all funds have their own sequence.
- **Held time is now working-time** — the Concerned-staff “(holding now)” figure and per-person held totals only count office hours. (Fixes e.g. a document received at 10 PM showing 11 hrs held overnight; it now shows the real working time from 8 AM.)
- **Daily working time** now shows the **actual window per day** (e.g. “8:00 AM – 5:00 PM”, or a mid-day start/stop) alongside the hours.
- **Holidays:** added an **“Other”** type (with your own label) for non-working days that aren't a holiday or suspension.
- **Fixed:** the Calendar-display selector's chosen option was unreadable (blue-on-blue); it's now clear.

## 1.5.1 — 2026-06-26
**Work-calendar polish: month-grid view, Super-Admin access fix, display toggle**
- **Modern month-grid calendar** for the Holidays and Department calendars — colored entry chips, month navigation, click a day to add an entry on that date.
- **Super-Admin display toggle** (Work Hours → Calendar display): **Grid / List / Form-only**. Grid and List are functionally identical.
- **Fixed:** Super Admins (who may have no home office) got a 403 on the Department Work Calendar — they can now pick which department to manage; managers stay locked to their own office.
- Confirmed **backdating is retroactive**: recording past leave/undertime/day-offs recomputes every document's working time live.

## 1.5.0 — 2026-06-26
**Working-hours engine: pending time counted in real work time + holiday/leave calendars**
- **Work Hours (Super Admin):** set the office schedule (default 8:00 AM–5:00 PM, Mon–Fri, 12:00–1:00 lunch = 8 hrs/day). A master toggle makes the system count **Holding / Idle / Turnaround** in working hours only — skipping nights, weekends, lunch, holidays and approved leave. Off by default (keeps plain calendar time until you enable it).
- **Holidays & Suspensions (Super Admin):** manage public holidays and emergency work suspensions; **2026 Philippine holidays preloaded** (run once: `php artisan db:seed --class=PhilippineHolidaySeeder --force`). No working hours are counted on these, for everyone.
- **Department Work Calendar (per-user toggle “Can manage work calendar”):** lets a trusted staffer record, for their own office, **department day-offs** (seminar/team-building, *additional to* the global holidays), **staff leave**, and **staff undertime** (hours actually worked). Every entry **requires a reason**, is stamped with who set it, and is written to the **activity log** — so exclusions can't be made silently.
- **Per-document daily breakdown:** optional “Daily working time” panel (e.g. Mon 2h, Tue 8h) on each document — hidden by default, enabled by a Super-Admin toggle.
- Detail layout reworked into clean full-width bands; tracking slip shows Fund without the code.

## 1.4.5 — 2026-06-26
**Bank-grade document detail; tracking slip fund tidy**
- **Document detail redesigned** into clean bordered section panels, each with an icon header and a tidy label/value grid — organized and uncrumpled, in keeping with the system theme. On desktop the panels sit **two-up** to use the width well: **Document | Description** and **Accounting | Timeline**; they stack on mobile.
- **Tracking slip** now shows the Fund name without the code in parentheses (e.g. “General Funds” instead of “General Funds (101)”).

## 1.4.4 — 2026-06-25
**Detail layout simplified; form fields no longer hang; live fund-code preview**
- **Document detail** is now plain and clean — a simple two-column spec list with hairline dividers, no boxes, no oversized Amount. Just organized rows under Document / Accounting / Timeline.
- **Encode form** no longer leaves half-width fields stranded: Document Type spans the full width, and Reference No. fills the row when Priority is hidden.
- **Fund code preview updates live** — picking a fund shows e.g. `221-2026-06-N` (the `N` running number is assigned on save, since the real sequence depends on who saves first).

## 1.4.3 — 2026-06-25
**Redesigned document detail & encode form — spec-sheet layout, hero Amount**
- **Document detail** reworked from labeled boxes into a clean **spec-sheet**: muted labels left, values right, hairline dividers, two columns, with accent-marked **Document / Accounting / Timeline** sections. The **Amount** is now a hero figure with the Fund beside it.
- **Encode form** groups all the Voucher/Payroll inputs into a single **“Accounting details”** panel that slides in when you pick Voucher or Payroll — so the form no longer reads as an endless stack as fields grow. Amount gets a ₱-prefixed hero input.

## 1.4.2 — 2026-06-25
**Accounting polish: Payroll funds, RC display, amount masking, cleaner detail layout**
- **Payroll now uses Funds too** — the Fund picker and fund-based tracking code apply to both Voucher and Payroll. They stay distinct in the database via the `document_type` column, so reports and filters can separate Vouchers from Payrolls.
- **Responsibility Center** is shown as **`[code]/[office]`** on the document detail page (your typed Code + the Office/Unit/Project name).
- **Amount field auto-formats** with thousands separators as you type (e.g. `165,000.00` instead of `165000`); the raw number is still what gets stored.
- **Modernized the document detail panel** — grouped into **Document / Accounting / Timeline** sections with clean cell cards, a prominent Amount, and better spacing now that there are more fields.

## 1.4.1 — 2026-06-25
**Fix: Accounting features are now driven by a database toggle, not a hardcoded office code**
- **Root cause of the missing 1.4.0 features:** the Accounting logic was matched to a hardcoded office code (`PACCO`), but the real office is `OPAcc`, so nothing activated — no Voucher/Payroll-only list, no Fund picker, no Amount/OBR/RC/Nature fields.
- **New “This is the Accounting office” toggle** on each Department (Departments → edit). Turning it on makes that office encode **only Voucher & Payroll**, show the **Fund** picker, generate the fund-based tracking code, and reveal the **Amount / OBR / Responsibility Center / Nature** fields. Ticking it **auto-creates** that office's Voucher & Payroll types; unticking it falls back to the global type set.
- **New “Hospital transactions division” toggle** on each Division — replaces the old hardcoded `FHTD` code. When on, encoders there get **General Fund & Trust Fund only**, their **own sequence**, and an **“-H”** suffix.
- All accounting checks now read these DB flags (`is_accounting`, `is_hospital`) — no magic office/division codes anywhere.
- **After upgrading:** open **Departments → (your Accounting office) → tick “This is the Accounting office” → Save.** For hospital handling, tick “Hospital transactions division” on its FHTD division.

## 1.4.0 — 2026-06-25
**Accounting office: per-office document types, fund-based tracking codes, voucher/payroll fields & a setup module**
- **Per-office document types.** Each office can now have its own set of document types. The Accounting office (PACCO) starts with **only Voucher and Payroll**; every other office keeps the shared global set unchanged. (Built so more office-specific types can be added later from the Document Types admin.)
- **Fund dropdown on Vouchers.** When an Accounting user encodes a **Voucher**, a **Fund** picker appears: General Funds (101), SEF (221), Trust Fund (401), Gen. Fund 20% Development Fund (101).
- **Auto-generated fund tracking code** in the format `[Fund code]-[Year]-[Month]-[sequence]` — e.g. an SEF voucher in June 2026 → `221-2026-06-8`. The sequence **resets every year**.
  - **General / SEF / Trust share one running sequence** (the next number is the next number regardless of which of the three).
  - The **20% Development Fund keeps its own separate sequence** — even though it shares code 101 with the General Fund, the system tells them apart behind the scenes.
- **Hospital division (FHTD).** Users in the Accounting *For Hospital Transaction Division* see **only General Fund and Trust Fund**, run on **their own sequence**, and every code gets an **`-H` suffix** — e.g. `101-2026-06-188-H`, `401-2026-06-817-H`.
- **Voucher / Payroll fields.** Selecting Voucher *or* Payroll reveals: **Amount (₱, required)**, **OBR No. (required — may be “N/A”)**, **Responsibility Center** as Office/Unit/Project (dropdown) **+ Code**, and **Nature of Transaction** (dropdown). These show on the document details and the printable slip.
- **Accounting Setup module** (Department/Assistant Heads) — one page to manage **Funds** (name, code, dev-fund & hospital flags), **Responsibility Centers** (Office/Unit/Project + code) and **Nature of Transaction** options that feed the encode form.

## 1.3.0 — 2026-06-24
**Per-user capabilities, role-scoped visibility, searchable routing dropdowns & QOL**
- **Per-user capabilities replace the “Receiving Staff” role.** That role is retired; everyone is **Staff** plus three Super-Admin toggles per account (shown as clean switches on the user form): **Can encode**, **Can transfer to another office**, **Can claim from another office**. Encoders automatically assign & release their own drafts. Existing Receiving Staff were migrated automatically with the matching toggles on.
- **Role-scoped visibility.**
  - **Division Head:** sees only their division — documents in it, that concern them, or that concern their division's staff.
  - **Department Head / Assistant Head:** sees the whole department (all divisions) + anything concerning their staff, even after it moves to another office.
  - **Everyone else:** only documents that concern them.
  - All roles still see any document that **personally concerns them**, across offices.
- **Searchable Forward & Transfer dropdowns** — type to filter by name/office, so picking from many users/offices is instant.
- **Mobile “Last action” fixed** — no more “0 seconds ago ago”.
- **Tracking history collapses** when long (shows the latest 5 with a “Show all” toggle) on desktop and mobile.

## 1.2.0 — 2026-06-24
**Digital Copy + Supporting Documents, possession-on-transit fix, paused total, real-time chat, slip & UI polish**
- **Transit timing fixed (again, properly).** The running “holding now” clock always follows whoever **physically has the document**. When a document is rejected and is on its way back, the clock is on the **rejecter** until the sender receives it; during any forward/release it's on the **sender** until the recipient accepts. No more “holding now” on someone who hasn't received it.
- **“Attachments” split into two clear features (each a Super-Admin toggle):**
  - **Supporting Documents** — a **required title** with an **optional** PDF or captured pages; add as many as you like. Every item (file or not) must be **ticked on hand-over** by the sender, and by the receiver to accept (or reject).
  - **Digital Copy** — the **encoder's** single digitized original (PDF or camera→PDF, 2 MB). Everyone concerned can view it; it does **not** affect receiving/rejecting.
- **Total paused time** now shows on the document details (overall time the document sat pending), alongside age and turnaround.
- **Real-time-ish chat while idle** — the unread badge (top bar + chat bubble) refreshes every ~10s and instantly when you return to the tab; the open chat list refreshes itself, so new direct/group messages show up without reopening. Still poll-based and lightweight.
- **Modern file pickers** everywhere (drag-style, shows the chosen filename), and **“Upload PDF” renamed to “Browse PDF File.”**
- **Dark mode:** the holder's timer text is now clearly readable (was washed out).
- **QR slip:** “Document Tracking Slip” is now a blue pill **above the Document Code** (out of the header), and the **scan URL is back** just above “Powered by PICTO.”

## 1.1.1 — 2026-06-24
**Attachment-possession fixes, rejection-return flow, slip redesign**
- **Attaching now requires real possession.** Only the encoder *while drafting* or the person who has actually **received** the document can attach files. **Nobody can attach while it's in transit** (released/forwarded) or before clicking **Accept & Receive** — fixes cases where the sender or a not-yet-received recipient could attach.
- **“Holding now” timing fixed.** While a document is in transit, the running clock is correctly attributed to **whoever sent/forwarded it** (it's their duty until the recipient receives) — not the recipient. Once the recipient accepts, the clock moves to them.
- **Rejection return flow.** When a document is rejected for a missing item, it returns to the sender; the sender re-scans and sees the **rejection reason**, and can **receive it back even incomplete** (acknowledging the rejection) to handle it internally. The full history + possession ledger show who last held the missing item for accountability.
- **QR slip redesigned (shorter & clearer):** “Document Tracking Slip” stays in the header panel; below it a **“Document Code”** label sits above the code; the **QR is 30% smaller**; the gray panel now holds **Type, Source/Origin (e.g. “PICTO - Provincial Information and Communications Technology Office”), and Encoded date/time** with a bottom border; and the long scan blurb/URL is replaced with a simple **“Powered by PICTO.”**

## 1.1.0 — 2026-06-24
**Document attachments & physical handover verification + QOL**
- **New: document attachments** (Super-Admin toggle, off by default). The holder can attach scanned files with a **title** each:
  - **Desktop:** upload a **PDF** (max 2 MB).
  - **Mobile:** **capture pages with the camera** (cover page + more) — they're combined and **saved as one compressed PDF** server-side (no app needed). Images are downscaled and JPEG-recompressed to stay small without hurting readability.
- **Handover verification.** When attachments exist, hand-over becomes a checklist:
  - The **sender** must tick the main document + every attachment as physically attached before they can forward/release/transfer.
  - The **receiver** ticks each item physically present; **Accept & Receive** only unlocks when everything is ticked. If something's missing they **Reject** (reason required), which **returns the document to the sender** to sort out — the rejecter can no longer act, and the sender can re-scan to receive it back.
- **Wider visibility:** all concerned staff **plus the department head of each concerned office** (even across two departments) can see the document and open its attachments.
- **QR slip redesign:** the system **logo** now sits on top with **“Provincial Government of Cavite”** shown large and prominent.
- **Voucher number confirmation:** voucher documents now require re-typing the number (paste disabled, live match check) to prevent mistakes from the physical voucher.
- **Documents list:** distributed documents now show **where** they went (department / division / N people) instead of just “Distributed”.
- **Chat spacing** between messages increased for readability.

## 1.0.3 — 2026-06-23
**Chat bubble sizing — definitive fix, group name polish**
- **Chat bubbles now shrink to their text for real.** Bubbles use `width: fit-content` via an inline style (immune to CSS purge/flex stretch), so a one-word message like “test” is a small bubble — fixed on the Messages page and the floating widget.
- **Message bodies are trimmed** (leading/trailing spaces & stray newlines), and existing messages were cleaned up, so old oversized bubbles collapse to the right size.
- **Group sender names are clearer** — readable size in the office theme colour (not faint), and in a **department group** each sender shows as **“Name · DIVISION”** so you can tell who's from where; one-to-one chats stay name-free.

## 1.0.2 — 2026-06-23
**Chat bubble sizing, scope/role controls & group chats**
- **Fixed chat bubble sizing** — short messages (e.g. “test”) no longer balloon into a large box; bubbles now shrink to fit their text on both the Messages page and the floating widget.
- **Chat scope control** (Super Admin → Settings → Workflow) — choose whether staff can message **anyone across offices** or **only within their own office**.
- **Exclude roles from chat** — bar specific roles (e.g. Governor, Vice Governor, Chiefs of Staff) from messaging entirely; they can't chat and don't appear as someone to message.
- **Group chats** — start a **Division** or **Department** group from the New-chat menu (page and widget). Membership is auto-filled from that unit (excluded roles skipped) and re-used rather than duplicated.

## 1.0.1 — 2026-06-23
**Chat layout fix & faster delivery**
- **Fixed message alignment** — received messages now align cleanly on the left (they were rendering right-aligned), with proper bubble “tail” corners and tidy timestamps.
- **Sender names hidden in one-to-one chats** (shown only in group chats), removing clutter.
- **Faster, lighter delivery** — replaced the fixed 4-second refresh with **adaptive polling**: new messages arrive in about **2 seconds** while you're actively chatting and ease back to ~6 seconds when idle (snappier when it matters, lighter when quiet). Polling still pauses when the tab/widget is closed.
- Applied to both the full **Messages** page and the floating chat bubble.

## 1.0.0 — Initial Release (2026-06-23)

The first official release of the **PGC Document Tracking System**. Everything below
this entry (the `v2.x` numbers) was **internal pre-release development** — those
iterations are kept as a working history. The system is versioned starting at **1.0.0**
now that it is ready for real use; a `2.0` will come after months of production use.

**What the system does**
- **QR-based document tracking** — encode a document, generate a QR slip, and follow
  it as it's released, received, forwarded, transferred between offices, and completed.
- **True possession timing** — time is attributed to whoever physically holds a
  document; per-staff "time held", an **Aging & Bottlenecks** report, and a **Pending
  (pause)** state for waiting on others.
- **Accurate, possession-gated actions** — staff can only act on a document once they've
  **received it by scanning the QR** (override is Super-Admin only).
- **Distribution & acknowledgements** — send a document to selected people, a division,
  or a whole department for acknowledgement, and track who has seen it.
- **Route slips** — one QR can carry several documents, each cleared or rejected
  individually *(toggle)*.
- **Linked documents** — cross-reference related documents *(toggle)*.
- **Batch receive** — receive a stack of QR-tagged documents at once *(toggle)*.
- **In-app messaging** — a Messenger-style chat (sidebar page **and** a floating bubble
  on every screen) with live unread badges *(toggle)*.
- **Reports** (HTML + colour PDF), **dashboard** with action queues and insights,
  **multi-department** routing, **roles & permissions**, **per-account encode rights**,
  audit logs, in-app documentation, theming/branding, 4-hour sessions, and a clean
  mobile experience.
- **Super-Admin feature toggles** for priority, route slips, batch receive, document
  linking, and messaging — turn capabilities on/off without code.

> **Real-time note:** messaging delivers via lightweight polling (a few seconds), so it
> runs on the current IIS + Cloudflare setup with no extra infrastructure. It can be
> upgraded to instant WebSocket push (Laravel Reverb) later without data changes.

---

# Pre-release development history

_The entries below used `v2.x` numbers during development and are retained for reference._

## v2.9.0 — 2026-06-23
**In-app messaging (chat) + feature toggles**
- **New: in-app messaging.** A clean, modern **Messages** area lets staff chat with colleagues (great for following up or asking about a document). Direct conversations, a searchable “New chat” picker, a live **unread badge** in the top bar and sidebar, and **near-real-time delivery** (new messages appear every few seconds while a chat is open — no extra server software required). Off by default.
- **Floating chat bubble on every page** (Messenger-style) — open, read, and reply to chats from the documents page or anywhere else without leaving what you're doing. Includes a “New chat” picker and an “open full page” shortcut; syncs with the same live unread badge.
- **Three new Super-Admin toggles** (Settings → Workflow):
  - **Batch receive** — on/off (default on).
  - **Link related documents** — on/off (default on).
  - **In-app messaging (chat)** — on/off (default off).
  When a feature is off it's hidden everywhere and its routes are blocked.
- Documentation: added a **Messaging (Chat)** guide.

> **Note on real-time:** messaging uses lightweight polling (a few seconds) so it works on the current IIS + Cloudflare setup with **zero extra infrastructure**. If you ever want instant (sub-second) push, it can be upgraded to WebSockets (Laravel Reverb) later — same UI, no data changes.

## v2.8.4 — 2026-06-23
**Dashboard redesign, batch receive, linked documents, distribute polish**
- **Dashboard reorganised** — your **action queues come first** (right after the stat cards, in a clean two-column grid with a count badge), then **Insights** (charts), then **Recent activity**. Quick actions (Encode, Batch receive, All documents) moved to the header.
- **New: Batch receive.** Receive a whole stack of QR-tagged documents at once — scan them one after another with a handheld scanner (auto-ticks each) or tick them from the list, then receive all in one click. Button on the Dashboard.
- **New: Link related documents.** Connect a document to another by tracking code; the link shows on both. You can only link documents you have access to (your office or ones that concern you).
- **Distribute polish:** people already asked to acknowledge are now removed from the “distribute to more people” list (and skipped server-side), and the button is reworded on repeat use.
- **Pending now blocks acknowledgement** — while a document is paused, recipients can't acknowledge until it's resumed (and all timers stay frozen).
- **Documentation expanded** — new guides for route slips & splitting, distributing & acknowledgements, linking documents, and batch receiving.

## v2.8.3 — 2026-06-23
**Acknowledgement targeting fix, combobox styling, archive vs complete clarity**
- **Fixed: distributing a document no longer makes everyone an "acknowledger."** Previously distributing flipped the whole document into a broadcast, so every prior holder (and even the distributor) showed “waiting to acknowledge.” Now only the **specifically chosen recipients** are asked — tracked via a new “acknowledgement requested” marker. The distributor and earlier handlers are unaffected.
- **Pausing (pending) now freezes acknowledgement timers too** — for the holder and all distribution recipients.
- **Fixed the search dropdowns' appearance** — the Office and Assignee pickers now have a proper border/background (they were rendering borderless/“floating”).
- **Archive vs Complete is now explicit.** Closing a document offers two clearly described choices: **✅ Completed** (the task is fully done) or **🗄 Archived** (closed without completion — cancelled, duplicate, no longer needed). The closed banner and history say which one applies.
- **Mobile QR view:** the confusing **“From”** field is now **“Origin (encoded by)”**, distinct from the “last handled by” line.
- Acknowledgement wording is now generic (“document”, not “memo”), since distributed items aren't always memos.

## v2.8.2 — 2026-06-23
**Acknowledgement flow, strict possession rules, encode-per-account & fixes**
- **Critical fix — actions only appear once you physically hold a document.** Previously a Department Head saw Assign/Release/Forward/Archive on a document merely *assigned* to them. Now **everyone** (heads included) can only act after they've **received** it by scanning the QR. Override is limited to **Super Admin**. *Release* is now strictly the encoder's action.
- **Acknowledgement now works for distributed documents.** Recipients can acknowledge by **scanning the QR** (mobile); desktop acknowledgement is blocked unless “desktop receive” is enabled — matching how physical receipt works. The dashboard now lists **“Waiting for your acknowledgement.”**
- **Encode permission is now per-account.** A Super-Admin toggle on each user (“Can encode documents”) controls who may add documents — independent of role. Existing encoders keep their access automatically.
- **New: receiving staff can distribute a document for acknowledgement** — to selected people (across divisions), a whole division, or the entire department — even after it has already passed through several people.
- **Per-recipient acknowledgement timing** — the Concerned-staff panel shows how long each person took to acknowledge, or how long they've been waited on.
- **“Send to selected people” limited to your own office** (across its divisions).
- **Fixed: editing a user showed Division as “None”** even when one was set — the division now preselects reliably.
- **Searchable dropdowns** for the assignee and source-office pickers on the encode form (handles many users/offices).
- **Mobile QR view now shows the last handler and their note**, plus the latest action.
- Wording: “broadcast memo” → “distributed to multiple people” (documents aren't always memos).

## v2.8.1 — 2026-06-22
**Route-slip items, clearer reports, action icons & fixes**
- **New: Route-slip multi-document tracking** (Super-Admin toggle in **Settings → Workflow**, off by default). One document/QR can list several individual documents; the holder marks each **Cleared** (good to go) or **Rejected** (returned to origin), so partial outcomes — e.g. 4 cleared, 1 rejected — are tracked, with reasons and an audit trail. Add the list on the encode form; decide per item on the document page.
- **Fixed: the “Aging & Bottlenecks” report crashed** (`Undefined variable $stats`) — the summary's statistics card had leaked into the aging branch. Fixed.
- **Aging report now has a summary at the top** — counts of how long active documents have been sitting with their current holder, bucketed: under 1 hour, 1–8 hours, 8–24 hours, 1–3 days, over 3 days.
- **Clearer, consistent report terminology** everywhere: **Total documents** (all), **Active (ongoing)** (in progress, not paused), **Pending (paused)** (on hold), **Completed / Archived**.
- **“Send to selected people” is now limited to your own office** (across its divisions), not other departments.
- **Per-person “time held” now shows for older/completed documents too** — the possession history is rebuilt from each document's audit trail.
- **You can no longer forward or re-assign a document to the person who already holds it** — the current holder is excluded from those lists (and blocked server-side).
- **Resume now requires remarks.**
- **Action buttons have clean icons** for quick recognition, and the **“Last action” panel was redesigned** into a tidier card.

## v2.8.0 — 2026-06-22
**Possession timing, pending workflow, multi-send, priority toggle, longer sessions**
- **Per-holder time tracking.** Every document now keeps a possession ledger — the time a document spends is attributed to whoever **physically holds** it. While a liaison/encoder still holds an assigned-but-not-yet-received document, the clock counts against *them*; once the recipient **receives** it, the clock moves to the recipient, and so on. This nudges encoders to actually hand documents over.
- **Concerned-staff panel now shows time held** — each person’s total holding time, with the current holder marked “holding now”.
- **New “Pending” action.** A holder can mark a document **pending** (awaiting the origin / someone else); this **pauses their timer** and drops the document out of the aging report. Remarks are required. Resume puts the clock back on. With cross-office routing on, they can **return it to another office** — the clock then starts against that office once they receive it.
- **New report — “Aging & Bottlenecks.”** Lists every open document **oldest-first** (excluding pending ones), showing total lifetime, **who currently holds it**, and **how long it’s been with that holder** — so you can see exactly what’s stuck and where.
- **Send to selected people (across offices).** A new distribution option lets you hand-pick multiple recipients from any office; each is notified and acknowledges receipt individually, tracked just like a memo.
- **Forwarding is now strictly within your own office.** Only **receiving staff** can release/transfer a document to **another office** (its receiving pool). Regular staff forward within the office only.
- **Priority is now optional.** A Super-Admin toggle in **Settings → Workflow** enables/disables the Priority field everywhere — encode form, lists, filters, document details, dashboard and reports. It is **off by default**.
- **Remarks are required to archive/complete** a document.
- **Longer sessions** — you now stay signed in for **4 hours** of inactivity (was 2) on both desktop and mobile.

## v2.7.0 — 2026-06-22
**Clean mobile login + assignment fixes**
- **Mobile login rebuilt clean.** Removed the background image/glass card on phones in favour of a plain white, **vertically-centered** form that **never scrolls** — sized to the dynamic viewport (`100dvh`) so the address bar can’t push it off-centre.
- **Fixed invisible login icons** — the user/lock/eye (show-password) icons used a non-existent size class and rendered at zero size; the show-password toggle is back.
- **You can no longer assign a document to yourself** — the encoder is excluded from the assignee/forward lists (you assign to *someone else* in your office).

## v2.6.9 — 2026-06-22
**Login no-scroll, claim permission, self-exclude**
- **Mobile login no longer scrolls** — the page is locked to the visible viewport height so the form stays centered on tall phones (e.g. OnePlus/Brave).
- **New “claim” permission.** Only **receiving-type staff** (Receiving Staff, Division/Department Heads, Chiefs of Staff, Super Admin) can claim a document from another office’s pool. Regular staff can still receive what’s directly assigned to them, but can’t claim office transfers.
- Excluded the current user from the **assignable staff** list.

## v2.6.8 — 2026-06-21
**Login centering + mobile notification dropdown**
- Fixed the modern glass card not centering on mobile. The taller card could overflow the viewport, which broke flex centering. It now sits in a centering wrapper that **centers the card when it fits and lets the page scroll when it doesn't** — correct on any phone height. The branded background is fixed so it stays put while scrolling.
- Fixed the **notifications dropdown overflowing the screen on mobile** — its width is now capped to the viewport so it no longer gets clipped on the left edge.

## v2.6.7 — 2026-06-21
**Modern login design**
- Refreshed the sign-in UI: a **glassmorphism card** (frosted blur, subtle border, soft shadow), a gradient **logo badge**, **inputs with leading icons** and soft focus rings, a show/hide password toggle, and a **gradient "Sign in" button** with a hover lift. Mobile keeps the vertically-centered card over the branded background; desktop keeps the split-screen.

## v2.6.6 — 2026-06-21
**Mobile login: centered card**
- The mobile sign-in card is now a floating card **vertically centered** over the branded background (logo + name + form inside), not pushed to the top or bottom. Uses dynamic viewport height so it's truly centered within the visible area on phones.

## v2.6.5 — 2026-06-21
**Mobile login redesign + accurate routing status**
- **Mobile login redesigned** into a proper branded layout: the uploaded background image with the gradient overlay fills the screen, branding sits on top, and the form is a clean white bottom-sheet (rounded top) anchored to the bottom — no more floating box on empty space. Desktop split-screen unchanged.
- **"Currently with" is now accurate through the whole flow.** A document only shows a person as the holder once they've **received** it. Before that it shows the true state:
  - *Pending Release* (assigned, not handed over)
  - *In transit* — released/forwarded to X, **awaiting receipt**
  - *Awaiting claim* — transferred to an office pool, not yet claimed
  Applied to the document detail page and the documents list.

## v2.6.4 — 2026-06-21
**Routing clarity & report fixes**
- **"Currently with" no longer implies possession before release.** A document that is *Pending Release* but already assigned now shows **"Pending release — Assigned to X, not yet handed over"** (both on the detail page and the documents list), instead of looking like the recipient already has it.
- **Documents by Division report fixed** — the table now has a **Division** column and a **By Division** chart (previously you couldn't see the division at all).
- **Staff Workload is now clearly per staff member** — ranked by number of documents held, with each person's Office · Division shown (it was already grouped per holder, but the layout made it look division-based).
- **Mobile login vertical centering fixed** — uses dynamic viewport height (`100dvh`) so the form is centered within the *visible* area on phones, not pushed below the browser's address bar.

## v2.6.3 — 2026-06-21
**QOL: wording, sticky sidebar, safer reset, mobile login**
- **"Draft" is now shown as "Pending Release"** everywhere (badges, filters, reports, charts). The stored value is unchanged — only the label.
- **Sidebar is now sticky** on desktop — it stays put while the page content scrolls.
- **Danger Zone → "Delete all users"** now also clears all documents/history, leaving **only the Super Admin** (previously it could orphan documents or fail on foreign keys).
- **Mobile login redesigned** — the uploaded background image with the gradient theme overlay now shows on phones, with the sign-in form in a centered, elevated card (was a plain off-white, poorly centered screen).

## v2.6.2 — 2026-06-21
**PDF charts fixed + completion-time statistics**
- **Fixed: pie charts were missing in report PDFs.** DomPDF can't render inline SVG reliably, so pies are now drawn as PNG images via PHP GD — they always print, in colour.
- **New statistics** on reports:
  - *Summary*: average completion time, fastest, slowest, completed count, open count, and average age of open documents.
  - *Processing Time & Overdue* (Accounting): average completion time, average days over the limit, and worst overshoot — alongside the existing on-time rate.
- Completion time is measured from when a document is received (or encoded) to when it's completed/archived.

## v2.6.1 — 2026-06-21
**Report security fix, redesigned login & report polish**
- **Security fix (important):** reports were not scoped — any user could generate reports (and PDFs) containing **other offices' documents**. Reports now respect document visibility: non-executive users only ever see their **own office's** documents, divisions and statistics.
- The **division filter** on Reports now lists only **your own office's divisions** (not every division in the system).
- The **Processing Time & Overdue** report is now hidden from offices that don't have a processing time configured (e.g. PICTO) — it only appears for offices that have one (Accounting) or users who can see all departments.
- **Fixed broken icons** on the *Pending*, *Open within time* and *Open & overdue* stat cards (incomplete clock glyph).
- **"Allowed" / "Days taken"** columns now spell out **days** (e.g. "7 days").
- **Fixed** sample vouchers showing **0 concerned staff** (assignees weren't attached).
- **Redesigned the login page** — modern split-screen: branded gradient panel (logo, system name, feature highlights) beside a clean sign-in card, full-height, responsive, with a branded primary "Sign in" button.

## v2.6.0 — 2026-06-21
**Reports with charts & statistics, friendlier wording, QOL**
- **Every report now has charts + statistics**, not just the summary: pie/doughnut breakdowns (status, priority, type), bar charts (division, staff workload), stat cards, and an on-time-rate statistic on the Processing Time report.
- **PDF exports print in color** — charts are server-rendered as **SVG pies + colored bars** (DomPDF can't run JavaScript), with colored stat cards and table headers. What you see on screen prints correctly.
- **Renamed "SLA" → "Processing Time & Overdue"** everywhere — plain wording aligned with the Citizen's Charter / ARTA "prescribed processing time" that staff already understand.
- **Overdue is Accounting-only by design** (only offices with a configured processing time are evaluated; currently PACCO at 7 days). Added richer sample data covering all four states: on-time, completed-late, open-within-time, open-overdue.
- **Login: show/hide password** toggle so users can check what they typed.
- **QR slip is now "print once, valid forever"** — it shows only the permanent **Origin office** + identity; the current location is *not* printed (it would go stale on every transfer). The QR always resolves to the live current holder, office & history, so HR/Accounting never need to reprint just because the document moved.

## v2.5.1 — 2026-06-21
**Detail-page clarity, routing guardrails & slip redesign**
- Document details now show a clear **"Last action"** line (e.g. *Received by HR Staff · 54 seconds ago*) so the most recent movement is unambiguous — the static *Received*/*Released* fields are first-touch timestamps and could look out of order.
- **Transfer to another office** no longer lists the office that already holds the document (you can't transfer a document to its own office); blocked server-side too. Use **Forward/Assign** to route within an office.
- **Fixed**: after transferring a document to another office, the original encoder could still see an **Assign / Re-assign** panel listing their own office's staff. Assignment is now allowed only while the document is still **inside your own office** (override roles excepted).
- **Receiving/claiming now requires scanning the QR** on the physical document (proving it's physically present), unless *Allow desktop receive* is explicitly enabled in Settings. This applies to cross-office **claims** too.
- **Redesigned the QR Tracking Slip** — clean header, prominent tracking code + priority chip, an **Origin → Current office** routing strip, and relevant facts (type, voucher/reference, source, encoded date). Removed the meaningless *"Assigned to: Unassigned"* line.
  - **Colors now print** (added `print-color-adjust: exact`) — the header/chip no longer drop to white on paper.
  - **Origin is permanent; Current office is a snapshot** stamped *"as of <print time>"*. The QR itself never changes and always resolves to the **live location & history**, so it stays correct no matter how many times the document is transferred (e.g. PICTO → OPG → Accounting → OPG …).
  - Source / Origin shows the **department only** (no division).
- Encode page: a concise **note in Source/Origin** explaining you can skip it when transferring to another office, plus a modern numbered-step layout.

## v2.5.0 — 2026-06-21
**Office transfer & claim (receiving pool)**
- New **"Transfer to another office"** action — sends a document to an office's **receiving pool** (no specific person needed). Requires the cross-department setting.
- That office's receivers see it under **"📥 To claim"** on their dashboard and get a notification. **Whoever claims it** becomes the holder and the only one with actions; it's recorded against them. Others stop seeing it as claimable.
- Documents list shows **"📥 To claim · OFFICE"** for unclaimed transfers; the claim is a one-tap **Claim & Receive** (works on desktop or via QR scan).
- Encode page: **direct assignment is own-office only** (division → staff); the **Source/Origin** section hides when transferring out (your office is auto-recorded as the origin).

## v2.4.0 — 2026-06-20
**Encode page redesign + cross-department control**
- **Redesigned Encode page** into clean sections (Document details · Source/Origin · Distribution) with a modern look.
- **Source / Origin**: division stays empty until an office is chosen; your **own office is listed first**; *Other/External* swaps to a free-text field.
- **Distribution**: assign within your office (**division → staff**), or send as a memo. The stray standalone Division field was removed.
- **Cross-department setting** (System Settings → Workflow): OFF = assign only within your own department; ON = route to any office. Applies to encoding, assigning and forwarding.
- Added **sample data**: a department memo broadcast to 15 staff (partly acknowledged) and two Accounting vouchers breaching the 7-day SLA — to preview the concerned-staff panel and the SLA report.

## v2.3.0 — 2026-06-20
**Inter-office routing & encoding improvements**
- **Source / Origin** is now a smart picker: choose the originating **office → division**, or pick **Other / External** to type an external client's name.
- Groundwork for inter-office routing (finalized in v2.5.0: direct assignment stays within your own office; sending to another office goes through that office's receiving pool).
- Unassigned documents now correctly show **"Not yet assigned · In transit"** instead of a misleading department/division.
- **Concerned staff** panel handles large lists cleanly: a count, an acknowledgement progress bar (for memos), and a **Show all** toggle beyond 12 people.
- _Who can encode_ is controlled by the **`documents.create`** permission per role (Roles & Permissions) — already in place.

## v2.2.2 — 2026-06-20
**Polish & admin tools**
- Redesigned the document **Information** panel — a clear "From → Currently with" routing highlight, grouped facts, and a tidy timeline section.
- **Logs filter** rebalanced (combined date range).
- Clearer **notification wording** (e.g. memos show "📣 New memo by …").
- **Danger Zone** expanded: Super Admin can delete all documents, users (keeps Super Admins), divisions, departments — or reset everything — to prep for real data.

## v2.2.1 — 2026-06-20
**Department/division visibility for inter-department tracking**
- Documents table now has an **Origin (from)** column and shows each person's **department · division** under the origin and current holder.
- Document details show **Current location**, origin & current holder units, and each **Concerned staff** member's unit (with memo acknowledgement status).
- **Tracking history** and the **activity log** now show the department · division of who acted and who received (e.g. "PICTO · TECHSUP → … · PICTO · DBA").
- The mobile scan page shows holder/origin units too.
- **Filter layout** rebalanced — date From/To combined into one tidy "Date range" field (no more lonely field).

## v2.2.0 — 2026-06-19
**SLA report, fixes & UI polish**
- **SLA / Turnaround report** — set a per-department SLA (e.g. vouchers must finish in 7 days) under Departments → Edit; the report shows on-time vs overdue (completed and still-open), with PDF export.
- **Fixed**: Super Admin no longer sees nonsensical actions on a completed document (the policy is now respected for document actions).
- Division edit shows its department as fixed (no dropdown), and Cancel returns to the department page; the standalone `/divisions` page is gone.
- **Edit/Delete are now proper icon buttons** everywhere (no more plain text links).
- Division staff counts and department user counts are clickable → filter the Users table.
- Cleaner "Updated" column (relative + exact time).
- Reset demo data to a realistic multi-department set (PICTO, PACCO, OPG, OPVG, PHRMO, SP) each with their own divisions.
- Plainer wording for the completion-deadline (SLA) setting + a modern **chip multi-select** for the document types it applies to.
- **Dependent dropdowns**: pick a department first and the division list updates (Documents filter, Users filter, and the user form).
- Consistent alignment in the documents "Updated" column.
- **System Settings → Danger Zone**: Super Admin can delete all documents & activity to start fresh (keeps users/departments/settings).

## v2.1.0 — 2026-06-19
**Structure & document types**
- **Divisions are now managed inside their Department** (the standalone Divisions menu was removed; edit a department to add/edit/remove its divisions).
- **Users table** now shows Department + Division, with a department filter.
- **Document Types module** (Super Admin) — CRUD your own types, mark which show a voucher-number field, and scope a type to a specific department or all.
- **Reopen** — Super Admin can bring an accidentally completed/archived document back to active.
- Department form clarified (Abbreviation/Code + Full Name).
- Cleaner, consistently top-aligned table columns.
- _Deferred to next: per-department SLA / overdue (e.g. 7-day voucher) report._

## v2.0.0 — 2026-06-19
**Multi-department architecture** 🏢
- **Departments** module (CRUD). Each department contains its own divisions and staff.
- **Department-scoped access** — staff and heads see documents within their own department (plus anything forwarded/released to them from other departments). Executives see across all departments.
- **New roles**: Provincial Governor, Provincial Vice Governor, Sangguniang Panlalawigan Member, Chief of Staff (OPG), Chief of Staff (OPVG), Provincial Administrator for Internal Affairs, and Division Head.
- **Memo broadcast** — when encoding, send a document as a **division** or **department** memo to everyone in scope; each recipient is notified and acknowledges receipt individually.
- Users & divisions now belong to a department; email is now optional.
- Migration is additive and safe for existing data (a backfill seeder assigns existing records to a default office).

## v1.7.0 — 2026-06-19
**Workflow integrity & dashboard finishing touches**
- **Must receive before acting** — a recipient can no longer Forward or Archive a document until they have **Received** it, keeping the audit trail intact. Out-of-flow override is limited to **Super Admin** and **Department Head** (not Assistant Department Head).
- **Clickable dashboard counters** — each stat card opens Document Tracking filtered to that stage.
- **Recent Activity** redesigned into clean full-width rows.
- **Notification dropdown** now loads live content (fixes empty dropdown when the badge updated).
- The encoder can **re-assign a released document** while it has not yet been received (fixes mis-assignments before pickup); the new recipient is notified.

## v1.6.0 — 2026-06-19
**Dashboard polish & more settings**
- Dashboard no longer shows long empty panels — empty queues collapse into a single "You're all caught up" card, and **Recent Activity** is now a full-width, height-capped scrolling panel.
- **Clickable charts & stat cards** — clicking a chart slice or a dashboard counter opens Document Tracking pre-filtered (by priority, status, or stage).
- Recent Activity redesigned into clean full-width rows.
- New **System Settings**: tracking-code **prefix**, **records per page**, **support contact** (footer), and a **dashboard announcement** banner.

## v1.5.0 — 2026-06-19
**Dashboard, dialogs & live alerts**
- **Dashboard counters** reworked into clear life-cycle stages — *Awaiting Release*, *In Transit*, *In Progress*, *Completed* — so a not-yet-released draft no longer looks like it's pending on a recipient.
- Replaced the browser's plain confirm with a **modern themed confirmation dialog** (dark-mode + theme-color aware).
- **Live notification badge** — the bell updates every 60 seconds without a page reload (lightweight polling; no WebSocket server needed).

## v1.4.0 — 2026-06-19
**Logs, notifications, timezone & quality-of-life**
- Added **versioning** + this in-app **Changelog** page (Super Admin).
- Set application timezone to **Asia/Manila (UTC+8)**.
- Activity log actions are now **human-friendly** ("Logged In", "Released Document", …).
- Added in-app **notifications** 🔔 — staff are alerted when a document is released or forwarded to them.
- **Disabled public registration** (accounts are admin-created only).
- **Documentation** module restricted to **Super Admin**.
- Document detail page hides the **Actions panel** when there is nothing to act on.
- Improved **filtering**: Documents (division + date range), Users (role + status), Logs (date range).

## v1.3.0 — 2026-06-18
**Authentication & audit trail**
- **Username-based login** (email optional, used for password resets).
- **Password reset by email** with a free-SMTP setup guide.
- Full **activity logging** — logins, logouts and every action — in Logs & History, scoped per user (Super Admin sees all; each user sees their own).

## v1.2.0 — 2026-06-18
**UI/UX polish**
- **Favicon** upload, **login background image** with gradient overlay.
- **Font-size control** in the top bar (accessibility for older users).
- **Responsive tables** (stacked cards on mobile).
- Clearer, modern **View / Edit / Delete** action buttons.
- Tracking trail ordered **newest → oldest**.
- **Confirmation prompts** on significant actions (assign/release/forward/archive).

## v1.1.0 — 2026-06-18
**Tracking enhancements**
- **Voucher** document type → voucher number becomes the QR code tail (`PGC-2026-{voucher}`).
- **Desktop-receive** setting (off by default; mobile-first).
- **Elapsed-time** indicators (age, idle time, turnaround).
- **Charts** on the dashboard and reports.
- Production **deployment guide** (IIS + Cloudflare Tunnel).

## v1.0.0 — 2026-06-18
**Initial release**
- Dashboard, Document Tracking with **QR generate / print / scan**, encode → assign → release → receive → forward → archive flow.
- Users, Divisions, Roles & Permissions, Reports (with PDF), Logs, System Settings, in-app Documentation.
- Role-based access control, dark mode, theming, runs on IIS.
