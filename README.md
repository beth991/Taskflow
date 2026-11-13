# TaskFlow

TaskFlow is an internal team task management application. It helps a team record work, assign tasks, and track completion. Admin and Member accounts receive role-specific dashboards and navigation.

## Six required features

1. Registration, login, and logout. Public registration always creates a Member; users cannot choose their role.
2. Task listing with all, completed, and incomplete filters, and task detail pages.
3. Task creation with a required title (maximum 100 characters) and optional notes.
4. Editing task title and notes, changing status, and deleting tasks according to server-side authorization.
5. Adding and removing assignees on tasks the signed-in user is authorized to manage.
6. Admin and Member dashboards/navigation with a consistent responsive design.

Other implemented team tools include shared Task Tags and Admin user/role management. Admins can manage tags and change another user's role subject to protection against self-demotion and removal of the last Admin. The Admin completion attribution is shown on a task detail page the Admin is authorized to view.

### Current task permission behavior

- Admin can view, edit, delete, change status, and manage assignees for every task, including tasks created by Members.
- A Member can see tasks they created and tasks assigned to them. A Member can edit/delete task details and manage assignees only on tasks they created. An assignee may confirm an assigned task as complete, but cannot edit its details, delete it, manage its assignees, or reopen it.

## Technology

- Laravel Framework 13.42 (Composer constraint: `^13.17`)
- PHP 8.4 target (Composer constraint: `^8.3`)
- Blade
- Tailwind CSS 4 and Vite
- Vanilla JavaScript
- MySQL 8.4 for the application database
- PHPUnit 12 and SQLite in-memory for automated tests

## System requirements

- PHP version supported by the project (`^8.3`; PHP 8.4 is the project target) with Laravel-required extensions and PDO MySQL.
- Composer 2.
- MySQL 8.4.
- Node.js and npm for frontend assets.

For the local Windows workflow below, use XAMPP for PHP/MySQL or an equivalent local PHP 8.4 and MySQL 8.4 installation. Docker is not required.

## Installation

### Windows with XAMPP

1. Install/start XAMPP and start MySQL from the XAMPP Control Panel. Confirm the PHP CLI used by PowerShell is PHP 8.4 and has Laravel's required extensions, including PDO MySQL. Install Composer 2 and Node.js/npm if they are not already available.
2. Open PowerShell in the project directory and install dependencies:

   ```powershell
   composer install
   if (-not (Test-Path .env)) { Copy-Item .env.example .env }
   php artisan key:generate
   npm install
   npm run build
   ```

   If `.env` already exists, preserve it instead of copying over it. The repository's `.env.example` defaults to SQLite; for the XAMPP MySQL workflow, set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, and the intended `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in your untracked local `.env`.
3. Create the selected empty local database (for example, `taskflow_db`) in phpMyAdmin or MySQL. Verify the connection settings and target database before applying schema changes.
4. After verifying the database target, run the migration command below. Start the Laravel development server with `php artisan serve` and open the URL Artisan prints (normally `http://127.0.0.1:8000`). For asset hot reload, run `npm run dev` in a second PowerShell window.

The Composer `setup` script runs migrations automatically. Use the explicit manual steps in this README instead, so you can verify the database target before any migration.

### Apply database migrations

Only after confirming `.env` points to the intended local database, apply the checked-in schema:

   ```sh
   php artisan migrate
   ```

This creates Laravel support tables and the TaskFlow users/roles, tasks, assignee, notes/completion attribution, and tag schema. This documentation does not imply that migrations have been run against your database. Never use `migrate:fresh` or `db:wipe` on a database containing data.

## Run the application

```sh
php artisan serve
```

Visit the URL printed by Artisan. For frontend hot reload during development, run `npm run dev` in another terminal.

## Optional Docker deployment notes (VPS)

Docker is not required for the supported local XAMPP workflow. The following optional deployment notes are retained for the repository's Docker files; they have not been validated by this documentation update. Docker uses a separate ignored `docker.env` file and does not read or copy the local XAMPP `.env`. The build context excludes all `.env*` and `docker.env*` files. MySQL has no published host port; only Nginx is published, bound to `127.0.0.1` so a host reverse proxy can provide public HTTPS.

### Requirements

- Docker Engine and the Docker Compose plugin on the Ubuntu VPS.
- A host-level HTTPS reverse proxy (for example, Nginx or Caddy) forwarding to `127.0.0.1:8080`.
- An empty or backed-up Docker MySQL volume before first deployment.

### Configure and start

From the project directory on the VPS:

```sh
cp docker.env.example docker.env
chmod 600 docker.env
```

Edit `docker.env` on the VPS. Set `APP_URL` to the HTTPS site URL, choose a free local `APP_PORT` if needed, and provide unique strong values for `MYSQL_PASSWORD` and `MYSQL_ROOT_PASSWORD`. Generate an application key with a trusted PHP/Laravel installation using `php artisan key:generate --show`, then place the output in `APP_KEY`. Do not put secrets in the Dockerfile, source control, or command history. `APP_ENV=production` and `APP_DEBUG=false` are enforced by the image and Compose configuration; the example file contains no credentials.

The MySQL image uses its initialization variables only when the data volume is first initialized. Editing a password in `docker.env` later does not rotate the password inside an existing database; rotate the database account deliberately and update the app setting together. Do not delete the volume to resolve a credential mismatch.

Build and start services:

```sh
docker compose --env-file docker.env build
docker compose --env-file docker.env up -d
```

The asset stage uses `npm ci` when a lock file is available and falls back to `npm install --no-package-lock` otherwise. For identical dependency resolution across clean checkouts, add and review a tracked `package-lock.json` in a separately approved change.

Check service state and the database health check:

```sh
docker compose --env-file docker.env ps
docker compose --env-file docker.env logs --tail=100 app nginx db
```

MySQL must report `healthy` before the app starts. Application migrations are deliberately **not** run at container startup. After confirming the target VPS database and taking a backup, apply them manually:

```sh
docker compose --env-file docker.env exec app php artisan migrate --force
```

Stop the services while preserving their named volumes:

```sh
docker compose --env-file docker.env down
```

Do not add `--volumes`/`-v` when stopping; that removes persistent Docker volumes, including the database.

### Database backup

Create encrypted or access-restricted backups on the VPS and copy them off-host. This command writes a temporary client credentials file inside the database container with restrictive permissions, streams the dump to a host file, and removes the temporary file:

```sh
umask 077
docker compose --env-file docker.env exec -T db sh -c 'umask 077; printf "[client]\\nuser=%s\\npassword=%s\\n" "$MYSQL_USER" "$MYSQL_PASSWORD" > /tmp/taskflow-client.cnf; mysqldump --defaults-extra-file=/tmp/taskflow-client.cnf --single-transaction "$MYSQL_DATABASE"; result=$?; rm -f /tmp/taskflow-client.cnf; exit $result' > taskflow-backup.sql
```

Encrypt or move the dump to protected off-host storage, verify it, and periodically test restoration in a separate environment. Do not publish the database port or commit backup files. Before any migration or update that changes schema, take and verify a fresh backup.

### Update the application

After reviewing and deploying the intended Git revision:

```sh
git pull --ff-only
docker compose --env-file docker.env build
docker compose --env-file docker.env up -d
docker compose --env-file docker.env ps
```

Review logs and health before manually running any required migration. Keep the previous image/revision and a verified database backup available for recovery. Avoid removing named volumes during routine updates.

The Docker app and MySQL share an internal network. The app image runs PHP-FPM as the unprivileged `www-data` user; the Nginx image contains only the public document root and serves `/var/www/html/public`. Production PHP configuration disables displayed errors and enables OPcache. No migration or seeder runs automatically.

## Accounts and roles

### Member account

Register at `/register`. Public registration validates the name, unique email, and confirmed password, then creates the account with the Member role. A submitted `role` field is not used. Passwords are hashed; registration passwords must be at least 8 characters and no more than 72 bytes.

### Initial Admin account

There is no public Admin registration and no sample Admin credential. Provision the first Admin deliberately using the existing `Database\Seeders\TaskFlowAdminSeeder`. The variable names are also listed in `.env.example`; set real values only in the untracked local `.env` or a secure process environment, never in a committed file:

- `TASKFLOW_ADMIN_NAME`
- `TASKFLOW_ADMIN_EMAIL`
- `TASKFLOW_ADMIN_PASSWORD` (at least 12 characters and no more than 72 bytes)

The seeder reads these values through `config/taskflow.php`, validates them, hashes the password through the User model, and never prints it. It creates only the first Admin. Re-running it for that same Admin email is safe; it refuses to promote an existing Member or create another Admin with a different email. On MySQL it serializes provisioning with a named advisory lock. The concurrency behavior has not been verified with a dedicated MySQL integration test.

Run the dedicated seeder after configuring the variables for the Artisan process:

```sh
php artisan db:seed --class='Database\Seeders\TaskFlowAdminSeeder'
```

After bootstrap, Admins can view accounts and change another user's role between Admin and Member at `/admin/users`. Admins cannot change their own role, and the application prevents a change that would leave no active Admin. If the database has no Admin account, provision the first one with the dedicated seeder before using Admin pages.

If Laravel configuration is cached, ensure the seeder process loads the intended variables. After provisioning, remove the bootstrap password from the process environment, clear any config cache that may contain it, and rebuild the cache only from the sanitized runtime environment. Do not place the bootstrap password in a committed file or shell command history.

### DatabaseSeeder

On a local or testing database, run the general seeder with:

```sh
php artisan db:seed
```

It creates a `Test User` only in `local` or `testing`. It returns without creating that user in production and does not provision an Admin. Do not use test accounts as production credentials; no demo password is documented here.

## Architecture and security

Web routes are defined in `routes/web.php`. Controllers coordinate requests, Form Requests validate input, Eloquent models define user/task/tag relations, and Policies enforce task, tag, and user permissions. Blade renders the pages; Vite builds Tailwind assets and the small Vanilla JavaScript behaviors. Feature tests live in `tests/Feature`.

The application uses authenticated routes, CSRF protection for web forms, throttling on login and registration, session regeneration after login/registration, and session invalidation on logout. Passwords are hashed by Laravel. Role values are represented by the `UserRole` enum and are not mass assignable; public registration creates Members. Protected actions are checked server-side. Eloquent/query-builder bindings protect database input, and Blade's escaped `{{ }}` syntax is used for dynamic output.

## Boundaries and assumptions

- This release focuses on internal task assignment, status, notes, shared tags, and Admin account/role management. It does not include deadlines, notifications, reporting, or complex analytics.
- Admin-wide task management is available as required by the assignment. Member task operations retain the assumptions described above.
- The default automated test database is SQLite in-memory. No dedicated MySQL concurrency integration environment is configured, so MySQL advisory-lock behavior must not be described as integration-tested.
- XAMPP is the local Windows setup. Docker is an optional separate deployment path documented below, not an installation prerequisite or the normal test environment.

## Tests and code style

Run the full test suite and check PHP formatting:

```sh
php artisan test
vendor/bin/pint --test
```

Feature tests use the SQLite in-memory database from `phpunit.xml`; they do not target the configured MySQL database. See [Testing](docs/TESTING.md) for coverage and the last verified results.

## Documentation

- [Database schema](docs/DATABASE.md)
- [Features and validation](docs/FEATURES.md)
- [Roles and permissions](docs/ROLES-AND-PERMISSIONS.md)
- [Testing](docs/TESTING.md)
- [Git workflow](docs/GIT-WORKFLOW.md)

## GitHub workflow

The primary branch is `main` and the configured remote is `origin`. See [Git workflow](docs/GIT-WORKFLOW.md) for status checks, commit conventions, and push steps.
