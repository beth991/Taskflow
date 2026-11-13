# TaskFlow

TaskFlow is a team task management application. It helps users create and track their tasks, filter them by completion status, and assign existing users to tasks. Admin and Member accounts receive role-specific dashboard and navigation labels while using the same task features and ownership rules.

## Six required features

1. Public registration, login, and logout. Public registration always creates a Member; users cannot select or submit their own role.
2. A task list with all, completed, and incomplete filters, plus task detail pages.
3. Task creation with a required title of at most 100 characters and optional task notes.
4. Editing task title and notes, changing status, and deleting tasks created by the signed-in user.
5. Adding and removing assignees on tasks created by the signed-in user.
6. Role-specific Admin and Member dashboard content and navigation.

Admin can view all tasks and details, including Member-created tasks, but can edit, delete, change status, and manage assignees only on tasks created by that Admin. A Member's task list includes tasks the Member created and tasks assigned to them. Members can confirm assigned tasks as complete; Admin sees the confirming Member marked on the task detail page. Assignment does not grant permission to edit notes or details, reopen a completed task, delete it, or manage assignees.

Task tags are an additional team feature: Admin manages the shared tag list, and Members can select existing tags on tasks they are allowed to create or edit.

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

## Installation

1. Install PHP dependencies:

   ```sh
   composer install
   ```

2. Create the local environment file and application key. In PowerShell:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Create an empty local MySQL database (for example, `taskflow_db`) and configure `.env` for it. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL installation. The checked-in `.env.example` defaults to SQLite; configure the intended local database before migrating. Never commit or share `.env` or its secrets.

4. Install and build frontend assets:

   ```sh
   npm install
   npm run build
   ```

5. After confirming the configured database is the intended local database, apply the migrations:

   ```sh
   php artisan migrate
   ```

   Migrations create the Laravel support tables, add the Member-default role column, and create the task and assignee tables. This documentation does not imply that migrations have been run against your database.

## Run the application

```sh
php artisan serve
```

Visit the URL printed by Artisan. For frontend hot reload during development, run `npm run dev` in another terminal.

## Accounts and roles

### Member account

Register at `/register`. Public registration validates the name, unique email, and confirmed password, then creates the account with the Member role. A submitted `role` field is not used. Passwords are hashed; registration passwords must be at least 8 characters and no more than 72 bytes.

### Initial Admin account

There is no public Admin registration and no sample Admin credential. Provision the first Admin deliberately using the existing `Database\Seeders\TaskFlowAdminSeeder`. Supply these process environment variables through a secure environment or secret manager:

- `TASKFLOW_ADMIN_NAME`
- `TASKFLOW_ADMIN_EMAIL`
- `TASKFLOW_ADMIN_PASSWORD` (at least 12 characters and no more than 72 bytes)

The seeder reads these values through `config/taskflow.php`, validates them, hashes the password through the User model, and never prints it. It creates only the first Admin. Re-running it for that same Admin email is safe; it refuses to promote an existing Member or create another Admin with a different email. On MySQL it serializes provisioning with a named advisory lock.

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
