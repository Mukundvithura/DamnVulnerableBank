# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Security learners anywhere who find BankVWA on GitHub and clone it to run on their own machine, in the same way people use DVWA. They're typically cybersecurity students or self-taught practitioners. Their job is to study how web application weaknesses arise and how they're fixed, in an environment that looks and behaves like a real product rather than a toy.

## Product Purpose

BankVWA (Bank Vulnerable Web Application) is an open-source security training lab built as a working online bank. It exists so learners can see web security concepts in the context where they matter most: money, accounts and personal data. Success means a learner can run it in minutes, recognise it as a believable bank, and come away understanding both the weakness and the secure implementation.

## Positioning

The training happens **inside a realistic, fully working online bank**, not on disconnected demo pages. Every feature (login, accounts, transfers, beneficiaries, statements, admin) is a real banking workflow with real data relationships. So the stakes and the impact of a flaw are concrete: whose money moves, whose data leaks. The banking features are first built securely, which gives learners a correct reference to compare against.

## Operating Context

- Learners clone the repository, start it with `docker compose up -d --build`, and use it in a browser at `http://localhost:8090`. Alternatively: `php setup.php` + `php -S localhost:8080` with a local MariaDB.
- They sign in with seeded fictional accounts (customers `arjun`, `priya`; administrator `admin`), alongside their browser's developer tools and the source code.
- `php setup.php` resets the bank to a known state at any time. Password-reset emails go to a local log file, not a real mail server.

## Capabilities and Constraints

- **Built and working:** registration, login/logout, password reset, dashboard, accounts, transaction history with search and filters, fund transfers, beneficiaries, monthly statements (print view and CSV), profile (display name and password), admin dashboard with an audit log, and user list.
- **Stack (fixed):** plain PHP 8.x + PDO + MariaDB/MySQL, server-rendered pages, plain CSS, minimal vanilla JavaScript. No framework, so every line stays readable to students.
- **Local-only:** never deployed publicly. Docker binds the app to `127.0.0.1`, and the database is not published.
- **Undecided:** the structure and presentation of the dedicated training modules are not built yet.

## Brand Commitments

- Name: **BankVWA**.
- Setting: an Indian bank. Amounts are in rupees (₹), and names and formats are Indian.
- All people, accounts and data are fictional, now and in the future.
- The product must always be clearly identifiable as a training lab, never mistakable for a real bank. The footer and statements already say so.

## Evidence on Hand

- `README.md`: setup, sample logins, and the feature-to-security-concept table.
- Seed data in `setup.php`: three fictional users with accounts, transactions and one saved payee.
- No screenshots, testimonials, user counts, stars, or third-party endorsements exist. Future work must not invent them.

## Product Principles

1. **Real bank first.** Every training scenario lives inside a believable banking workflow with real consequences in the data.
2. **Secure reference, then contrast.** The correct implementation is built and visible, so learners can compare it with what went wrong.
3. **Readable by a student.** Plain PHP, one concept per file where possible, and comments that explain *why*.
4. **Safe by default to run.** Local-only, fictional data, one-command reset.
5. **Obviously a lab.** Realistic enough to teach, never convincing enough to impersonate a real bank.
