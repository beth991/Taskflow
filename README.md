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

Task management, assignees, role administration, and role-specific dashboards are planned milestones and are not implemented yet. See [feature progress](docs/FEATURES.md).

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

   The current migrations contain Laravel's base tables. TaskFlow task and role migrations will be documented when those milestones are implemented.

5. Install frontend dependencies and build the assets:

   ```sh
   npm install
   npm run build
   ```

## Run the application

Start Laravel's development server:

```sh
php artisan serve
```

For frontend development with hot reload, run `npm run dev` in a separate terminal.

## Authentication

Visit `/register` to create an account, `/login` to sign in, and use the navigation's **Log out** action to sign out. The authenticated `/dashboard` page is currently a milestone placeholder while task features are developed.

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
