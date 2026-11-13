# Roles and Permissions

TaskFlow supports two account roles: Admin and Member. Roles affect the signed-in frontend presentation. They do not grant different task ownership permissions.

## Role assignment

- Public registration always creates a Member. The registration request validates only name, email, password, and password confirmation; it does not accept a role.
- The `users.role` column defaults to `member`, including for existing users when the additive role migration is applied.
- The initial Admin is provisioned deliberately through `Database\\Seeders\\TaskFlowAdminSeeder`, not through a public registration route. See [README account setup](../README.md#accounts-and-roles) for environment variables and the command.
- The Admin seeder creates only the first Admin. It is safe to rerun for the same Admin email, refuses to promote a Member, and refuses a second Admin with a different email.

## Frontend differences

The shared responsive layout displays an Admin or Member role badge and uses role-specific dashboard and task navigation labels. The dashboard copy and task-list links are also role-specific. Both roles use the same TaskFlow task features and consistent visual design.

## Task access rules

| Action | Admin | Member |
| --- | --- | --- |
| Register publicly | Registration creates Member; Admin cannot be selected | Registration creates Member |
| Create task | Yes | Yes |
| List tasks | Tasks created by this Admin only | Tasks created by this Member only |
| Edit title or completion status | Own created tasks only | Own created tasks only |
| Delete task | Own created tasks only | Own created tasks only |
| Add or remove assignees | Own created tasks only | Own created tasks only |

**Admin does not have global task-management access.** An Admin cannot view, update, delete, or manage assignees on a task created by another user. Being an assignee does not grant permission to edit or manage the task, and assigned tasks are not included in the current task list unless the signed-in user also created them.

These rules are enforced server-side: task lookup is scoped through the authenticated user's `tasksCreated()` relationship and task operations are authorized by `TaskPolicy`. Blade visibility is not the authorization boundary.
