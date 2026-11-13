# TaskFlow Features

This document describes the six required features that are implemented in the current project. The role frontend changes presentation only; Admin does not receive cross-owner task permissions.

## 1. Registration and authentication

- Guests can register at `/register`, log in at `/login`, and authenticated users can log out.
- Name and email are required; email must be unique. Password is required, confirmed, at least 8 characters, and at most 72 bytes.
- Login and registration POST routes are rate-limited. Successful registration and login regenerate the session; logout invalidates the session and regenerates the CSRF token.
- Passwords are stored using Laravel's `hashed` cast.
- Public registration uses only the validated name, email, and password. The `role` input is ignored, and the database default assigns `member`.
- Admin provisioning is restricted to the dedicated `TaskFlowAdminSeeder`; see [Roles and permissions](ROLES-AND-PERMISSIONS.md).

## 2. Task list and status filters

- Authenticated users can view their created tasks at `/tasks`.
- Filters are `all`, `completed`, and `incomplete`; invalid filter values fail validation.
- Each entry shows title, completion state, creation time, and assignee names. The list is paginated at 10 tasks per page.
- The list is scoped to tasks created by the signed-in user. Assignee membership alone does not expose another user's task in this list.

## 3. Create a task

- Authenticated users can create tasks from `/tasks/create`.
- The title is required and limited to 100 characters on the server.
- The authenticated user is set as creator by the Eloquent relationship; request fields cannot select a different creator.
- New tasks start incomplete.

## 4. Edit, status, and delete

- A task creator can edit the task title, change its completed/incomplete status, or delete it.
- Title validation is the same for create and edit: required, string, maximum 100 characters.
- The status endpoint validates only `is_completed`; extra request fields are not used to update the task.
- Tasks belonging to another user are scoped out and return not found for edit, update, status, and delete attempts.

## 5. Assignee management

- A task creator can add one or more existing users as assignees from the task edit page and remove current assignees.
- User IDs must exist; duplicate IDs in one request are rejected. Re-adding an existing assignee does not create a duplicate pivot record.
- Assignee names are shown on the task list and edit page. Email addresses are not shown by this feature.
- Only a task creator can manage its assignees. CSRF protection is enabled on state-changing forms.

## 6. Role-specific frontend

- The shared layout displays an Admin or Member badge and role-specific navigation labels.
- The dashboard content and task links differ between Admin and Member while using the same visual design and task actions.
- Both roles use the same creator-only task scope. Admin can manage only tasks the Admin created; there is no role-based global task management.
- Guests are redirected to login before accessing the dashboard or task routes.

## Short usage flow

1. Register a Member or log in to an existing account.
2. Open the dashboard, then create a task or visit the task list.
3. Filter tasks by completion status; edit a task to manage its title and assignees.
4. Use the task row action to change completion state or delete a task.

## Route summary

| Method | URI | Name | Access |
| --- | --- | --- | --- |
| GET / POST | `/register` | `register`, `register.store` | Guest; POST rate-limited |
| GET / POST | `/login` | `login`, `login.store` | Guest; POST rate-limited |
| GET | `/dashboard` | `dashboard` | Authenticated |
| POST | `/logout` | `logout` | Authenticated |
| GET | `/tasks` | `tasks.index` | Authenticated; own created tasks |
| GET / POST | `/tasks/create`, `/tasks` | `tasks.create`, `tasks.store` | Authenticated |
| GET / PUT / DELETE | `/tasks/{task}/edit`, `/tasks/{task}` | `tasks.edit`, `tasks.update`, `tasks.destroy` | Authenticated; creator only |
| PATCH | `/tasks/{task}/status` | `tasks.status.update` | Authenticated; creator only |
| POST / DELETE | `/tasks/{task}/assignees` | `tasks.assignees.store`, `tasks.assignees.destroy` | Authenticated; creator only |
