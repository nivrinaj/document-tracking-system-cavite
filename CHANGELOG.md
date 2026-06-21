# Changelog

All notable changes to the **PGC Document Tracking System** are recorded here.
Each version corresponds to a git tag (e.g. `v1.4.0`) so any release can be
reviewed or rolled back. Format based on [Keep a Changelog](https://keepachangelog.com).

---

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
