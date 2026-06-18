<?php

namespace Database\Seeders;

use App\Models\DocumentationPage;
use Illuminate\Database\Seeder;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'category' => 'Getting Started', 'sort_order' => 1,
                'title' => 'System Overview',
                'excerpt' => 'What this system does and who uses it.',
                'content' => <<<'MD'
# System Overview

The **Document Tracking System (DTS)** tracks physical documents as they move through the department using **QR codes**.

## The big picture

1. A document arrives at the department.
2. **Receiving staff** encode its details and the system generates a **QR code**.
3. Staff assign the document to a person, then click **Release** and print the QR.
4. The QR is attached to the document and handed to the assigned staff.
5. The assigned staff **scan the QR with their phone**, log in, and tap **Receive**.
6. From there they can **Forward** it to someone else or **Archive/Complete** it.
7. Every action is logged so anyone concerned can see where the document is.

## Roles

| Role | What they can do |
|------|------------------|
| **Super Admin** | Everything, including users, roles, divisions and settings |
| **Department Head / Assistant Head** | See **every** document and the full history |
| **Receiving Staff** | Encode, assign, release, print QR |
| **Staff** | Receive, forward and archive documents assigned to them |

## Divisions

- **ISDA** — Information Systems and Database Administration
- **Administrative Division**
- **ICT** — Technical
- **ETD** — Education & Training Division
MD,
            ],
            [
                'category' => 'Getting Started', 'sort_order' => 2,
                'title' => 'Demo Accounts',
                'excerpt' => 'Login details for testing each role.',
                'content' => <<<'MD'
# Demo Accounts

All demo accounts use the password: **`password`**

| Email | Role |
|-------|------|
| superadmin@pgc.test | Super Admin |
| head@pgc.test | Department Head |
| asst.head@pgc.test | Assistant Department Head |
| receiving@pgc.test | Receiving Staff |
| isda.staff@pgc.test | Staff (ISDA) |
| ict.staff@pgc.test | Staff (ICT) |
| etd.staff@pgc.test | Staff (ETD) |
| admin.staff@pgc.test | Staff (Admin) |

> Change these in `database/seeders/UserSeeder.php`, then run `php artisan migrate:fresh --seed`.
> **Remember to change passwords before going live.**
MD,
            ],
            [
                'category' => 'User Guide', 'sort_order' => 1,
                'title' => 'The Document Workflow',
                'excerpt' => 'Step-by-step: encode → release → receive → forward → archive.',
                'content' => <<<'MD'
# The Document Workflow

## 1. Encode a document (Receiving Staff)
Go to **Document Tracking → Encode Document**. Fill in the title, type, priority and source.
You may assign it to a staff member right away, or leave it for later.

## 2. Assign
On the document page, use **Assign / Re-assign** to choose who should handle it.
You can assign to **anyone**, including yourself.

## 3. Release & Print
Click **Release Document**. This marks it as released and lets you **Print QR Slip**.
Attach the printed QR to the physical document and hand it over.

## 4. Receive (Assigned Staff)
The assigned person scans the QR with their **phone camera**, logs in, and taps **Receive**.
If the wrong person scans it, they only see a *“QR code not found”* message.

## 5. Forward or Archive
Once received, the holder can:
- **Forward** it to another staff member (a reason is **required**), or
- **Archive / Complete** it (details are **required**).

## Tracking
Everyone who has ever held the document — plus the Heads — can open it to see the full
**history timeline** and who is currently holding it.
MD,
            ],
            [
                'category' => 'Developer Guide', 'sort_order' => 1,
                'title' => 'Project Structure',
                'excerpt' => 'Where everything lives in the codebase.',
                'content' => <<<'MD'
# Project Structure

This is a standard **Laravel 12** app. The most important folders:

```
app/
  Http/Controllers/   <- one controller per module (DocumentController, UserController, ...)
  Models/             <- Document, Division, User, DocumentLog, Setting, DocumentationPage
  Policies/           <- DocumentPolicy: who can view/receive/forward/archive
  Services/           <- DocumentService: the workflow (assign, release, receive, ...)
  Http/Middleware/    <- EnsureUserIsActive
database/
  migrations/         <- table definitions
  seeders/            <- demo data (roles, divisions, users, documents, these docs)
resources/
  views/              <- Blade templates (one folder per module)
  views/layouts/app.blade.php  <- the main dashboard layout (sidebar, dark mode)
  views/components/   <- reusable UI (btn, card, badge, nav-item, stat-card)
  css/app.css         <- Tailwind + small helper classes (.input, .label, .table-th)
routes/
  web.php             <- all routes
```

## How a request flows
`routes/web.php` → **Controller** → (uses) **Service / Model** → returns a **Blade view**.

Authorisation happens through `app/Policies/DocumentPolicy.php` and the
permission middleware on the routes.
MD,
            ],
            [
                'category' => 'Developer Guide', 'sort_order' => 2,
                'title' => 'How to Add a New Field to Documents',
                'excerpt' => 'A worked example a beginner can copy.',
                'content' => <<<'MD'
# How to Add a New Field to Documents

Say you want to add a **"due date"** to documents.

### 1. Create a migration
```bash
php artisan make:migration add_due_date_to_documents_table
```
In the new file under `database/migrations/`:
```php
public function up(): void {
    Schema::table('documents', function (Blueprint $table) {
        $table->date('due_date')->nullable()->after('priority');
    });
}
public function down(): void {
    Schema::table('documents', fn ($table) => $table->dropColumn('due_date'));
}
```
Run it:
```bash
php artisan migrate
```

### 2. Make it fillable
In `app/Models/Document.php`, add `'due_date'` to the `$fillable` array
(and to `$casts` as `'due_date' => 'date'`).

### 3. Add it to the form
In `resources/views/documents/create.blade.php` and `edit.blade.php`:
```blade
<div>
    <label class="label">Due date</label>
    <input type="date" name="due_date" value="{{ old('due_date', $document->due_date?->format('Y-m-d')) }}" class="input">
</div>
```

### 4. Validate it
In `app/Http/Controllers/DocumentController.php` (`store` and `update`):
```php
'due_date' => ['nullable', 'date'],
```

### 5. Show it
In `resources/views/documents/show.blade.php`, add a line in the `<dl>` block.

That's it — five small steps and your new field works everywhere.
MD,
            ],
            [
                'category' => 'Developer Guide', 'sort_order' => 3,
                'title' => 'How to Add a New Module / Page',
                'excerpt' => 'Controller + route + view + sidebar link.',
                'content' => <<<'MD'
# How to Add a New Module / Page

Example: an **"Announcements"** page.

### 1. Make a controller
```bash
php artisan make:controller AnnouncementController
```

### 2. Add a route
In `routes/web.php`, inside the `auth` group:
```php
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
```

### 3. Create the view
`resources/views/announcements/index.blade.php`:
```blade
<x-app-layout>
    <x-slot name="header">Announcements</x-slot>
    <x-card>Hello from announcements!</x-card>
</x-app-layout>
```

### 4. Add a sidebar link
In `resources/views/layouts/app.blade.php`, copy an existing `<x-nav-item>` block:
```blade
<x-nav-item :active="request()->routeIs('announcements.*')" :href="route('announcements.index')" label="Announcements">
    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24..."/>
</x-nav-item>
```

### 5. (Optional) Protect it with a permission
Add a permission in `RolePermissionSeeder.php`, then guard the route:
```php
->middleware('permission:announcements.view')
```
MD,
            ],
            [
                'category' => 'Developer Guide', 'sort_order' => 4,
                'title' => 'Roles & Permissions Explained',
                'excerpt' => 'How access control works (spatie/laravel-permission).',
                'content' => <<<'MD'
# Roles & Permissions

This system uses the **spatie/laravel-permission** package.

## Concepts
- A **permission** is a single ability, e.g. `documents.create`.
- A **role** is a named bundle of permissions, e.g. *Receiving Staff*.
- A user has one (or more) roles.

## Checking permissions
In **Blade**:
```blade
@can('documents.create')
    <a href="...">Encode</a>
@endcan
```
In a **controller**:
```php
abort_unless($request->user()->can('documents.create'), 403);
```
On a **route**:
```php
->middleware('permission:documents.create')
```

## Document-specific rules
Beyond simple permissions, `app/Policies/DocumentPolicy.php` decides things like
*"can THIS user receive THIS document"* (only the current holder can). This is what
makes the QR show **"not found"** to the wrong user.

## Super Admin
The Super Admin bypasses all checks via `Gate::before()` in
`app/Providers/AppServiceProvider.php`.

## Editing roles
Use the **Roles & Permissions** module in the app — no code needed. Tick the
permissions you want for each role and save.
MD,
            ],
            [
                'category' => 'Deployment', 'sort_order' => 1,
                'title' => 'Running on IIS (Windows)',
                'excerpt' => 'Host the app on Internet Information Services.',
                'content' => <<<'MD'
# Running on IIS (Windows)

> A `web.config` is already included in the `public/` folder.

### Requirements
- **PHP for Windows** (Non-Thread-Safe build) + the **IIS URL Rewrite** module
- **PHP Manager for IIS** (easiest way to register PHP) or a manual FastCGI handler

### Steps
1. **Enable IIS** with *CGI* (Control Panel → Programs → Turn Windows features on/off → IIS → Application Development Features → CGI).
2. **Install URL Rewrite**: <https://www.iis.net/downloads/microsoft/url-rewrite>
3. In **IIS Manager**, add a **Website**:
   - Physical path → the project's **`public`** folder (e.g. `D:\PGC\public`)
   - Binding → port 80 (or any free port)
4. Register PHP via **PHP Manager** (point it at `php-cgi.exe`), or add a FastCGI handler mapping for `*.php`.
5. Give the app pool user **write access** to `storage/` and `bootstrap/cache/`.
6. Set production values in `.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://your-server-name
   ```
7. Run once on the server:
   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   ```

### Important for QR scanning on phones
`APP_URL` must be an address the phones can reach (the server's **LAN IP** or hostname),
because the QR code encodes `APP_URL/track/...`. Phones on Wi-Fi must be on the same network.

### Building front-end assets
On the server (or before copying the project):
```bash
npm install
npm run build
```
This creates `public/build/` which IIS serves directly.
MD,
            ],
            [
                'category' => 'Deployment', 'sort_order' => 3,
                'title' => 'Transfer to Production Server (IIS + Cloudflare)',
                'excerpt' => 'Step-by-step: move this app to a fresh Windows server and publish it at https://dts.cavite.gov.ph.',
                'content' => <<<'MD'
# Transfer to Production Server (IIS + Cloudflare)

This is the full checklist to move the system from your development PC to a
**fresh Windows Server** that already has **IIS with CGI** installed, and to
publish it at **https://dts.cavite.gov.ph** through a **Cloudflare Tunnel**.

> Throughout, we assume the project will live at `C:\inetpub\dts` on the server.
> Adjust the path if you prefer another location.

---

## Part A — Install prerequisites on the server

Download and install:

1. **PHP 8.3 for Windows — Non-Thread-Safe (NTS), x64.** Extract to e.g. `C:\php`.
   - In `C:\php\php.ini` (copy from `php.ini-production`) enable these extensions
     (remove the leading `;`): `extension=pdo_mysql`, `extension=mbstring`,
     `extension=openssl`, `extension=fileinfo`, `extension=curl`, `extension=gd`,
     `extension=zip`. Set `cgi.fix_pathinfo=1`.
   - Add `C:\php` to the system **PATH**.
2. **IIS URL Rewrite module** — <https://www.iis.net/downloads/microsoft/url-rewrite>
3. **PHP Manager for IIS** (easiest way to register PHP) — optional but recommended.
4. **Composer** — <https://getcomposer.org/Composer-Setup.exe>
5. **Node.js 20+ LTS** (only needed to build the CSS/JS) — <https://nodejs.org>
6. **MySQL 8** (or MariaDB). Create an empty database and a user.
7. **cloudflared** (the Cloudflare Tunnel client) — <https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/>

---

## Part B — Copy the project

1. On your dev PC, **do not copy** these folders (they are rebuilt on the server):
   `vendor/`, `node_modules/`. You *can* copy `public/build` but we will rebuild it.
2. Copy the whole project folder to the server, e.g. to `C:\inetpub\dts`.
   (USB drive, network share, or `git clone` if you push it to a repo.)
3. **Do not** copy your dev `.env`. We create a fresh one below.

---

## Part C — Configure the application

Open a terminal **in `C:\inetpub\dts`** and run:

```bash
copy .env.example .env
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
```

Now edit **`.env`** for production:

```ini
APP_NAME="PGC Document Tracking System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dts.cavite.gov.ph        # <-- the public Cloudflare hostname

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgc_dts
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_SECURE_COOKIE=true               # cookies only sent over https
```

> **Why `APP_URL` matters:** the QR codes encode `APP_URL/track/...`. Setting it to
> the public https hostname makes every printed QR point at
> `https://dts.cavite.gov.ph/track/...`. The app is already configured to trust the
> Cloudflare proxy and force https links, so this just works.

Build the database and caches (first deployment):

```bash
php artisan migrate --force
php artisan db:seed --force        # creates roles, divisions and demo users (first time only)
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> ⚠️ **Change the demo passwords** (see *Demo Accounts*) or delete the demo users
> before real use.

---

## Part D — Folder permissions

The IIS application-pool identity must be able to write to two folders:

1. In IIS the app pool runs as **`IIS APPPOOL\<your-site-name>`** (or set a service account).
2. Grant **Modify** permission to that identity on:
   - `C:\inetpub\dts\storage`
   - `C:\inetpub\dts\bootstrap\cache`

PowerShell example (replace the site name):

```powershell
$id = "IIS APPPOOL\dts"
icacls "C:\inetpub\dts\storage" /grant "$id:(OI)(CI)M" /T
icacls "C:\inetpub\dts\bootstrap\cache" /grant "$id:(OI)(CI)M" /T
```

---

## Part E — Create the IIS website

1. Open **IIS Manager → Sites → Add Website**.
   - **Site name:** `dts`
   - **Physical path:** `C:\inetpub\dts\public`  ← **point at `public`, not the root!**
   - **Binding:** http, port **80** (host name can be left blank; Cloudflare connects locally).
2. Register PHP as the handler for `*.php`:
   - With **PHP Manager**: *Register new PHP version* → pick `C:\php\php-cgi.exe`.
   - Or manually: **Handler Mappings → Add Module Mapping**
     - Request path `*.php`, Module `FastCgiModule`,
       Executable `C:\php\php-cgi.exe`, Name `PHP_via_FastCGI`.
3. The included **`public/web.config`** already provides the Laravel URL-rewrite rules.
4. Browse `http://localhost` on the server — you should see the login page.
   (If you see a 500 error, set `APP_DEBUG=true` temporarily, run
   `php artisan optimize:clear`, and re-check permissions.)

---

## Part F — Publish with a Cloudflare Tunnel

This exposes the local IIS site (port 80) at `https://dts.cavite.gov.ph`
**without opening any firewall ports**. Cloudflare handles the HTTPS certificate.

> Requires the domain **cavite.gov.ph** to be managed in your Cloudflare account.

```bash
# 1. Authenticate (opens a browser to pick the cavite.gov.ph zone)
cloudflared tunnel login

# 2. Create a named tunnel
cloudflared tunnel create dts-tunnel

# 3. Route the public hostname to this tunnel (creates the DNS record for you)
cloudflared tunnel route dns dts-tunnel dts.cavite.gov.ph
```

Create the config file at `C:\Users\<you>\.cloudflared\config.yml`:

```yaml
tunnel: dts-tunnel
credentials-file: C:\Users\<you>\.cloudflared\<TUNNEL-ID>.json

ingress:
  - hostname: dts.cavite.gov.ph
    service: http://localhost:80
  - service: http_status:404
```

Test it, then install it as a Windows service so it runs on boot:

```bash
cloudflared tunnel run dts-tunnel          # test in the foreground first
cloudflared service install                # then install as a service
```

Finally, in the **Cloudflare dashboard → SSL/TLS**, set the mode to **Full**.
Visit **https://dts.cavite.gov.ph** — you should get the login page over HTTPS,
and printed QR codes will open the correct page on any phone with internet.

---

## Part G — Updating later

When you change code on the server:

```bash
git pull            # or copy the changed files
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

That's the whole pipeline. After Part F, the system is live and phone-scannable
from anywhere.
MD,
            ],
            [
                'category' => 'Deployment', 'sort_order' => 2,
                'title' => 'Everyday Commands',
                'excerpt' => 'The commands you will use most often.',
                'content' => <<<'MD'
# Everyday Commands

Run these from the project folder (`D:\PGC`).

### Development
```bash
php artisan serve          # start the app at http://localhost:8000
npm run dev                # rebuild CSS/JS automatically while you edit
```

### Database
```bash
php artisan migrate                 # apply new migrations
php artisan migrate:fresh --seed    # WIPE and rebuild with demo data
php artisan db:seed                 # run seeders only
```

### After changing code/config
```bash
php artisan optimize:clear   # clear all caches if something looks stale
php artisan storage:link     # (run once) makes uploaded logos visible
```

### Building assets for production
```bash
npm run build
```

> Tip: with **Laragon**, you can also just click *Start All* and open
> `http://pgc.test` if you set up a virtual host.
MD,
            ],
        ];

        foreach ($pages as $page) {
            DocumentationPage::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($page['title'])],
                $page
            );
        }
    }
}
