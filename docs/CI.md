# Continuous Integration (CI)

This project includes a GitHub Actions workflow that runs the test suite across multiple PHP versions and database drivers (SQLite, MySQL, PostgreSQL).

Workflow path: `.github/workflows/ci.yml`

What the workflow does
- Runs on pushes and pull requests to `main`.
- Matrix runs across PHP 8.2 and 8.3.
- For each matrix entry the workflow runs tests against one of: SQLite, MySQL, or PostgreSQL.
- Uses services containers for MySQL and Postgres and configures the Laravel `.env` accordingly.

Run tests locally

SQLite (fast, default for local dev):

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan test --filter=StartTimeEntryCommandTest
```

MySQL (requires a local MySQL instance or Docker):

```bash
# with Docker run a MySQL container:
docker run --name timeshit-mysql -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=test -p 3306:3306 -d mysql:8.0

cp .env.example .env
php artisan key:generate
php artisan env:set DB_CONNECTION=mysql || true
php artisan env:set DB_HOST=127.0.0.1 || true
php artisan env:set DB_PORT=3306 || true
php artisan env:set DB_DATABASE=test || true
php artisan env:set DB_USERNAME=root || true
php artisan env:set DB_PASSWORD=root || true

php artisan migrate --force
php artisan test --filter=ActiveTimerConstraintMySqlTest
```

PostgreSQL (requires a local Postgres instance or Docker):

```bash
docker run --name timeshit-pg -e POSTGRES_DB=test -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=postgres -p 5432:5432 -d postgres:15

cp .env.example .env
php artisan key:generate
php artisan env:set DB_CONNECTION=pgsql || true
php artisan env:set DB_HOST=127.0.0.1 || true
php artisan env:set DB_PORT=5432 || true
php artisan env:set DB_DATABASE=test || true
php artisan env:set DB_USERNAME=postgres || true
php artisan env:set DB_PASSWORD=postgres || true

php artisan migrate --force
php artisan test --filter=ActiveTimerConstraintTest
```

Notes
- Tests that assert DB-level uniqueness will be skipped on DBs that don't support the migration approach used in the repository (e.g. MySQL tests are skipped on non-MySQL drivers).
- CI ensures migrations and tests run cleanly across the supported DB engines.
