# TaskFlow

TaskFlow is a team task management application under active development. Its goal is to give teams a clear place to organize shared work, assign responsibility, and track task progress.

## Technology

- Laravel 13
- PHP 8.4 (the Composer constraint is PHP `^8.3`)
- MySQL 8.4
- Blade
- Tailwind CSS 4
- Vanilla JavaScript

## Feature status

The authentication milestone is implemented: users can register, log in, and log out. Registration validates the submitted profile and a password between 8 and 72 bytes, ignores any submitted role, and stores passwords using Laravel's hashed password cast.

The database/model, personal task management, assignee management, and Milestone 5.1 role storage/provisioning are implemented. New users receive the Member role, and an initial Admin can be provisioned through a dedicated seeder. Role-based task authorization and role-specific dashboards remain planned for Milestone 5.2. Deadlines are not implemented. See [feature progress](docs/FEATURES.md).

## Official requirements and development assumptions

### Official requirements

- Admin and Member are the supported roles.
- Public registration always creates a Member and never accepts a role from the request.
- Admin accounts are provisioned through a restricted, deliberate mechanism rather than public registration.
- Role-based task permissions and different Admin/Member dashboards are planned for Milestone 5.2.

### Development assumptions

- The first Admin is provisioned by an operator using the dedicated command below; no sample Admin account is included. The default `DatabaseSeeder` creates a `Test User` only in local/testing and never in production.
- Existing users receive the Member role when the additive role migration is applied.
- Deadline behavior is outside Milestone 5.1 and remains unimplemented.

## Database schema

TaskFlow adds a role column to the existing Laravel `users` table and creates two task tables:

| Table | Important columns | Purpose |
| --- | --- | --- |
| `users` | `role` (`VARCHAR(20)`, default `member`, indexed) | Stores the user's Admin or Member role. Existing users receive `member` when the additive migration is applied. |
| `tasks` | `id`, `user_id`, `title` (`VARCHAR(100)`), `is_completed` (default `false`), timestamps | Stores each task and its creator. |
| `task_user` | `task_id`, `user_id`, timestamps | Joins tasks to their assignees; the `(task_id, user_id)` pair is the composite primary key, so duplicate assignments are rejected. |

`tasks.user_id` references `users.id` and restricts deleting a user who created tasks, preserving ownership. Both pivot foreign keys cascade when their parent task or user is deleted, so dependent assignment rows are cleaned up. `Task::creator()` and `User::tasksCreated()` represent task ownership. `Task::assignees()` and `User::assignedTasks()` represent the many-to-many assignment and include pivot timestamps.

The role and task schemas use additive migrations. The `User` model accepts only the `admin` and `member` roles. These migrations will not be applied to a local database until you run them.

## Requirements

- PHP 8.4 with the PHP extensions required by Laravel and PDO MySQL.
- Composer 2.
- MySQL 8.4.
- Node.js and npm to build or serve the frontend assets.

## Installation

1. Install PHP/Composer dependencies:

   ```sh
   composer install
   ```

2. Create a local environment file from the example and generate an application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows PowerShell, use `Copy-Item .env.example .env` for the first command.

3. Set the local database connection in `.env`. For the standard local TaskFlow setup, use `DB_CONNECTION=mysql` and `DB_DATABASE=taskflow_db`, then set the host, port, username, and password for your MySQL installation. Never commit `.env` or share its secrets.

4. For a new local database, apply the migrations available in this checkout:

   ```sh
   php artisan migrate
   ```

   This applies the Laravel base migrations and TaskFlow role, task, and pivot migrations to the configured local database. Do not run it against a database you do not intend to update. Role-based task authorization is not implemented yet.

5. Install frontend dependencies and build the assets:

   ```sh
   npm install
   npm run build
   ```

## Provision the initial Admin

Set the following variables in the process environment used to run Artisan. Use a temporary secret/environment manager for the password; do not persist the bootstrap password in `.env`:

- `TASKFLOW_ADMIN_NAME`
- `TASKFLOW_ADMIN_EMAIL`
- `TASKFLOW_ADMIN_PASSWORD` (at least 12 characters and no more than 72 bytes)

Keep the password out of command arguments and shell history. Never commit these values. The `config/taskflow.php` file maps the environment variables, and the seeder reads them through Laravel's `config()` helper.

If configuration is cached without these values, set the process environment variables and run `php artisan config:clear` before provisioning. Then run:

```sh
php artisan db:seed --class='Database\Seeders\TaskFlowAdminSeeder'
```

The seeder creates the account only if no Admin exists and the email is unused. Re-running it with the same Admin email makes no changes. If the email belongs to a Member or a different Admin already exists, it stops without modifying any account. The password is hashed by the User model and is not printed by the seeder. The default `DatabaseSeeder` does not invoke this seeder.

After provisioning, remove the temporary password from the process environment before rebuilding the production config cache. Laravel's cached configuration contains values loaded from environment variables, so protect the cache file and ensure the one-time password is not retained in it.

`DatabaseSeeder` is for local/testing only. It creates a test account with the factory's test password; its environment guard prevents that account from being created in production. Do not remove the guard or use the test account as production credentials.

## Run the application

Start Laravel's development server:

```sh
php artisan serve
```

For frontend development with hot reload, run `npm run dev` in a separate terminal.

## Authentication

Visit `/register` to create an account, `/login` to sign in, and use the navigation's **Log out** action to sign out. The authenticated `/dashboard` page remains a placeholder until role-specific dashboards are implemented.

## Task management

After signing in, visit `/tasks` to view your tasks, filter by completion status, create a task, edit its title, change its completion status, or delete it. On a task's edit page, its owner can add one or more existing users as assignees or remove current assignees. Titles are required and limited to 100 characters. Each task is scoped to its creator; other users' tasks are not shown or available through task routes, including assignee management endpoints.

## Testing and code style

Run the test suite with:

```sh
php artisan test
```

Run Laravel Pint in check-only mode with:

```sh
vendor/bin/pint --test
```

See [testing notes](docs/TESTING.md) for current test coverage and known environment requirements.

## Development workflow

Development is organized into reviewed milestones. Changes use Conventional Commits after the user approves each milestone commit. Pushes require separate user approval. See [Git workflow](docs/GIT-WORKFLOW.md).
