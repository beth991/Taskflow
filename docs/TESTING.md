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
- `TaskManagementTest`: create and update title validation, 99/100/101-character boundary, authenticated creator assignment, incomplete default, ownership isolation, task update/delete, status changes, all/completed/incomplete filters, pagination, invalid filters, and guest access.
- `TaskAssigneeManagementTest`: adding/removing one or multiple assignees, foreign and duplicate user IDs, duplicate assignments, pivot timestamps, ownership restrictions, guest access, and displayed names.
- `RoleBasedFrontendTest`: Admin and Member dashboard/navigation differences, guest redirect, and Admin access isolation from another user's tasks.
- `TaskModelTest`: Eloquent relationships, pivot timestamps and uniqueness, foreign keys and deletion rules, incomplete default, and declared title column length.
- `UserRoleMigrationTest`: Member default for a pre-existing user when the role migration is applied in SQLite memory.
- `UserRoleValidationTest`: supported Admin and Member enum values.
- `TaskFlowAdminSeederTest`: first Admin creation, same-email idempotency, refusal of a second Admin or Member promotion, config-based input, and password output redaction.
- `DatabaseSeederTest`: test account creation in local/testing and no test account in production.
- `MySqlAdvisoryLockTest`: lock acquisition results, timeout, release success/failure, and preserving callback exceptions. The lock connection is mocked; these tests do not exercise concurrent MySQL connections.

SQLite does not enforce MySQL's `VARCHAR` length behavior in the same way. Runtime title length is covered by request validation, while schema tests inspect the migration declaration. A separate integration check with two concurrent processes on a dedicated MySQL test database is still required to verify cross-process initial-Admin provisioning. Never run such a check against production data.

## Last verified results

After Milestone 5.2 implementation, the full suite reported **77 tests passed and 323 assertions**. Laravel Pint `--test` passed. This result was observed before the documentation-only Milestone 8 changes; tests were not rerun for those documentation changes.
