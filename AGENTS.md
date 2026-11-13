# TaskFlow Development Guide

## Project overview

TaskFlow is an internal team task management application built with Laravel 13, PHP 8.4, Blade, Tailwind CSS 4, Vanilla JavaScript, Vite, and MySQL 8.4. Follow Laravel conventions and the existing project structure. Do not add packages or frameworks without a clear technical reason and the user's approval. Docker is not part of the normal development or test workflow.

## Important project structure

- `app/Enums/`: supported user roles.
- `app/Http/Controllers/`: web and authentication controllers.
- `app/Http/Requests/`: request validation.
- `app/Models/`: User, Task, and Tag Eloquent models and relationships.
- `app/Policies/`: task, tag, and user authorization rules.
- `app/Support/`: shared infrastructure such as MySQL advisory locking.
- `database/migrations/`: schema history; create additive migrations for schema changes.
- `database/seeders/`: local/test data and deliberate initial Admin provisioning.
- `resources/views/`: Blade layouts, authentication, dashboards, tasks, tags, and Admin pages.
- `resources/css/`, `resources/js/`, `vite.config.js`: Tailwind/Vite frontend assets and Vanilla JavaScript.
- `routes/web.php`: named web routes and authentication boundaries.
- `tests/Feature/`: HTTP, authorization, seeder, and workflow coverage.

## Product behavior and access rules

The application includes registration/login, role-specific dashboards/navigation, task listing and status filters, task create/view/edit/delete, notes, assignees, shared tags, Admin user/role management, and completion attribution.

- Public registration always creates a Member. Never accept a public role field as authority.
- Admin-only user and tag management must be authorized on the server through policies or equivalent backend checks.
- Members can see tasks they created or that are assigned to them. They can edit/delete task details and manage assignees only for tasks they created. An assignee can confirm an assigned task as complete; assignment does not grant detail-edit, delete, assignee-management, or reopen permission.
- Admins can view, edit, delete, change status, and manage assignees for all tasks, regardless of creator. Members can view tasks they created or that are assigned to them; they can edit/delete/manage assignees on tasks they created, and an assigned Member may confirm a task as complete. These Member operation details are project assumptions because the assignment leaves them open.
- Task completion attribution records the first successful completion transition; preserve atomic behavior so a later concurrent completion cannot replace the completing user.
- Admin role changes protect self-role changes and the last active Admin. Initial Admin creation is through `TaskFlowAdminSeeder`, not public registration.

When changing a permission, review the policy, controller query scope, Form Request, routes, Blade controls, and feature tests together. UI visibility is never a substitute for backend authorization. Preserve Task Tags, completion attribution, and assignee behavior when changing related code.

## Laravel, PHP, and Blade conventions

- Keep responsibilities separated among controllers, Form Requests, models, policies, and Blade views.
- Use named routes, route model binding where compatible with the existing IDOR behavior, Eloquent relationships, and eager loading for displayed relations.
- Validate request input server-side; use validated/safe input only. Keep creator IDs and role assignment out of untrusted mass assignment.
- Use explicit model fillable/guarded configuration. Set privileged fields only through deliberate trusted code paths.
- In Blade, use escaped `{{ }}` output for user-controlled values. Avoid raw `{!! !!}` output for untrusted data.
- Use Tailwind CSS 4 and the existing Vite build. Frontend behavior uses Vanilla JavaScript; do not add a framework.
- Avoid duplicated logic and unrelated refactoring. Keep the responsive dark TaskFlow design and accessible labels/focus states.

## Security requirements

- Keep Laravel authentication, CSRF middleware, session protections, and route middleware enabled.
- Authentication routes are throttled; do not weaken rate limits without a reviewed reason.
- Regenerate the session after authentication and invalidate it on logout.
- Hash passwords through Laravel's hashing facilities. Never store, return, log, or commit plaintext passwords, tokens, APP_KEY, or other secrets.
- Use Eloquent or parameterized queries; never concatenate untrusted values into SQL.
- Enforce ownership and role rules on every protected backend operation. Test manipulated IDs and forged fields, including `role`, `user_id`, completion fields, and tag/assignee IDs.
- Treat notes, task titles, tag names, and user names as untrusted output and retain Blade escaping.
- Do not modify `.env`, `docker.env`, or credentials without explicit authorization. Never add environment files or secrets to Git.
- The Admin seeder reads values through Laravel config (`taskflow.admin.*`); use a secure runtime environment and do not expose the bootstrap password in output or shell history.

## Database and migrations

The current application schema includes Laravel support tables plus `users`, `tasks`, `task_user`, `tags`, and `tag_task`. Tasks include nullable notes and `completed_by`; users have the `admin` or `member` role. Preserve foreign keys, deletion rules, composite pivot keys, and timestamps.

- Make schema changes with new additive migrations; do not rewrite applied migrations.
- Never run migrations, seeders, destructive database commands, or manual data cleanup unless the user explicitly requests the exact action and target.
- The normal PHPUnit setup uses SQLite in-memory (`phpunit.xml`). It is isolated from the application's MySQL configuration.
- MySQL concurrency integration testing is **not configured or verified**. SQLite unit/feature tests do not prove MySQL advisory-lock concurrency. Do not claim otherwise or connect tests to the normal development database.
- Do not use Docker for routine development or tests; no Docker command is part of the standard workflow.

## Testing and quality checks

Add or update focused feature tests for changed behavior, including authorization-denial cases and invalid/forged input. The suite should cover authentication, roles, tasks, filters, notes, completion attribution, assignees, tags, Admin user management, and safe output.

From PowerShell at the repository root, use:

```powershell
php artisan test
vendor/bin/pint --test
git diff --check
git status --short
git diff
```

Run tests only with the configured test database. Do not run them if configuration could direct them to a non-test database; inspect test configuration without printing `.env` secrets. Report exact commands and results; never infer a passing result.

## Git workflow

- Work on one approved stage at a time and preserve pre-existing local changes.
- Before editing, inspect `git status --short`; before review, inspect `git diff` and untracked files.
- Keep changes focused and make Conventional Commits only after a milestone is complete, reviewed, and tested.
- Stage only explicitly reviewed files. Check staged diff and status before commit.
- Never commit or push without the user's explicit approval. Never force-push.
- Never discard, reset, overwrite, or clean local work that is outside the requested scope.

## Operating boundaries

- Do not install dependencies without approval.
- Do not run migrations or seeders without explicit approval.
- Do not use Docker as part of the normal project workflow.
- Do not change Windows security settings.
- Do not make unrelated feature, source, test, migration, or configuration changes when the user limits the scope to documentation or named files.
- If an explicit product requirement conflicts with current code, document the discrepancy and ask for direction rather than silently broadening scope.
