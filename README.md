# Kerjago

A Southeast Asia recruitment marketplace connecting employers and jobseekers (Indonesia, Singapore, Malaysia, Philippines, Vietnam, Thailand). Jobseekers search and apply for jobs and track their application status; employers post jobs, search talent, and manage applicants through a simple pipeline.

## Stack

| Layer | Technology                                                   |
| --- |--------------------------------------------------------------|
| Backend | Laravel 13, PHP 8.5                                          |
| Frontend | Inertia v3 + Vue 3 + Tailwind CSS v4 (shadcn-vue components) |
| Auth | Laravel Fortify (with 2FA + passkeys from the starter kit)   |
| Database | PostgreSQL                                                   |
| Queue & cache | Redis (phpredis extension)                                   |
| Routing bridge | Laravel Wayfinder (typed TS route functions)                 |
| Search | Plain database queries for now — Search Engine planned       |
| Tests | Pest 4                                                       |
| Static analysis | Larastan (PHPStan level 10)                                  |

## Prerequisites

- **PHP 8.5** with extensions: `pdo_pgsql`, `redis` (phpredis), `mbstring`, `intl`
  - Verify: `php -m | grep -E 'pdo_pgsql|redis'` — both must appear.
- **Composer 2**
- **Node.js 22+** and npm
- **PostgreSQL 15+** running on `127.0.0.1:5432`
  - Local dev assumes user `postgres` with an empty password (adjust `.env` if yours differs).
- **Redis** running on `127.0.0.1:6379`
  - Verify: `php artisan tinker --execute 'Illuminate\Support\Facades\Redis::connection()->ping();'` after setup.

## First-time setup

```bash
git clone <repo-url> kerjago && cd kerjago

# 1. PHP dependencies
composer install

# 2. JS dependencies
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate
```

### Databases

Two databases: `kerjago` (dev) and `kerjago_test` (tests — `phpunit.xml` points at it).

```bash
createdb -h 127.0.0.1 -U postgres kerjago
createdb -h 127.0.0.1 -U postgres kerjago_test
```

Or via SQL: `CREATE DATABASE kerjago; CREATE DATABASE kerjago_test;`

### Configure `.env`

`.env.example` already ships correct defaults for local dev:

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kerjago
DB_USERNAME=postgres
DB_PASSWORD=

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Change `DB_USERNAME`/`DB_PASSWORD` if your Postgres uses different credentials.

> Note: the queue's database fallback table is `queue_jobs`, not `jobs` — the domain `Job` model owns the `jobs` table. Don't "fix" this.

### Migrate and seed

```bash
php artisan migrate --seed
```

The seeder creates demo data: 1 employer with 25 active + 5 draft jobs, 1 jobseeker with 1 application, and 15 extra jobseeker profiles.

## Running the app

```bash
composer run dev
```

This runs `php artisan dev`, which starts the PHP server, queue worker, log tail, and Vite dev server in one terminal. Then open http://localhost:8000.

Run pieces individually if you prefer:

```bash
php artisan serve        # HTTP server
npm run dev              # Vite (hot reload)
php artisan queue:work   # Redis queue worker
php artisan pail         # live log tail
```

### Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Employer | `employer@example.com` | `password` |
| Jobseeker | `jobseeker@example.com` | `password` |

### Key URLs

- `/jobs` — public job search (works logged out)
- `/dashboard` — role-specific dashboard after login
- `/employer/jobs`, `/employer/talent`, `/employer/company` — employer area
- `/applications`, `/profile` — jobseeker area

## Tests

Tests run against the `kerjago_test` Postgres database (configured in `phpunit.xml`) with `RefreshDatabase` — the dev `kerjago` database is never touched.

```bash
php artisan test --compact                          # full suite
php artisan test --compact --filter=ApplicationTest # one file/test
```

Postgres is required for tests: talent skill search uses `skills::text ilike`, which SQLite can't run.

## Quality gates

All of these must pass before merging:

```bash
vendor/bin/pint --dirty                          # PHP formatting (auto-fixes)
vendor/bin/phpstan analyse --memory-limit=1G     # static analysis, level 10, zero errors
php artisan test --compact                       # Pest suite
npm run lint                                     # ESLint (auto-fixes)
npm run format                                   # Prettier (auto-fixes)
npm run types:check                              # vue-tsc
npm run build                                    # production build must succeed
```

## Wayfinder (typed routes)

Frontend never hardcodes URLs — it imports generated route functions from `@/routes/...`. After adding or renaming a Laravel route:

```bash
php artisan wayfinder:generate
```

The Vite plugin regenerates automatically while `npm run dev` is running.

## Domain model in one minute

- `User` (ULID PK, `role` enum: `employer` | `jobseeker`) — one role forever per account
- `EmployerProfile` / `JobseekerProfile` — one per user; existence = "profile complete"
- `Job` — belongs to an employer profile; status `draft` → `active` → `closed`; only `active` is searchable/appliable
- `Application` — unique per (job, jobseeker profile); status `submitted`/`reviewed`/`shortlisted`/`rejected`; carries a **resume snapshot** copied at apply time (ADR 0006)
- Salaries are whole major currency units in `unsignedBigInteger` — `15000000` = IDR 15,000,000

### Conventions

- Business logic lives in single-purpose action classes under `app/Actions/` (e.g. `ApplyToJob`, `SearchJobs`) — controllers stay thin and inject them.
- Search stays behind `SearchJobs` / `SearchTalent` actions so the future Meilisearch swap touches only those two classes.
- Resume files live on the **private** `local` disk (`storage/app/private`), served only through the authorized download route `applications/{application}/resume`.
- Route gating: `role:employer|jobseeker` middleware checks the role; `profile.complete` redirects to profile setup before profile-dependent actions (posting jobs, applying, talent search).

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| `Unable to locate file in Vite manifest` | Run `npm run dev` (or `npm run build`) |
| `SQLSTATE[08006]` connection refused | Postgres not running, or wrong `DB_PORT` (must be 5432, not 3306) |
| `Class "Redis" not found` / queue errors | phpredis extension missing — `php -m \| grep redis`; install via `pecl install redis` |
| Tests fail with `database "kerjago_test" does not exist` | `createdb -h 127.0.0.1 -U postgres kerjago_test` |
| Redirect loop / stale config after editing `.env` | `php artisan config:clear` |
| Frontend imports from `@/routes/...` missing | `php artisan wayfinder:generate` |
| Resume download 404s locally after `migrate:fresh` | Old file paths point at deleted records — re-upload via profile page |
| PHPStan killed at 128M | Always pass `--memory-limit=1G` |
