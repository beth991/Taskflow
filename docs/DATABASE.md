# TaskFlow Database

This reference follows the checked-in migrations. It documents the schema defined by those files; it does not claim that migrations have been run against any particular database.

## Application tables

### `users`

The Laravel users migration defines:

| Column | Definition | Purpose |
| --- | --- | --- |
| `id` | Unsigned bigint primary key | User identifier. |
| `name` | String | Display name. |
| `email` | String, unique | Login identity. |
| `email_verified_at` | Nullable timestamp | Framework email verification field. |
| `password` | String | Hashed account password. |
| `remember_token` | Nullable remember token | Framework session remember field. |
| `created_at`, `updated_at` | Nullable timestamps | Record timestamps. |
| `role` | String(20), default `member`, indexed | Role added by an additive migration. |

Supported model roles are `admin` and `member`, represented by `App\\Enums\\UserRole`. Public registration does not accept a role and relies on the database default.

The same Laravel migration also defines `password_reset_tokens` (`email` primary key, `token`, nullable `created_at`) and `sessions` (`id` primary key, nullable indexed `user_id`, nullable IP/user-agent, payload, and indexed activity time).

### `tasks`

| Column | Definition | Purpose |
| --- | --- | --- |
| `id` | Unsigned bigint primary key | Task identifier. |
| `user_id` | Foreign key to `users.id` | Creator of the task. |
| `title` | String(100) | Required task title; request validation enforces the limit. |
| `is_completed` | Boolean, default `false` | Completion state. |
| `created_at`, `updated_at` | Timestamps | Record timestamps. |

Deleting a user who created tasks is restricted by the foreign key to preserve task ownership.

### `task_user`

| Column | Definition | Purpose |
| --- | --- | --- |
| `task_id` | Foreign key to `tasks.id` | Assigned task. |
| `user_id` | Foreign key to `users.id` | Assignee. |
| `created_at`, `updated_at` | Timestamps | Assignment timestamps. |

The composite primary key (`task_id`, `user_id`) prevents duplicate assignments. Both foreign keys cascade on deletion, removing pivot rows when a task or assigned user is deleted. This does not override the restrictive creator foreign key on `tasks.user_id`.

## Relationships

- `Task::creator()` is `belongsTo(User::class, 'user_id')`.
- `User::tasksCreated()` is `hasMany(Task::class, 'user_id')`.
- `Task::assignees()` is `belongsToMany(User::class, 'task_user')->withTimestamps()`.
- `User::assignedTasks()` is `belongsToMany(Task::class, 'task_user')->withTimestamps()`.

The task list in the current application queries `tasksCreated()`. Being an assignee does not itself grant permission to view or manage another user's task.

## Laravel support tables

The checked-in base migrations also define framework infrastructure tables:

- `cache` and `cache_locks` for the database cache store.
- `jobs`, `job_batches`, and `failed_jobs` for queue infrastructure.

These tables are framework support schema, not additional TaskFlow user-facing features.

## Applying migrations

The migration command is:

```sh
php artisan migrate
```

Confirm the active database connection and target before running it. Automated feature tests use SQLite in-memory and do not verify MySQL-specific behavior.
