# BankVWA: Bank Vulnerable Web Application

A training bank for learning web security, inspired by DVWA. It's a realistic online-banking app
in plain PHP + MariaDB. The banking features are built **securely** first, so that later lab modules
can show what goes wrong when a defence is removed.

> **Warning: local use only.** BankVWA is a teaching lab. Never deploy it on a public server
> or a shared network. The Docker setup binds to `127.0.0.1` for this reason.

## Quick start (Docker, recommended)

```bash
docker compose up -d --build
```

Open **http://localhost:8090**. The database is created automatically on first start.

| Task | Command |
|---|---|
| Reset all data | `docker compose exec app php setup.php` |
| See emails (password reset links) | `docker compose exec app cat /tmp/bankvwa-mail.log` |
| Stop | `docker compose down` |
| Stop and delete the database | `docker compose down -v` |

## Running without Docker for the app

Needs PHP 8.x with `pdo_mysql`, and a MariaDB/MySQL server. The defaults in `config/config.php`
expect the database on `127.0.0.1:3307`:

```bash
docker run -d --name bankvwa-db -p 127.0.0.1:3307:3306 \
  -e MARIADB_RANDOM_ROOT_PASSWORD=1 -e MARIADB_DATABASE=bankvwa \
  -e MARIADB_USER=bankvwa -e MARIADB_PASSWORD=bankvwa_pass mariadb:11

php setup.php            # create tables + sample data (wipes existing data)
php -S localhost:8080    # then open http://localhost:8080
```

Every setting in `config/config.php` can be overridden with an environment variable
(`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, `BASE_URL`).
Reset emails are written to `bankvwa-mail.log` in the system temp folder.

## Sample logins (fictional people)

| Username | Password | Role |
|---|---|---|
| `arjun` | `Bank@1234` | customer: savings + checking, has a saved payee |
| `priya` | `Bank@1234` | customer |
| `admin` | `Admin@1234` | administrator |

## Features

| Area | Page | Security idea it demonstrates |
|---|---|---|
| Login / logout | `auth/login.php`, `auth/logout.php` | prepared statements, `password_hash`, session ID regeneration, generic errors, POST-only logout |
| Registration | `auth/register.php` | server-side validation, DB `UNIQUE` constraints, DB transactions |
| Password reset | `auth/forgot-password.php`, `auth/reset-password.php` | random single-use expiring tokens stored hashed, no account enumeration, links built from config not the `Host` header |
| Dashboard | `dashboard/` | authorization by `user_id` from the session |
| Accounts | `banking/accounts.php` | IDOR prevention (`WHERE id = ? AND user_id = ?`) |
| Transactions | `banking/transactions.php` | safe dynamic queries, `LIKE` escaping, output encoding |
| Transfer | `banking/transfer.php` | exact `DECIMAL` money, row locks, atomic balance check, Post/Redirect/Get |
| Beneficiaries | `banking/beneficiaries.php` | validate on input, encode on output |
| Statements | `banking/statements.php` | no user-controlled file paths, CSV formula-injection guard |
| Profile | `banking/profile.php` | re-authentication before password change |
| Admin | `admin/` | server-side role check (`require_admin()`), audit log |

## Project layout

```
config/       settings (config.php) and the PDO connection (database.php)
includes/     auth.php (session, login checks, e(), param(), audit(), helpers), header/footer, txn-list
auth/         login, logout, registration, password reset
dashboard/    home page after login
banking/      accounts, transactions, transfer, beneficiaries, statements, profile
admin/        admin dashboard (stats + audit log) and user list
assets/css/   styles (plain CSS, includes print styles for statements)
database.sql  schema: users, accounts, transactions, beneficiaries, password_resets, audit_logs
setup.php     builds the schema and loads sample data
```

## Helpers you'll see everywhere (`includes/auth.php`)

- `require_login()` / `require_admin()`: put at the top of protected pages
- `e($text)`: escape text before putting it in HTML
- `param($_GET, 'name')` / `password_param($_POST, 'name')`: read form/URL input safely
- `db()`: the shared PDO connection
- `audit($userId, 'action', 'details')`: write to the audit log
