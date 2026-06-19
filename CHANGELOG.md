# Changelog

All notable changes to the **PGC Document Tracking System** are recorded here.
Each version corresponds to a git tag (e.g. `v1.4.0`) so any release can be
reviewed or rolled back. Format based on [Keep a Changelog](https://keepachangelog.com).

---

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
