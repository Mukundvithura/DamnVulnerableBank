# BankVWA: Bank Vulnerable Web Application

A training bank for learning web security, inspired by DVWA. It is a realistic online-banking
app in plain PHP + MariaDB. The app was first built **securely**, then ten real vulnerabilities
were introduced into the actual banking pages so each can be exploited and then fixed.

> **Warning: local use only.** BankVWA is intentionally vulnerable. All customers and data are
> fictional. Never deploy it on a public server or shared network.

> The original **secure** version is preserved in this repo's git history. Restore it any time with
> `git checkout .` (or check out the "Secure baseline" commit).

## Running it

Needs PHP 8.x with `pdo_mysql` and a MariaDB/MySQL server. The database runs in Docker:

```bash
docker run -d --name bankvwa-db -p 127.0.0.1:3307:3306 \
  -e MARIADB_RANDOM_ROOT_PASSWORD=1 -e MARIADB_DATABASE=bankvwa \
  -e MARIADB_USER=bankvwa -e MARIADB_PASSWORD=bankvwa_pass mariadb:11

php setup.php                 # create tables + sample data (wipes existing data)
php -S 0.0.0.0:8080 -t .      # then open http://localhost:8080
```

> Use `0.0.0.0`, **not** `localhost` — `php -S localhost:8080` binds IPv6 only, so `127.0.0.1`
> (needed for some demos) is refused.

For the **CORS** demo, run a second server on another port so the attacker page has a different origin:

```bash
php -S 0.0.0.0:8095 -t .      # open http://localhost:8095/labs/attack-cors.html
```

## Sample logins (fictional)

| Username | Password | Role |
|---|---|---|
| `arjun` | `Bank@1234` | customer (savings + current, has a saved payee) |
| `priya` | `Bank@1234` | customer |
| `admin` | `Admin@1234` | administrator |

SQL-injection bypass: username `admin'#` with any password logs you in as the administrator.

## Vulnerabilities

Ten issues live in the real banking pages. Full details, impact, and a vulnerable-code-vs-fix
comparison for each are in **`SECURITY_REPORT.docx`**.

| # | Vulnerability | Location | How to trigger |
|---|---|---|---|
| F-01 | SQL Injection (auth bypass) | `auth/login.php` | username `admin'#` + any password |
| F-02 | OS Command Injection | `admin/diagnostics.php` | host `127.0.0.1 & whoami` |
| F-03 | Broken Authentication | `auth/login.php`, `includes/auth.php` | no lockout; session ID not rotated on login |
| F-04 | CSRF (account takeover) | `banking/profile.php` | open `labs/attack-csrf.html` while logged in |
| F-05 | SSRF | `banking/profile.php` | avatar URL `file:///C:/Windows/win.ini` |
| F-06 | Stored XSS (cross-user) | `includes/txn-list.php` | transfer to Priya with a `<script>` note |
| F-07 | Directory Traversal | `banking/statements.php` | `?file=../../config/config.php` |
| F-08 | Clickjacking (one-click transfer) | no frame headers + `banking/transfer.php` | open `labs/attack-clickjack.html` |
| F-09 | CORS Misconfiguration | `api/balance.php` | open `labs/attack-cors.html` (from `:8095`) |
| F-10 | Reflected XSS | `banking/transactions.php` | search `<script>alert(document.cookie)</script>` |

Attacker proof-of-concept pages live in `labs/`:
`attack-csrf.html`, `attack-cors.html`, `attack-clickjack.html`.

## Report

- **`SECURITY_REPORT.docx`** — the full penetration-test report (PurpleSec structure): executive
  summary, scope, findings summary, and per-finding vulnerable-code-vs-mitigation. Open in Word,
  update the Table of Contents (F9), and export to PDF.
- `report-assets/` — screenshots embedded into the report findings.
- `SECURITY_REPORT.html` — the same content as a printable web page (optional).

## Project layout

```
config/       settings (config.php) and the PDO connection (database.php)
includes/     auth.php (session, helpers), header/footer, txn-list
auth/         login, logout, registration, password reset
dashboard/    home page after login
banking/      accounts, transactions, transfer, beneficiaries, statements, profile
admin/        admin dashboard (stats + audit log), user list, diagnostics (command injection)
api/          balance.php (CORS-misconfigured JSON endpoint)
labs/         attacker proof-of-concept HTML pages
assets/css/   styles
database.sql  schema; setup.php builds it and loads sample data
```
