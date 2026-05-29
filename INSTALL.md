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
2. Go to `public_html` (or a subdirectory if you want to install in
   `example.com/faucet`).
3. Upload everything from this project. Make sure hidden files
   (`.htaccess`) are uploaded too (in File Manager: Settings -> "Show
   hidden files").

## 3. Create the database

1. cPanel -> **MySQL Databases**.
2. Create a new database, e.g. `myacct_faucet`.
3. Create a new MySQL user with a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.

## 4. Import the schema

1. cPanel -> **phpMyAdmin** -> select your database.
2. Click **Import** and choose the file `sql/schema.sql`.
3. Click **Go**. You should see all tables and the default settings
   created.

## 5. Configure the site

1. In File Manager copy `config/config.sample.php` to `config/config.php`.
2. Edit `config/config.php` and set:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'myacct_faucet');
   define('DB_USER', 'myacct_faucetuser');
   define('DB_PASS', 'your-strong-password');

   define('SITE_URL',   'https://faucet.example.com');
   define('APP_SECRET', 'long-random-string-replace-me');
   define('COOKIE_SECURE', true);   // if your site is served over HTTPS
   define('DEBUG', false);          // ALWAYS off in production
   ```

3. Save the file. The `.htaccess` files inside `/config` and `/includes`
   already block direct access to PHP files in those folders.

## 6. First login

1. Visit your site, e.g. `https://faucet.example.com/`.
2. Click **Login** and use the default admin credentials:

   - **Username:** `admin`
   - **Password:** `admin123`

3. Go to **Admin Panel -> Users -> admin -> Edit** and change the
   password immediately. Also update the email and FaucetPay email.

## 7. Connect FaucetPay

1. In your FaucetPay account, go to
   **Account -> Webmaster Tools -> API**.
2. Copy your **API Key**.
3. In the admin panel, open **Settings -> FaucetPay API**.
4. Paste the API key, choose **DOGE** (or any other supported coin),
   click **Save settings**.
5. Open **Admin -> FaucetPay API** in the sidebar and click
   **Check balance** to confirm the integration works.
6. Send some funds to your FaucetPay wallet so the faucet can pay out.

## 8. Configure the faucet

In **Admin -> Settings -> Faucet** set the values you want:

- **Reward / claim**: e.g. `0.0005`
- **Cooldown (seconds)**: `300` (= 5 minutes)
- **Daily claim limit**: `200`
- **Referral %**: `20`

In **Admin -> Settings -> Security** turn on:

- One account per IP
- One claim per IP
- VPN / proxy detection
- reCAPTCHA (paste your site & secret keys)
- Login attempt limits

## 9. Optional - reCAPTCHA v2

1. Get keys at https://www.google.com/recaptcha/admin (choose v2,
   "I'm not a robot").
2. In admin panel: **Settings -> Security**, enable reCAPTCHA and paste
   the site key & secret key.

## 10. Optional - Cron job

Set up a daily cron in cPanel -> **Cron Jobs**:

```
0 3 * * * /usr/local/bin/php /home/USERNAME/public_html/cron/clean.php > /dev/null 2>&1
```

This trims old logs / login attempts / sponsor clicks and updates contest
statuses (upcoming -> active -> ended).

You can also call it via web with a secret key:

```
https://faucet.example.com/cron/clean.php?key=YOUR_APP_SECRET
```

## 11. Optional - Customize content

In **Admin -> Content** you can edit:

- Homepage intro text
- Terms & Conditions
- Privacy Policy

In **Admin -> FAQ** you can add/edit/delete FAQ entries.

In **Admin -> Announcement** you can show a yellow announcement bar at
the top of every page.

## 12. SEO

The site already includes:

- `robots.txt` (edit it to update the Sitemap URL)
- `/sitemap.xml` -> rewritten to `/sitemap.xml.php` by `.htaccess`
- Bootstrap 5 responsive layout
- Per-page `<title>` and meta description from settings

## 13. Going live checklist

- [ ] `config/config.php` has correct DB credentials and SITE_URL
- [ ] `APP_SECRET` is a long random string
- [ ] `DEBUG` is set to `false`
- [ ] Admin password has been changed
- [ ] FaucetPay API key is configured and balance is funded
- [ ] reCAPTCHA enabled with valid keys
- [ ] Privacy Policy / Terms text edited
- [ ] HTTPS enforced (cPanel -> SSL/TLS Status -> AutoSSL)
- [ ] `COOKIE_SECURE = true` once HTTPS is confirmed
- [ ] Cron job scheduled

That's it. Enjoy your new Dogecoin faucet!
