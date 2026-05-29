# Doge Faucet (PHP + MySQL + FaucetPay API)

A complete, mobile-friendly Dogecoin faucet website with FaucetPay
**instant auto-withdrawal**, referral system, ad management, sponsor
links, anti-cheat protections, and a full admin panel. Designed to run
on plain **cPanel shared hosting** - no Composer, no Node.js, no VPS
required.

> See [`INSTALL.md`](INSTALL.md) for step-by-step setup instructions.

## Highlights

- PHP 8 / MySQL / Bootstrap 5 / jQuery / Font Awesome
- FaucetPay API integration (auto payouts on every claim)
- Configurable reward, cooldown timer and daily claim limit
- 20% (configurable) lifetime referral commission
- Referral contests with live leaderboards
- Full admin panel: users, settings, ads, sponsors, contests, FAQ,
  announcements, content editor, contact messages, logs, IP blacklist,
  FaucetPay API tester
- Anti-abuse: one account per IP, one claim per IP, login attempt
  limiter, IP blacklist, optional VPN/proxy detection, optional
  reCAPTCHA v2, CSRF protection on every form, prepared statements,
  output escaping, security headers
- Bootstrap 5 dark theme, mobile-friendly, with header / sidebar /
  dashboard / popup / footer / between-content ad slots
- Cookie notice, announcement bar, analytics-code injection,
  maintenance mode

## Project layout

```
.
+-- index.php                    Public homepage
+-- login.php / register.php / logout.php / forgot.php / reset.php
+-- faq.php / contact.php / terms.php / privacy.php
+-- sponsors.php / sponsor_go.php
+-- sitemap.xml.php / robots.txt / .htaccess
+-- config/
|   +-- config.sample.php        (copy to config.php and edit)
|   +-- .htaccess                blocks direct access
+-- includes/                    PHP libraries
|   +-- init.php                 bootstrap (config, db, session, security)
|   +-- db.php                   PDO + helpers
|   +-- functions.php            settings, helpers, logging
|   +-- security.php             CSRF, rate limit, blacklist, recaptcha, vpn
|   +-- auth.php                 login / require_login / require_admin
|   +-- faucetpay.php            FaucetPay API client
|   +-- header.php / footer.php  shared layout for public pages
+-- user/
|   +-- dashboard.php
|   +-- claim.php / claim_action.php   (AJAX claim handler)
|   +-- withdrawals.php / withdraw.php
|   +-- referrals.php / profile.php
+-- admin/
|   +-- index.php                dashboard
|   +-- users.php / user_edit.php
|   +-- settings.php             (5 tabs)
|   +-- claims.php / withdrawals.php
|   +-- ads.php / sponsors.php / contests.php
|   +-- faq.php / content.php / announcements.php
|   +-- messages.php / logs.php / blacklist.php / api_test.php
|   +-- includes/header.php / footer.php
+-- assets/
|   +-- css/style.css
|   +-- js/app.js
|   +-- img/favicon.svg
+-- cron/
|   +-- clean.php                daily cleanup job
+-- sql/
|   +-- schema.sql               full database schema + defaults
+-- INSTALL.md
+-- README.md
```

## Default credentials

After importing `sql/schema.sql` you can log in with:

- **Username**: `admin`
- **Password**: `admin123`

**Change this immediately** from Admin -> Users.

## How payouts work

- **Faucet claims**: paid out **instantly** to the user's FaucetPay
  email via the FaucetPay `send` API. A row is added to the
  `withdrawals` table on every payout (success or failure).
- **Referral commission** (default 20%): credited to the referrer's
  in-site balance. The referrer can withdraw it on demand from
  `user/withdraw.php`. This is done because referral fractions can be
  smaller than FaucetPay's per-payout minimums.
- **Sponsor click rewards** (optional, configurable per sponsor link):
  also credited to balance, dedup'd per IP per 24h.

## Security

- All forms protected by per-session CSRF tokens
- All SQL via PDO prepared statements
- All output escaped via `e()` helper (htmlspecialchars)
- Passwords hashed with bcrypt (`PASSWORD_BCRYPT`)
- Session ID regenerated on login; `HttpOnly`, `SameSite=Lax`, optional
  `Secure` cookies
- Login attempt limiter with configurable lockout window
- IP blacklist (manual + ban-as-action)
- Optional VPN / proxy detection via proxycheck.io free API
- Optional reCAPTCHA v2 on register / login / contact / claim
- Anti-faucet-abuse rules:
  - One account per IP (toggle)
  - One claim per IP per cooldown (toggle)
  - Daily claim limit per user
  - VPN/proxy detection (toggle)
  - Banned-status check on every request
- Security headers: X-Content-Type-Options, X-Frame-Options,
  Referrer-Policy, X-XSS-Protection
- `.htaccess` blocks direct access to `/config`, `/includes`, `/sql`,
  `/cron`, dotfiles, `.sample`, `.sql`, `.md`, etc.

## License & disclaimers

This project is provided as-is for legitimate Dogecoin faucet
operators. You are responsible for compliance with local laws and with
FaucetPay's terms of service.
