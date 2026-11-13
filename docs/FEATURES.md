# TaskFlow Feature Progress

This document records implemented behavior only. Planned work is labeled clearly and is not represented as available functionality.

## Authentication — implemented

- Guest registration with name, unique email, password length, and confirmation validation.
- Registration passwords must be 8–72 bytes to match the default bcrypt input limit.
- New users receive the `member` role by database default; submitted role fields are not accepted when creating an account, and role is excluded from mass assignment.
- Passwords are hashed by the `User` model's Laravel `hashed` cast.
- Login uses Laravel's session guard, session ID regeneration, and a route-level attempt limit.
- Logout clears the authenticated session and regenerates its CSRF token.
- The dashboard route requires authentication.

## Role storage and Admin provisioning — implemented (Milestone 5.1)

- An additive migration adds an indexed `users.role` string with the `member` default, including for existing accounts when applied; the User model casts it to a backed enum limited to `admin` and `member`.
- `User::isAdmin()` and `User::isMember()` provide role checks.
- `TaskFlowAdminSeeder` creates the first Admin from Laravel configuration, does not print the password, is idempotent for that Admin, and stops without changes if a Member owns the email or another Admin already exists.
- Role-based task authorization, role-specific dashboards, and deadlines are not implemented in this milestone.

## Database and Eloquent relationships — implemented

- `tasks` stores creator, a `VARCHAR(100)` title, completion state (default `false`), and timestamps.
- `task_user` connects tasks and assignees with timestamps and a composite primary key on (`task_id`, `user_id`).
- Task creator deletion is restricted while created tasks exist; task/user deletion cascades only to dependent pivot records.
- `Task::creator()` / `User::tasksCreated()` represent ownership; `Task::assignees()` / `User::assignedTasks()` represent assignment.

## Personal task management — implemented

- Authenticated users can list, create, edit, and delete only tasks they created.
- Task titles are required and limited to 100 characters on create and update requests.
- New tasks are created through the authenticated user's `tasksCreated()` relation and start incomplete.
- The task list shows title, completion status, and creation time, with all/completed/incomplete filters and pagination.
- Task status can be updated through a dedicated endpoint that accepts only `is_completed`.
- Foreign task IDs are scoped out and return not found; policies also authorize task operations.

## Assignee management — implemented

- Task owners can add one or more existing users as assignees and remove current assignees.
- Assignee changes are validated and authorized by the `manageAssignees` TaskPolicy ability; task IDs are resolved through the authenticated owner's created-task relation and foreign task IDs return not found.
- Duplicate IDs in a request are rejected, while adding an already assigned user is idempotent and does not create duplicate pivot rows.
- The task list and edit page display assignees; the edit page provides add and remove forms.
- Existing `task_user` foreign keys, composite primary key, and pivot timestamps are reused; no migration was added.

## Planned milestones

- Admin/Member task authorization and role-specific access rules.
- Full role-specific dashboard and navigation.
- Deadline storage, authorization, and status categories.

## Current routes

| Method | URI | Name | Access |
| --- | --- | --- | --- |
| GET | `/register` | `register` | Guest |
| POST | `/register` | `register.store` | Guest, rate limited |
| GET | `/login` | `login` | Guest |
| POST | `/login` | `login.store` | Guest, rate limited |
| GET | `/dashboard` | `dashboard` | Authenticated |
| POST | `/logout` | `logout` | Authenticated |
| GET | `/tasks` | `tasks.index` | Authenticated; own tasks only |
| GET | `/tasks/create` | `tasks.create` | Authenticated |
| POST | `/tasks` | `tasks.store` | Authenticated |
| GET | `/tasks/{task}/edit` | `tasks.edit` | Authenticated; own task only |
| PUT | `/tasks/{task}` | `tasks.update` | Authenticated; own task only |
| DELETE | `/tasks/{task}` | `tasks.destroy` | Authenticated; own task only |
| PATCH | `/tasks/{task}/status` | `tasks.status.update` | Authenticated; own task only |
| POST | `/tasks/{task}/assignees` | `tasks.assignees.store` | Authenticated; own task only |
| DELETE | `/tasks/{task}/assignees` | `tasks.assignees.destroy` | Authenticated; own task only |
