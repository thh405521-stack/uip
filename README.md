# Data Analysis Portal — Backend (Laravel)

A standalone Laravel API providing just two things:

1. **Auth** — login / logout / token refresh (JWT bearer tokens, no cookies).
2. **Data Analysis Dashboard** — `GET /api/v1/data-analysis/dashboard`,
   returning real, computed KPIs/charts from a small seeded dataset
   (universities + projects + users).

This is a fresh Laravel 13 project (not a trimmed copy of a larger
codebase) — it runs completely independently, with its own database.

## Setup with XAMPP (MySQL)

This project is configured out of the box for MySQL via XAMPP, using a
database named `uip-nub`:

1. Start **Apache** and **MySQL** from the XAMPP control panel.
2. Open `http://localhost/phpmyadmin`, click **New**, and create a database
   named `uip-nub` (leave collation as default).
3. In the project folder:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   php artisan serve
   ```
4. The API is now at `http://localhost:8000`.

`.env.example` already points at `DB_DATABASE=uip-nub` with XAMPP's default
MySQL credentials (`root`, no password) — no manual `.env` editing needed
unless your XAMPP setup differs from the defaults.

## Setup with XAMPP (MySQL)

This project is configured out of the box for MySQL via XAMPP, using a
database named `uip-nub`:

## Demo accounts (created by the seeder)

| Email | Password | Role |
|---|---|---|
| `analyst@example.com` | `password` | `data_analyst` — can view the dashboard |
| `admin@example.com` | `password` | `admin` — can also view the dashboard |

Other seeded background users have `student`/`university` roles — they can
log in, but calling the dashboard endpoint with their token returns
`403 Only Data Analysis Portal accounts can view this dashboard`, matching
the intended access rule.

## Endpoints

| Method | Path | Auth | Notes |
|---|---|---|---|
| POST | `/api/v1/auth/login` | — | `{ email, password }` → `{ access_token, refresh_token, redirect, ... }` |
| POST | `/api/v1/auth/logout` | — | `{ refresh_token }` |
| POST | `/api/v1/auth/refresh-token` | — | `{ refresh_token }` → new token pair |
| GET | `/api/v1/data-analysis/dashboard` | Bearer token | `data_analyst` or `admin` role only |

Every response uses the same JSON envelope: `{ success, message, data, errors, meta }`.


## Configuration notes

- **Database**: configured for MySQL (`uip-nub`, see above). If you'd
  rather use the zero-setup SQLite option instead, set `DB_CONNECTION=sqlite`
  in `.env` and remove/comment the other `DB_*` lines — `php artisan migrate`
  will then create `database/database.sqlite` automatically. Note that
  `DataAnalysisDashboardService::userGrowthSeries()` uses MySQL's
  `DATE_FORMAT()` for the monthly chart — swap it for SQLite's `strftime()`
  or Postgres's `to_char()` if you switch engines.
- **JWT_SECRET**: set to any long random string in `.env` before deploying
  anywhere real — the example value is for local development only. Generate
  one with `php -r "echo bin2hex(random_bytes(32));"`.
- **CORS**: `FRONTEND_URL` in `.env` controls which origin is allowed to
  call this API (defaults to `http://localhost:5173`, the Vite dev server
  default for the companion React frontend). Update it to your deployed
  frontend's URL in production.
- **Re-seeding**: `php artisan migrate:fresh --seed` wipes and rebuilds
  the demo data (fresh random signup/project dates) if you want a clean
  slate.

## What's in here

```
app/Http/Controllers/Api/Auth/   LoginController, LogoutController, RefreshTokenController
app/Http/Controllers/Api/        DataAnalysisDashboardApiController
app/Http/Middleware/             UipAuthMiddleware (Bearer JWT guard), UipCorsMiddleware
app/Services/                    UipJwtService, RoleService, DataAnalysisDashboardService
app/Models/                      User, Role, RefreshToken, University, Project,
                                  SavedDashboard, DataExport
database/migrations/             users/roles/refresh_tokens + the dashboard's
                                  source tables (universities, projects) +
                                  workspace tables (saved_dashboards, data_exports)
database/seeders/                DatabaseSeeder — 2 login accounts, ~40 background
                                  users, 6 universities, 120 projects, all spread
                                  realistically over time so every chart has
                                  real data to show
```

Every number the dashboard returns is computed live from these seeded
tables via real Eloquent queries — nothing is hardcoded.

## Pairs with

The companion `frontend/` package (React + Vite) — same auth contract and
dashboard response shape, so it talks to this backend with zero changes
needed on either side. Point its `vite.config.js` proxy (or your deployed
env config) at wherever this backend is running.
