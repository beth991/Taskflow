# TaskFlow Features

This document describes the six required features and additional implemented team tools. Admin has global task visibility, but cross-owner task changes remain prohibited.

## 1. Registration and authentication

- Guests can register at `/register`, log in at `/login`, and authenticated users can log out.
- Name and email are required; email must be unique. Password is required, confirmed, at least 8 characters, and at most 72 bytes.
- Login and registration POST routes are rate-limited. Successful registration and login regenerate the session; logout invalidates the session and regenerates the CSRF token.
- Passwords are stored using Laravel's `hashed` cast.
- Public registration uses only the validated name, email, and password. The `role` input is ignored, and the database default assigns `member`.
- Admin provisioning is restricted to the dedicated `TaskFlowAdminSeeder`; see [Roles and permissions](ROLES-AND-PERMISSIONS.md).

## 2. Task list and status filters

- Authenticated users can view tasks at `/tasks`. Admin sees all tasks; Member sees tasks they created and tasks assigned to them.
- Filters are `all`, `completed`, and `incomplete`; invalid filter values fail validation.
- Each entry shows title, completion state, creation time, and assignee names. The list is paginated at 10 tasks per page. Selecting a task title opens its detail page.
- Assigned Members can confirm a task as complete; the task then displays the `Completed` label to users who can view it. The detail page marks the Member who most recently confirmed completion, and only Admin sees that marker and confirmer identity. Assignees cannot reopen completed tasks.

## 3. Create a task

- Authenticated users can create tasks from `/tasks/create`.
- The title is required and limited to 100 characters on the server.
- Optional notes can be added and edited by the task creator; notes are limited to 5,000 characters and displayed to the creator and assignees.
- The authenticated user is set as creator by the Eloquent relationship; request fields cannot select a different creator.
- New tasks start incomplete.

## 4. Edit, status, and delete

- A task creator can edit the task title and notes, change its completed/incomplete status, or delete it.
- A Member assigned to a task can confirm it as complete. An assignee cannot edit task details or reopen a completed task.
- Title validation is the same for create and edit: required, string, maximum 100 characters.
- The status endpoint validates only `is_completed`; extra request fields are not used to update the task.
- Admin can view another user's task and its detail page. Cross-owner edit-form, task-detail update, delete, and assignee-management attempts return not found. An assigned Member may mark the task complete; unrelated Members and Admins receive a forbidden response when changing its status.

## 5. Assignee management

- A task creator can add one or more existing users as assignees from the task edit page and remove current assignees.
- User IDs must exist; duplicate IDs in one request are rejected. Re-adding an existing assignee does not create a duplicate pivot record.
- Assignee names are shown on the task list and edit page. Email addresses are not shown by this feature.
- Only a task creator can manage its assignees. CSRF protection is enabled on state-changing forms.

## 6. Role-specific frontend

- The shared layout displays an Admin or Member badge and role-specific navigation labels.
- The dashboard content and task links differ between Admin and Member while using the same visual design and task actions.
- Admin can view all task lists and detail pages, but can edit, delete, change status, and manage assignees only on tasks the Admin created. Members can see tasks assigned to them and confirm them as complete, but can otherwise manage only tasks they created.
- Guests are redirected to login before accessing the dashboard or task routes.

## 7. Shared task tags

- Admin can list, create, edit, and delete shared tags from `/admin/tags`.
- Tag names are required, unique, and limited to 50 characters.
- Members cannot manage the shared tag list. They can select existing tags when creating or editing tasks they own.
- Task tag selection is validated against existing tag IDs, rejects duplicates, and synchronizes the many-to-many relation.
- Deleting a tag removes its pivot records without deleting tasks.

## 8. Admin user management

- Admin can view the paginated user list at `/admin/users` and change another user's role between Admin and Member.
- Public registration remains Member-only; role changes use a separate Admin-only PATCH endpoint.
- Admin cannot change their own role, and a role change cannot leave the application without an active Admin.
- The role update accepts only the `admin` and `member` enum values and changes no other user attributes.

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
| GET | `/tasks` | `tasks.index` | Authenticated; all tasks for Admin, own and assigned tasks for Member |
| GET | `/tasks/{task}` | `tasks.show` | Authenticated; any task for Admin, creator or assigned Member |
| GET / POST | `/tasks/create`, `/tasks` | `tasks.create`, `tasks.store` | Authenticated |
| GET / PUT / DELETE | `/tasks/{task}/edit`, `/tasks/{task}` | `tasks.edit`, `tasks.update`, `tasks.destroy` | Authenticated; creator only |
| PATCH | `/tasks/{task}/status` | `tasks.status.update` | Authenticated; creator may toggle, assigned Member may mark complete |
| POST / DELETE | `/tasks/{task}/assignees` | `tasks.assignees.store`, `tasks.assignees.destroy` | Authenticated; creator only |
| GET / POST | `/admin/tags` | `admin.tags.index`, `admin.tags.store` | Authenticated Admin only |
| GET / POST | `/admin/tags/create` | `admin.tags.create` | Authenticated Admin only |
| GET / PUT / DELETE | `/admin/tags/{tag}/edit`, `/admin/tags/{tag}` | `admin.tags.edit`, `admin.tags.update`, `admin.tags.destroy` | Authenticated Admin only |
