# IMedia Registration

**IMedia Registration** is a WordPress form builder plus a standalone PHP admin app. Forms are built and rendered by the WordPress plugin; submissions are forwarded (HMAC-signed) to the admin app at `https://www.inventivemedia.com.ph/registration/`, which stores them in a separate MySQL database and provides a dashboard, threshold alerts, CSV exports, and the rest of the admin UI.

**Version:** 3.0.0 · **Author:** Christian Catuday · **License:** GPL v2 or later · **Plugin URI:** [https://www.inventivemedia.com.ph/](https://www.inventivemedia.com.ph/)

---

## 1. Architecture

Two pieces ship in one folder and talk to each other over an HMAC-signed HTTP boundary:

```
[ WP site visitor ]
      │  fills [imedia_form id="42"]
      ▼
[ WordPress plugin ]  ── signs body with HMAC-SHA256
      │
      │  POST /api/submit  (X-IMF-Signature: sha256=<hex>)
      ▼
[ Standalone PHP app ]  ── verifies signature, replay window ±5 min
      │
      │  PDO / MySQL
      ▼
[ Admin app DB ]  (separate from the WordPress DB)
```

The two sides are **independent processes** with independent databases. The only contract between them is the HMAC submission protocol described in [§8 Submission protocol](#8-submission-protocol-hmac).

---

## 2. Requirements

| Component | Minimum |
|---|---|
| WordPress | 5.0+ |
| PHP | 8.1+ (8.3+ recommended) |
| WordPress DB | MySQL 5.6+ or MariaDB 10.3+ |
| Admin app DB | MySQL 5.7+ or MariaDB 10.3+ (separate database, same cPanel account) |
| Web server | Apache with `mod_rewrite` (cPanel default) |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`, `mail` (or SMTP creds) |

---

## 3. Repository layout

```
imedia-registration/
├── imedia-registration.php        Main plugin file (WordPress reads this)
├── uninstall.php                  Fires on plugin uninstall
├── routes.php                     Standalone-app route table
├── README.md                      This document
├── INSTALLATION.md                Standalone deployment guide
├── DESIGN.md                      Design system (Studio) source of truth
├── AGENTS.md                      LLM coding guidelines
├── composer.json                  Dependency declaration
├── composer.lock                  Locked dependency versions
├── skills-lock.json               Skill lock file
├── phpcs.dist.xml                 PHP_CodeSniffer config
├── phpunit.xml.dist               PHPUnit config
│
├── app/                           Standalone app — modern PHP
│   ├── Controllers/               HTTP entry points (one per resource)
│   ├── Models/                    Data access objects
│   ├── Services/                  Mailer, file storage, stats, outbox worker
│   ├── Core/                      Bootstrap, Config, Router, Request, Response,
│   │                              Session, Auth, Database, Logger, View, Csrf,
│   │                              Hmac, PhpmailerLoader
│   └── Middleware/                AdminAuth, CsrfVerify, HmacVerify
│
├── includes/                      WordPress integration layer
│   ├── class-imf-database.php     CPT schema + helpers
│   ├── class-imf-admin.php        WP admin menus, asset enqueue, form builder UI
│   ├── class-imf-frontend.php     [imedia_form] shortcode + frontend assets
│   ├── class-imf-ajax.php         Admin AJAX endpoints
│   ├── class-imf-rest-api.php     WP REST endpoints (incl. submission receiver)
│   ├── class-imf-email.php        Outbound email helpers
│   └── helpers.php                Pure functions (HMAC, sanitization, defaults)
│
├── resources/                     User-facing assets and templates
│   ├── assets/
│   │   ├── css/                   form-builder.css, entries.css, style.css
│   │   └── js/                    form-builder.js, entries.js, dashboard.js, script.js
│   └── views/
│       ├── wordpress/             PHP templates loaded by the WP admin pages
│       │                          (form list, builder, settings, entries, detail)
│       ├── layouts/               public.php, admin.php — used by the standalone app
│       ├── partials/              table.php, pagination.php, kpi-card.php,
│       │                          chart-canvas.php, restore-modal.php
│       ├── admin/                 Standalone-app admin pages
│       │   ├── registrations/     list.php, view.php, edit.php
│       │   ├── users/             list.php, edit.php
│       │   ├── contacts/          list.php
│       │   ├── applications/      list.php
│       │   ├── custom-endpoints/  list.php, edit.php, submissions.php
│       │   ├── outbox/            index.php
│       │   ├── alumni.php
│       │   ├── alerts.php
│       │   ├── dashboard.php
│       │   ├── profile.php
│       │   └── settings.php
│       ├── login.php              Standalone-app login form
│       └── home.php               Standalone-app home stub
│
├── config/                        config.example.php (template; copy to config.php)
├── database/                      Database artifacts
│   ├── schema.sql                 Full schema (fresh installs)
│   ├── migration-005-resume.sql   Additive: registrations.resume_path
│   ├── migration-006-login-throttle.sql   Additive: admins.locked_until + login_attempts
│   └── seeds/seed.sql             Optional sample data
│
├── cron/                          process-outbox.php — drain the email queue
├── public/                        Standalone app web root
│   ├── .htaccess                  URL rewrite + uploads/ PHP deny
│   ├── index.php                  Front controller
│   └── uploads/                   Resume files land here
├── storage/                       Runtime data
│   └── logs/                      app-YYYY-MM-DD.log (gitignored)
├── tests/                         PHPUnit test suites (Unit/, Integration/, Regression/)
├── vendor/                        Composer dependencies (gitignored; run `composer install`)
├── dist/                          Build artifacts (plugin.zip, standalone.zip)
├── docs/                          BUILD.md, SUBMISSION_CONTRACT.md
├── tools/                         build-dist.sh, sign_submit.php
├── scripts/                       build-standalone.sh, test-forward.php
└── graphify-out/                  Knowledge graph (cache + reports)
```

---

## 4. Install — step by step

### 4.1 Upload the plugin folder

Upload the `imedia-registration/` folder to `wp-content/plugins/` of the WordPress install at `https://www.inventivemedia.com.ph/`.

If you are upgrading from a previous install where the folder was named `imedia-forms`, **delete the old folder first** and then upload the new one. WordPress will deactivate the plugin on folder rename — you will need to re-activate it.

### 4.2 Activate the plugin

WordPress admin → **Plugins** → **IMedia Registration** → **Activate**.

### 4.3 Create the admin app database

In cPanel:
1. **cPanel → MySQL Databases** → Create database (e.g. `inventivemedia_imreg`).
2. Create a database user, give it `ALL PRIVILEGES` on that database.
3. Note the database name, username, password, and host (usually `localhost`).

### 4.4 Import the schema

In **cPanel → phpMyAdmin**:
1. Select the new database from the left sidebar.
2. **Import** tab → choose `database/schema.sql` from the plugin folder.
3. Click **Go**. You should see "Import has been successfully finished".
4. (Optional) Import `database/seeds/seed.sql` for sample data.

### 4.5 Configure the admin app

1. In the plugin folder, copy `config/config.example.php` to `config/config.php`.
2. Edit `config.php`:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` — from step 4.3.
    - `BASE_URL` — leave as `https://www.inventivemedia.com.ph/registration`.
   - Generate the HMAC shared secret. In cPanel **Terminal** (or via SSH) run:
     ```
     php -r "echo bin2hex(random_bytes(32));"
     ```
     The secret is not stored in `config.php` — it is configured in the admin UI's **Settings → HMAC shared secret** (step 4.7). Paste the same value into the WP plugin's Shared Secret (step 4.6).

### 4.6 Configure the WP plugin

WordPress admin → **IMedia Registration → Settings → Standalone App (HMAC)**:
- **Registration App URL**: `https://www.inventivemedia.com.ph/registration`
  (the plugin appends `/api/submit` itself; if blank, it falls back to `https://<your-site>/registration`).
- **Shared Secret**: paste the same secret from step 4.5. The field is write-only — leave it blank to keep the stored value.
- Click **Save Settings**.

The plugin will then sign every forwarded body with HMAC-SHA256 of the exact body bytes (over a fresh `_imf_timestamp` field) and add the `X-IMF-Signature: sha256=<hex>` header. The standalone app verifies the signature, then rejects any request older than 5 minutes (replay protection).

> **Known limitation:** WP options (including `imf_shared_secret`) are stored in the `wp_options` table as plain text. They are not encrypted at rest. The threat model assumes anyone with DB read access can read the secret — but the secret only matters for an attacker who is on a different host and can forge requests without DB access. The signature on every request is what actually protects the data.

### 4.7 Configure HMAC in the admin app

Log in to the admin app (`/admin/login`) with the default account (step 4.8). Go to **Settings → HMAC shared secret** and paste the same secret generated in step 4.5. Save. The standalone app will now verify signatures using this secret.

### 4.8 Map form IDs to targets

The WP plugin only knows the global **Registration App URL**. The standalone side's **Settings → Form routes** table decides where each `form_id`'s submissions go (registration, contact, OJT, trainer, or a custom endpoint). For each IMedia Form CPT in the new app, add a row: `form_id → target_type` (and `target_slug` if the type is `custom`).

If a per-form `_imf_api_endpoint` is set on the IMedia Form CPT (legacy per-form override), the WP plugin forwards to that URL **but still signs with the global secret** — so the destination must be a standalone that knows the same secret. If you want a form to bypass HMAC forwarding entirely, set `_imf_api_enabled` to `0` on that form.

### 4.9 Change the default admin password

Log in to the admin app at `https://www.inventivemedia.com.ph/registration/admin/login` with:
- Email: `admin@example.com`
- Password: `admin123`

Go to **Users → Edit** and change the email and password.

---

## 5. How a submission flows

```
1. Visitor fills [imedia_form id="42"] on the WP site
2. JS POSTs to WP REST: /wp-json/imedia-forms/v1/submit
3. WP saves a local copy in wp_imf_entries (safety net)
4. WP builds a JSON body and signs it with HMAC(SECRET, body)
5. WP POSTs (fire-and-forget) to
   https://www.inventivemedia.com.ph/registration/api/submit
   with header X-IMF-Signature: sha256=<hmac>
6. The admin app's HmacVerify middleware accepts the request
7. SubmitController looks up form_routes for form_id=42
8. INSERT into the target table (registrations, contacts, etc.)
9. Mailer sends the student a confirmation email
10. Mailer sends the admin a "new registration" notification
11. ThresholdChecker: counts 'confirm' rows for the (course, y, m) slot
12. If the count >= alert_threshold (default 9), ThresholdChecker
    inserts into threshold_alerts_sent (UNIQUE blocks duplicates)
    and Mailer sends the threshold alert
13. Admin sees the new row in /admin/registrations,
    a green KPI counter on the dashboard, and a red banner
    if the slot is at/over threshold
```

---

## 6. Admin app URL map

| URL | Purpose |
|---|---|
| `/` | Stub |
| `/api/submit` | HMAC-verified intake from the WP plugin |
| `/admin/login` | Admin login |
| `/admin` | Dashboard (KPIs + charts) |
| `/admin/alerts` | Course slots at/over threshold |
| `/admin/registrations` | List / create |
| `/admin/registrations/{id}` | View / edit / history / delete / restore |
| `/admin/outbox` | Email queue (queued / failed / sent) |
| `/admin/alumni` | Soft-deleted rows, with restore |
| `/admin/contacts` | Contact inquiries |
| `/admin/applications/ojt` | OJT applications |
| `/admin/applications/trainer` | Trainer applications |
| `/admin/custom-endpoints` | Dynamic endpoints |
| `/admin/users` | Admin user management |
| `/admin/settings` | SMTP, threshold, form routes, test-email |
| `/admin/export/{type}.csv` | CSV downloads |

---

## 7. Data model

11 tables, all InnoDB + `utf8mb4_unicode_ci`. Foreign keys use
`ON DELETE CASCADE` for `custom_submissions → custom_endpoints` so
endpoint deletion cleans up its rows automatically. `DATETIME` (not
`TIMESTAMP`) so MySQL doesn't auto-update the column on UPDATE.

| Table | Purpose |
|---|---|
| `admins` | Admin users (bcrypt hashes, `locked_until`) |
| `contacts` | Contact-form submissions |
| `applications` | OJT + Trainer applications |
| `registrations` | Course registrations |
| `custom_endpoints` | User-defined form endpoints |
| `custom_submissions` | Submissions into custom endpoints |
| `form_routes` | Maps form_id → handler/target |
| `threshold_alerts_sent` | Dedup index for course-threshold emails |
| `outbox_emails` | Email queue |
| `settings` | Singleton settings row (id=1) |
| `login_attempts` | Throttle log (Phase 8) |

For new installations, import only `database/schema.sql` — it contains the complete schema. The migration files (`database/migration-*.sql`) are retained for upgrading older installations; do not apply them on a fresh install.

### 7.1 Status model

| Field | Values |
|---|---|
| `status` (enrollment) | `pending`, `tentative`, `confirm`, `forfeit`, `reschedule` |
| `payment_status` | `pending`, `deposit`, `fully_paid` |

If `payment_status` is `deposit` or `fully_paid`, then `paid_amount`, `paid_at`, and `remark` are required. The form will refuse to save without them.

Every change to `status` or `payment_status` is logged in `status_history` (who, when, from, to, optional note).

---

## 8. Submission protocol (HMAC)

| Field | Where | Notes |
|---|---|---|
| `X-IMF-Signature` | HTTP header | `sha256=<hex>` — HMAC-SHA256 of the raw body bytes |
| `_imf_timestamp` | Body field | Unix seconds; must be within ±300s of server time |
| Body | POST | JSON; the entire body is the signed material (header is not signed) |

**Replay window:** ±300s. A request with an out-of-window timestamp is rejected with HTTP 419.
**Body integrity:** the hash is over the **exact bytes** the server reads. Any whitespace or encoding change by an intermediary breaks the signature.
**Header format:** `X-IMF-Signature: sha256=abcdef0123…` (lowercase hex).

The HMAC secret never leaves the two servers. It is never sent in the request.

---

## 9. Threshold alerts

When the count of `status = 'confirm'` rows for a `(course, year, month)` slot reaches `alert_threshold` (default `9`, configurable in Settings), the admin app sends **one** email per slot. The `threshold_alerts_sent` table has a `UNIQUE KEY (course, course_year, course_month)` so the 10th, 11th, ... confirms do not re-send.

The dashboard shows a red banner above any slot currently at or above the threshold.

---

## 10. Outbox (email queue)

The admin app queues every outgoing email into the `outbox_emails` table (threshold alerts, the "new registration" admin notification, and the "Test email" button). A worker sends them via SMTP using the credentials configured in **Settings → SMTP**.

### 10.1 Manual — "Process outbox now"

Go to **Outbox** in the admin sidebar. The Queued tab shows pending emails with a **Process outbox** button. One click runs the worker (up to 25 emails, 20-second wall-clock cap, 3 retry attempts per row).

### 10.2 Automatic — cPanel cron job

For fully automatic processing, add a cPanel cron job (every 5 minutes):

```
*/5 * * * * /usr/local/bin/php /home/<cpanel-account>/public_html/registration/cron/process-outbox.php >> /home/<cpanel-account>/logs/imreg-outbox.log 2>&1
```

The script writes a one-line summary to stdout and logs every run + failure to `storage/logs/app-YYYY-MM-DD.log`.

### 10.3 Failure handling

- A failed send increments `attempts` and stores the error in `last_error`. The row stays `queued`.
- After 3 failed attempts, the row moves to `failed` and stops retrying. The admin can re-queue from the Failed tab.
- Deadlocks (MySQL errno 1213) are retried once with a 50 ms backoff.

### 10.4 Required settings

- **Settings → SMTP**: `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_from_email`, `smtp_from_name`, `smtp_secure` (1 = TLS).
- **Settings → Threshold alert**: `threshold_alert_to` (recipient), and the subject/body templates.
- **Settings → Admin notification**: `admin_notification_to`, subject/body, and the enabled flag.

If `smtp_host` is empty, "Test email" still enqueues a row, but the worker will mark it `failed` with `last_error = 'smtp_not_configured'`.

---

## 11. File uploads (resumes)

The **Edit** and **New Registration** admin pages accept a resume file (PDF, DOC, or DOCX, max 5 MB). Files are stored at `public/uploads/registrations/{YYYY}/{MM}/<random-hex>.<ext>`. The relative path is stored in `registrations.resume_path` and the file is served back via the authenticated route `/admin/registrations/{id}/resume`.

Direct execution of PHP in `public/uploads/` is blocked by the `.htaccess` deny rule, so a malicious file in the uploads dir cannot run.

MIME types are detected with `finfo` (the actual file content, not the browser-supplied `Content-Type`), and the file extension is taken from the detected MIME — never from the user-supplied filename.

Settings (in the `settings` row, editable via direct SQL or a future admin form):
- `upload_resume_enabled` (default 1)
- `upload_resume_max_bytes` (default 5242880 = 5 MB)
- `upload_resume_allowed_mime` (default PDF + DOC + DOCX)
- `upload_resume_storage_path` (default `public/uploads/registrations/{YYYY}/{MM}/`)

---

## 12. Design system

The admin app uses a hand-written design system in `public/assets/css/app.css`. No build step, no Tailwind, no bundler. The CSS file is what ships.

**Color source:** `DESIGN.md` "Studio" — deep teal-jewel `#1F574D` (moss) for primary CTAs, warm rust `#B8541B` (ember) for accent, deep slate `#0F1419` (ink) for foreground, cool near-white `#F4F6F8` (paper) for page background, `#FFFFFF` (vellum) for card surfaces.

**Dark mode:** OS preference by default, with a manual override (light / dark / auto) via the topbar toggle. The choice is stored in `localStorage.imreg-theme` and applied to `<html class="dark">` before first paint to avoid FOUC. Chart.js reads the brand colors from CSS custom properties on every render, so dark mode flips correctly without a page reload.

**Component classes** (the vocabulary used across the views):
- `.imreg-card`, `.imreg-card--hover`, `.imreg-kpi`, `.imreg-kpi--alert`
- `.imreg-btn`, `.imreg-btn--primary`, `.imreg-btn--secondary`, `.imreg-btn--ghost`, `.imreg-btn--danger`, `.imreg-btn--sm`, `.imreg-btn--lg`, `.imreg-btn--block`
- `.imreg-input`, `.imreg-input--error`, `.imreg-input--code`, `.imreg-select`, `.imreg-textarea`, `.imreg-checkbox`, `.imreg-label`, `.imreg-error`, `.imreg-help`
- `.imreg-table`, `.imreg-table__empty`, `.imreg-table__caption`
- `.imreg-flash`, `.imreg-flash--success`, `.imreg-flash--error`, `.imreg-flash--warning`
- `.imreg-banner`, `.imreg-banner--alert`
- `.imreg-badge`, `.imreg-badge--pending`, `.imreg-badge--tentative`, `.imreg-badge--confirm`, `.imreg-badge--forfeit`, `.imreg-badge--reschedule`
- `.imreg-pagination`, `.imreg-pagination__link`, `.imreg-pagination__link--active`, `.imreg-pagination__ellipsis`
- `.imreg-tabs`, `.imreg-tab`, `.imreg-tab__badge`
- `.imreg-modal`, `.imreg-modal__title`, `.imreg-modal__body`, `.imreg-modal__footer`
- `.imreg-section`, `.imreg-section__header`, `.imreg-section__num`, `.imreg-section__title`, `.imreg-section__hint`, `.imreg-section__body`
- `.imreg-nav`, `.imreg-nav-link`, `.imreg-nav-link__badge`, `.imreg-nav-group`, `.imreg-nav-user`, `.imreg-theme-toggle`
- `.imreg-grid`, `.imreg-grid--form`, `.imreg-actions`, `.imreg-toolbar`, `.imreg-divider`, `.imreg-sr-only`, `.imreg-skip-link`

Adding a new component class is one `app.css` edit. No rebuild, no JS, no manifest. The same class works in light and dark mode without a per-theme override because the color tokens are defined in both `:root` and `.dark`.

**Mobile behavior:** below 768px the sidebar collapses to a slide-out drawer triggered by the hamburger button in the topbar. Escape key and backdrop click close the drawer. The hamburger is a focus-visible button so keyboard users can navigate to it.

**Reduced motion:** all animation and transition duration is overridden to 0.01ms when the user has `prefers-reduced-motion: reduce`. The "Threshold reached" pulse on the dashboard stops animating. The flash auto-dismiss becomes instant.

**Fonts** — `Fraunces` (variable serif, 600 weight) for headings and KPIs, `Inter` for body text and controls, `JetBrains Mono` for data tables and IDs. All loaded from Google Fonts via `<link rel="stylesheet">` at runtime, with a `preconnect` hint for the gstatic host. No self-hosted `.woff2` files; no extra cPanel disk usage.

---

## 13. Performance

- **Persistent PDO connections** (single TCP socket per PHP worker).
- **Unbuffered result sets** for the two largest CSV exports so a 50k-row
  dump streams straight from MySQL to the wire (see `Database::unbuffered()`).
- **Single-round-trip stats** on the dashboard (`Registration::stats()` does
  one `SUM(CASE)` for total + new-today + new-week + new-month).
- **Outbox list view** SELECTs only the columns the table renders; the
  full `body_html` is fetched on demand.
- **Outbox tab counts** come from one `GROUP BY` query, not three.
- **Resume downloads** stream with `readfile` + `X-Accel-Buffering: no`.
- **Lazy session start** — the public submit path is session-free, so
  visitors don't get a `Set-Cookie` they don't need.
- **Chart.js** is the only JS dependency and is loaded with `defer` from
  a local file. No build step, no Node, no Tailwind.

---

## 14. Security

- **HMAC-signed submissions** (`sha256`) with a 5-minute replay window
  (request must include `_imf_timestamp`; ±300s absolute bound).
- **Single source of truth for sessions** — `App\Core\Session` is the
  only file that touches `$_SESSION`. CI grep guarantees it stays that way.
- **CSRF token** per session, validated on every POST with `hash_equals`.
- **Bcrypt** password hashes, regenerated on login to prevent fixation.
- **Login throttling** (Phase 8): 5 failures / 15 min per IP, 10 failures /
  1 h per account; the account then flips a DB-side `admins.locked_until`
  that survives across logins even when the IP rotates. Returns HTTP 429.
- **Security headers** on every response: `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
- **HMAC secret and SMTP password are write-only** in the settings UI.
- **`/uploads/`** denies PHP execution at the `.htaccess` level.
- **Known limitation:** `wp_options` is not encrypted at rest. If an
  attacker has DB read access, the HMAC secret is recoverable. Treat
  DB access as game-over regardless of secret storage.

---

## 15. Usage — WordPress plugin

This section walks through the day-to-day operation of the WP-side plugin.

### 15.1 Build a new form

1. WordPress admin → **IMedia Registration** → **Forms** (top-level menu) → **Add New**.
   The form builder opens as a custom post-type editor with the form-builder panel in the main area.
2. Type the **form title** (shown to visitors as the form heading). The default WordPress title field is hidden in the builder — use the field at the top of the panel.
3. Drag fields from the **left palette** into the **form canvas** on the right. You can reorder by drag-and-drop.
4. Click any field in the canvas to expand its **field options** (label, name, placeholder, validation, default value, custom CSS class, required toggle).
5. Toggle each field's **active/inactive** status from the field options. Inactive fields are kept in the form definition but not shown to visitors.
6. Click **Save Draft** then **Publish** (the standard WP Publish button is hidden — the builder uses a custom save flow).

### 15.2 Field types

| Type | Use for |
|---|---|
| `text`, `phone`, `number` | Single-line inputs (`phone` renders as `type="tel"`) |
| `email` | Email with optional double-entry confirmation field |
| `textarea` | Multi-line input |
| `date` | Three modes: split day/month/year boxes, dropdowns, or a JS datepicker |
| `time` | Native `type="time"` |
| `name` | First + last name side-by-side |
| `address` | Full address grid (street, line 2, city, state, zip, country) |
| `select`, `multiselect` | Dropdown(s) with optional optgroups and `@category` filtering |
| `radio`, `checkbox`, `multiple_choice` | Choice lists, also with optgroups |
| `file` | File upload (size/MIME enforced server-side) |
| `section` | Non-input divider with optional sub-text |
| `hidden` | A value the form carries but the visitor never sees |

### 15.3 Configure form appearance

Open the **Appearance** section in the builder:

- **Title color** — hex value for the form heading
- **Submit button** — background color, text color, width (`auto` or `full`), alignment (`left` / `center` / `right`)
- **Anti-spam** — toggle reCAPTCHA (requires site + secret keys in plugin Settings) and/or honeypot (a hidden email field; bots fill it, humans don't)

### 15.4 Configure email notifications

In the builder's **Email** section:

- **Admin notification** — recipient (defaults to WP admin email), reply-to, subject, body. Body uses `[all_fields]` and `[form_title]` placeholders.
- **User confirmation** — checkbox + subject + body. Sent to the value of the field named `email` (or whichever field has `type="email"`).

Both templates are stored as post meta and not validated for HTML — escape is the admin's responsibility.

### 15.5 Configure submission forwarding

For each form, in the builder's **API** section:

- **API Enabled** — `1` to forward to the standalone app, `0` to keep submissions in WP only.
- **API Endpoint** — per-form override. If empty, the global **Registration App URL** from Settings is used. Both must speak the same HMAC secret.

If the global URL is empty, the plugin falls back to `https://<your-site>/registration`.

### 15.6 Embed a form on a page

Use the shortcode on any page, post, or widget:

```
[imedia_form id="42"]
```

Optional `category` attribute limits a select field's options to one category (use `@category` in the select options to define categories):

```
[imedia_form id="42" category="summer-2026"]
```

In a theme file:

```php
<?php echo do_shortcode('[imedia_form id="42"]'); ?>
```

### 15.7 Manage entries in WP admin

- **IMedia Registration → Entries** — all submissions across all forms, filterable, searchable.
- Click an entry to open the detail view. The detail view shows the original payload plus normalized columns.
- **Star** an entry (flag for follow-up). **Mark read/unread** updates the `is_read` flag in `wp_imf_entries`.
- **Trash** soft-deletes the row; **Restore** brings it back. These are reversible from the same screen.

The WP-side `wp_imf_entries` table is a **safety net** — the canonical record is in the standalone app's MySQL. Entries are kept here even if the HMAC forward fails so you don't lose data.

### 15.8 Plugin Settings

WordPress admin → **IMedia Registration → Settings**:

- **Default submit button text** — used when a form has no override.
- **reCAPTCHA site key / secret key** — required if any form enables reCAPTCHA.
- **Registration App URL** — the standalone app's HTTPS base.
- **Shared Secret** — the HMAC shared secret. **Write-only**; the field is `type="password"` and the stored value is never echoed back. Leave it blank to keep the current stored value.

### 15.9 Per-form API overrides

If two forms need to land in two different standalone apps (or two different endpoints within the same app), use the per-form **API Endpoint** field. The plugin still signs with the global secret, so the destination must know it.

To make a form store submissions only in WP (no forward), set **API Enabled** to `0`.

### 15.10 Custom endpoints (advanced)

The standalone app can have user-defined endpoints. From **Settings → Custom endpoints** in the standalone app, create a slug + display name. The corresponding `form_routes` row maps a WP `form_id` to that custom endpoint, and submissions land in `custom_submissions` rather than `registrations`/`contacts`/etc.

---

## 16. Usage — Standalone admin app

This section walks through the day-to-day operation of the standalone app at `https://www.inventivemedia.com.ph/registration/`.

### 16.1 First login

1. Navigate to `/admin/login`.
2. Sign in with the default seed account (`admin@example.com` / `admin123`).
3. **Immediately** go to **Users → Edit** (or **Profile**) and change the password. The default account is publicly documented and not safe in production.

### 16.2 Dashboard

`/admin` is the landing page. It shows four KPI cards and three charts:

| KPI | Source |
|---|---|
| Total inquiries | one `SELECT COUNT(*)` |
| New today | since 00:00 server time |
| New this week | last 7 days |
| At / over threshold | rows in `threshold_alerts_sent` joined to live `registrations` |

If any course slot is at/over the alert threshold, a **red banner** appears above the charts. Click it to jump to **/admin/alerts**.

The topbar has a **theme toggle** (light / dark / auto) — the choice is persisted in `localStorage.imreg-theme` and applied before first paint.

### 16.3 Manage registrations

`/admin/registrations` lists every registration with status badges, search, and pagination.

**Create a new registration:**
1. Click **+ New Registration** (top-right).
2. Fill the form: course, year, month, name, email, phone, status, payment status, optional resume.
3. If you set payment status to `deposit` or `fully_paid`, also enter `paid_amount`, `paid_at`, and a `remark` — the form refuses to save without them.
4. Save.

**View:**
- Click any row to open `/admin/registrations/{id}`.
- The view shows the full record, the **status history** (who changed what, when), and a **resume** download link if a file is attached.

**Edit:**
- Open the view, then click **Edit**.
- Changing `status` or `payment_status` appends a row to `status_history` with your user id, the old and new values, an optional note, and a UTC timestamp.

**Status workflow:**

```
pending ──► tentative ──► confirm ──► (reschedule | forfeit)
                                │
                                └─► deposit ──► fully_paid
```

- `pending` — initial state from a new submission.
- `tentative` — soft hold (e.g. student is reviewing the syllabus).
- `confirm` — committed. The threshold checker counts these.
- `reschedule` — moving the student to a different slot.
- `forfeit` — student dropped. Optionally recoverable via Alumni.

**Soft-delete (→ Alumni):**
- From the view page, click **Delete**. The row moves to `/admin/alumni` and is no longer counted in dashboard totals.
- Soft-delete is reversible. See [§16.4 Alumni](#164-alumni).

**Resume download:**
- The authenticated route `/admin/registrations/{id}/resume` streams the file. Filename is set from the original detected MIME.

### 16.4 Alumni

`/admin/alumni` lists every soft-deleted registration. One click on **Restore** brings the row back to `/admin/registrations` with its full history.

### 16.5 Contacts

`/admin/contacts` lists contact-form submissions. Read-only by design — there's no editing flow. **Delete** is the only action and is irreversible.

### 16.6 OJT and Trainer applications

- `/admin/applications/ojt` — OJT applications
- `/admin/applications/trainer` — Trainer applications

Both share the same schema (`applications` table with a `type` column). View detail, or delete (irreversible).

### 16.7 Custom endpoints

`/admin/custom-endpoints` lets you create dynamic form destinations:

1. Click **+ New Endpoint**. Enter a **slug** (lowercase, dash-separated) and a **display name**.
2. After saving, the endpoint is live. Submissions routed here land in `custom_submissions` with a foreign key to the endpoint.
3. From the endpoint's detail page, browse its submissions.

**Map a WP form to a custom endpoint:**
1. In the standalone app: **Settings → Form routes** → add a row: `form_id`, `target_type=custom`, `target_slug=<your-slug>`.
2. The next submission from that WP form lands in `custom_submissions` for the matching endpoint.

### 16.8 Form routes

`/admin/settings` → **Form routes** section. Each row says: "for `form_id=N`, send submissions to `target_type` (and `target_slug` if `custom`)".

If a `form_id` has no row, submissions are rejected with a 4xx error and the WP side will keep retrying. Add a row for every WP form that has API forwarding enabled.

### 16.9 Users

`/admin/users` manages admin accounts.

**Create a new admin:**
1. **+ New User** → enter name, email, password.
2. The user is created with role=admin and a bcrypt-hashed password.

**Edit a user:**
- Click any row to open the edit page. Update name, email, or role.
- **Reset password**: enter a new plaintext password; it's re-hashed on save.

**Account lockout:**
- After 10 failed logins within 1 hour, the account's `admins.locked_until` flips to a future timestamp. The lockout survives across logins (it doesn't clear when the IP changes) and returns HTTP 429.
- An admin must manually clear the lockout (edit the user, or via direct SQL: `UPDATE admins SET locked_until = NULL WHERE id = N`).

### 16.10 Profile

`/admin/profile` updates the **currently logged-in** user's name, email, and password. The change to email takes effect on the next login.

### 16.11 Settings

`/admin/settings` has four sections:

**SMTP** — `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_from_email`, `smtp_from_name`, `smtp_secure` (1 = TLS). All fields except `smtp_pass` are readable in the form; `smtp_pass` is write-only (left blank to keep the current value).

**Threshold alert** — `threshold_alert_to` (recipient), subject template, body template. Sent when a (course, year, month) slot hits the threshold for the first time.

**Admin notification** — `admin_notification_to`, subject template, body template, enabled flag. Sent on every new registration submission.

**User confirmation** — subject template, body template, enabled flag. Sent to the submitter's email field.

**Form routes** — see [§16.8](#168-form-routes).

**Test email** — a button at the bottom of the SMTP section. Clicking it enqueues a single test email into the outbox and returns immediately. Process the outbox (manually or via cron) to see whether SMTP works.

### 16.12 Outbox

`/admin/outbox` has three tabs: **Queued**, **Failed**, **Sent**.

- **Queued** — emails waiting to send. Click **Process outbox** to run the worker for one batch.
- **Failed** — emails that exceeded 3 retries. Click **Retry** to move a single row back to Queued.
- **Sent** — the audit trail. Read-only.

The worker caps each run at 25 emails and 20 seconds wall-clock. For continuous processing, set up the cron job (see [§10.2](#102-automatic--cpanel-cron-job)).

### 16.13 Threshold alerts page

`/admin/alerts` lists every (course, year, month) slot that has been at/over the threshold. Each row links to the filtered registrations list. Use this to triage which slots need capacity decisions.

### 16.14 CSV exports

Authenticated route — requires admin login:

| URL | Output |
|---|---|
| `/admin/export/registrations.csv` | Every active registration |
| `/admin/export/contacts.csv` | Every contact submission |
| `/admin/export/applications-ojt.csv` | OJT applications |
| `/admin/export/applications-trainer.csv` | Trainer applications |

The two largest exports use unbuffered queries so a 50k-row CSV streams straight from MySQL to the wire.

### 16.15 Mobile / responsive

Below 768px the sidebar collapses into a slide-out drawer. The hamburger button in the topbar opens it; the Escape key and a backdrop click close it. The drawer is keyboard-navigable (the hamburger is `focus-visible`).

The reduced-motion preference disables all transitions (banner pulse, flash auto-dismiss).

---

## 17. License

GPL v2 or later.

**Author:** Christian Catuday
**Plugin URI:** [https://www.inventivemedia.com.ph/](https://www.inventivemedia.com.ph/)
**Version:** 3.0.0
