# TaskFlow Testing

## Commands

Run the full PHPUnit suite and check PHP style with Laravel Pint:

```sh
php artisan test
vendor/bin/pint --test
```

`phpunit.xml` explicitly selects SQLite with `DB_DATABASE=:memory:`. Database-backed feature tests use Laravel's test database lifecycle and apply migrations only to that in-memory SQLite database. The suite does not use the application's configured MySQL database. It does not prove MySQL-specific behavior or cross-process MySQL advisory-lock concurrency.

## Coverage

- `AuthenticationTest`: registration and login/logout, input validation, hashed passwords, default Member role, ignored submitted Admin role, 72-byte password boundary, rate limiting, session regeneration/invalidation, and guest/authenticated routes.
- `TaskManagementTest`: create and update title validation, 99/100/101-character boundary, optional notes creation/update/length validation, authenticated creator assignment, incomplete default, ownership isolation, task update/delete, status changes, all/completed/incomplete filters, pagination, invalid filters, and guest access.
- `TaskAssigneeManagementTest`: adding/removing one or multiple assignees, foreign and duplicate user IDs, duplicate assignments, pivot timestamps, ownership restrictions, guest access, and displayed names.
- `TagManagementTest`: Admin tag CRUD, required/unique/50-character validation, Member and guest denial, and pivot cleanup when a tag is deleted.
- `TaskTagManagementTest`: selecting multiple tags, shared tag use across tasks, syncing and clearing tags on update, invalid/duplicate tag ID rejection, task ownership enforcement, and tag display/empty state.
- `AdminUserManagementTest`: Admin-only paginated user list, Member/guest denial, role promotion/demotion, self-role protection, last-Admin guard, enum validation, and ignoring extra user fields.
- `RoleBasedFrontendTest`: Admin and Member dashboard/navigation differences, Member visibility of assigned tasks and notes, completion confirmation and Completed label, saved completion actor on task details, unrelated-user detail isolation, restriction against reopening assigned tasks, status filters, guest redirect, and Admin access isolation from another user's tasks.
- `TaskModelTest`: Eloquent relationships, pivot timestamps and uniqueness, foreign keys and deletion rules, incomplete default, and declared title column length.
- `UserRoleMigrationTest`: Member default for a pre-existing user when the role migration is applied in SQLite memory.
- `UserRoleValidationTest`: supported Admin and Member enum values.
- `TaskFlowAdminSeederTest`: first Admin creation, same-email idempotency, refusal of a second Admin or Member promotion, config-based input, and password output redaction.
- `DatabaseSeederTest`: test account creation in local/testing and no test account in production.
- `MySqlAdvisoryLockTest`: lock acquisition results, timeout, release success/failure, and preserving callback exceptions. The lock connection is mocked; these tests do not exercise concurrent MySQL connections.

SQLite does not enforce MySQL's `VARCHAR` length behavior in the same way. Runtime title length is covered by request validation, while schema tests inspect the migration declaration. A separate integration check with concurrent processes on a dedicated MySQL test database is still required to verify initial-Admin provisioning and Admin role changes under real MySQL locks. Never run such a check against production data.

## Last verified results

After Admin User Management, Task Tags, task notes, assignee completion confirmation, and task detail attribution, the full suite reported **112 tests passed and 519 assertions**. Laravel Pint `--test` and `git diff --check` passed. The suite uses SQLite in-memory; additive task migrations were not run against MySQL.
