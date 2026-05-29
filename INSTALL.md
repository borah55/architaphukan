# Installation Guide - Doge Faucet

This guide walks you through installing the Doge Faucet on a standard cPanel
shared hosting account. No VPS, Composer, or Node.js is required.

## 1. Requirements

- PHP 8.0 or newer
- MySQL 5.7+ or MariaDB 10.2+
- PHP extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `json`
- A FaucetPay merchant account: https://faucetpay.io/
- (Optional) A Google reCAPTCHA v2 site & secret key

## 2. Upload the files

1. In cPanel open **File Manager**.
2. Go to `public_html` (or a subdirectory).
3. Upload everything from this project. Make sure hidden files
   (`.htaccess`) are uploaded too.

## 3. Create the database

1. cPanel -> **MySQL Databases**.
2. Create a database, e.g. `myacct_faucet`.
3. Create a MySQL user with a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.

## 4. Import the schema

1. cPanel -> **phpMyAdmin** -> select your database.
2. Click **Import** and choose `sql/schema.sql` (fresh install).
3. Click **Go**.

> **Already running v1?** Run `sql/migrate_v1_to_v2.sql` instead. It
> drops the old separate `email` column, renames `password_hash` to
> `pin_hash`, and resets every PIN to `123456` so users can log in and
> change it.

## 5. Configure the site

1. Copy `config/config.sample.php` to `config/config.php`.
2. Edit it:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'myacct_faucet');
   define('DB_USER', 'myacct_faucetuser');
   define('DB_PASS', 'your-strong-password');

   define('SITE_URL',   'https://faucet.example.com');
   define('APP_SECRET', 'long-random-string-replace-me');
   define('COOKIE_SECURE', true);   // if served over HTTPS
   define('DEBUG', false);          // ALWAYS off in production
   ```

## 6. First login

Visit your site and click **Sign in**.

- **FaucetPay email**: `admin@example.com`
- **PIN**: `123456`

You will be redirected to the admin panel. Open **Users → admin →
Edit** and change the FaucetPay email and reset the PIN immediately.

> Authentication uses **only the FaucetPay email + a 6-digit PIN**.
> There is no separate site email/password. Payouts and PIN reset
> emails go to the same FaucetPay email you sign up with.

## 7. Connect FaucetPay

1. In FaucetPay: **Account → Webmaster Tools → API**.
2. Copy your **API Key**.
3. Admin panel → **Settings → FaucetPay API**, paste the key, choose
   **DOGE** (or another supported coin), save.
4. Admin panel → **FaucetPay API**: click **Check balance** to verify.

## 8. Configure the faucet

In **Admin → Settings → Faucet** set:

- Reward / claim (e.g. `0.0005`)
- Cooldown seconds (e.g. `300`)
- Daily claim limit (e.g. `200`)
- Referral % (e.g. `20`)

In **Settings → Security** turn on:

- One account per IP
- One claim per IP
- VPN / proxy detection (optional, requires outbound network)
- reCAPTCHA (paste site & secret keys)
- PIN length (4-8, default 6)

## 9. Optional - reCAPTCHA v2

1. Get keys at https://www.google.com/recaptcha/admin (v2, "I'm not a robot").
2. Admin → **Settings → Security**, enable and paste both keys.

## 10. Optional - Cron job

cPanel → **Cron Jobs** → Add daily:

```
0 3 * * * /usr/local/bin/php /home/USERNAME/public_html/cron/clean.php > /dev/null 2>&1
```

Trims old logs/login attempts/sponsor clicks and rolls contest
statuses (upcoming → active → ended).

You can also call it via web with the secret key:

```
https://faucet.example.com/cron/clean.php?key=YOUR_APP_SECRET
```

## 11. Customize content

- **Admin → Pages** edits Homepage / Terms / Privacy text.
- **Admin → FAQ** to add/edit/delete FAQ items.
- **Admin → Announcement** to show a banner at the top of every page.
- **Admin → Advertisements** to add ads in 6 placements (header,
  sidebar, dashboard, popup, footer, between-content).
- **Admin → Sponsor links** for click-reward sponsor entries.
- **Admin → Referral contests** for time-bound referral leaderboards.

## 12. Going-live checklist

- [ ] `config/config.php` filled in with correct DB credentials & SITE_URL
- [ ] `APP_SECRET` is a long random string
- [ ] `DEBUG = false`
- [ ] Default admin PIN changed
- [ ] FaucetPay API key configured and balance funded
- [ ] reCAPTCHA enabled with valid keys
- [ ] Privacy & Terms text customised
- [ ] HTTPS enforced (cPanel → SSL/TLS Status → AutoSSL)
- [ ] `COOKIE_SECURE = true` once HTTPS is verified
- [ ] Cron job scheduled

That's it - enjoy your Dogecoin faucet!
