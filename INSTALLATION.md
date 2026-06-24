# IMedia Registration — Installation Guide

Two components are delivered:

| Package | What it is | Where it goes |
|---------|-----------|---------------|
| `imedia-registration-plugin.zip` | WordPress plugin | Upload via WP Admin → Plugins → Add New |
| `imedia-registration-standalone.zip` | Standalone admin app | Extract to `public_html/registration/` via cPanel File Manager |

---

## Step 1: Upload the Plugin

1. Log in to WordPress Admin
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose `imedia-registration-plugin.zip` and click **Install Now**
4. Click **Activate**

---

## Step 2: Deploy the Standalone App

1. Log in to **cPanel**
2. Open **File Manager**
3. Navigate to `public_html/`
4. Upload `imedia-registration-standalone.zip`
5. Extract it — this creates a `registration/` folder
6. Verify the folder contains: `index.php`, `.htaccess`, `app/`, `config/`, `routes.php`

The app will be accessible at:
```
https://www.inventivemedia.com.ph/registration/
```

---

## Step 3: Create the Database

1. In **cPanel**, open **MySQL Databases**
2. Create a new database (e.g. `yourusername_imreg`)
3. Create a new database user with a strong password
4. Add the user to the database with **ALL PRIVILEGES**
5. Note the database name, username, and password

---

## Step 4: Import the Schema

1. In **cPanel**, open **phpMyAdmin**
2. Select your new database from the left sidebar
3. Click **Import** tab
4. Choose `database/schema.sql` and click **Go**

> `schema.sql` is the complete schema for new installations. The `database/` folder also contains migration files — these are retained for upgrading older installations only. Do not run them on a fresh install.

---

## Step 5: Configure the Standalone App

1. In **cPanel File Manager**, navigate to `public_html/registration/config/`
2. Open `config.php` in the editor
3. Replace the placeholder values:

| Setting | Replace with |
|---------|-------------|
| `DB_NAME` | The database name from Step 3 |
| `DB_USER` | The database user from Step 3 |
| `DB_PASS` | The database password from Step 3 |
| `MAIL_FROM_EMAIL` | Your sender email address |

4. Generate an HMAC secret:
   - In cPanel **Terminal**, run: `php -r "echo bin2hex(random_bytes(32));"`
   - Or use an online hex generator (64 hex characters)
5. Paste the secret into `config.php`
6. Save the file

---

## Step 6: Configure the WordPress Plugin

1. In WordPress Admin, go to **IMedia Registration → Settings**
2. Under **Standalone App (HMAC)**:
   - **Registration App URL**: `https://www.inventivemedia.com.ph/registration`
   - **Shared Secret**: Paste the **same** HMAC secret from Step 5
3. Click **Save Settings**

---

## Step 7: Set Up Form Routes

1. Visit the standalone app: `https://www.inventivemedia.com.ph/registration/admin/login`
2. Log in with the default account: `admin@example.com` / `admin123`
3. Go to **Users → Edit** and change the password immediately
4. Go to **Settings → Form routes**
5. Add a row for each form ID that should forward submissions

---

## Step 8: Set Up Email (SMTP)

1. In the standalone app, go to **Settings → SMTP**
2. Enter your email credentials (host, port, username, password)
3. Click **Test Email** to verify
4. Go to **Outbox** and click **Process outbox** to send the test

---

## Step 9: Test

1. Create a form in WordPress: **IMedia Registration → Forms → Add New**
2. Set **API Enabled** to `1` in the form's API section
3. Publish the form and embed it: `[imedia_form id="1"]`
4. Submit the form on the frontend
5. Verify it appears in the standalone app at **Registrations**
6. Check that the admin notification email was sent

---

## Troubleshooting

| Symptom | Likely cause |
|---------|-------------|
| Standalone app shows blank page | Check `config.php` — set `APP_DEBUG` to `true` temporarily to see errors |
| `500 Internal Server Error` | Check `.htaccess` — ensure no `<Directory>` blocks remain |
| Submission fails with HTTP 419 | HMAC secret mismatch — verify plugin settings and `config.php` match exactly |
| Emails not sending | SMTP settings not configured or incorrect — check in Settings → SMTP |
| File uploads fail | Ensure `public_html/registration/uploads/` is writable (permissions 755) |

---

## File Permissions

| Path | Permission |
|------|-----------|
| `public_html/registration/uploads/` | 755 (writable by PHP) |
| `public_html/registration/config/config.php` | 640 |
| Everything else | 644 or 755 |
