# Roles and Permissions

TaskFlow supports two account roles: Admin and Member. Roles affect the signed-in frontend presentation. They do not grant different task ownership permissions.

## Role assignment

- Public registration always creates a Member. The registration request validates only name, email, password, and password confirmation; it does not accept a role.
- The `users.role` column defaults to `member`, including for existing users when the additive role migration is applied.
- The initial Admin is provisioned deliberately through `Database\\Seeders\\TaskFlowAdminSeeder`, not through a public registration route. See [README account setup](../README.md#accounts-and-roles) for environment variables and the command.
- The Admin seeder creates only the first Admin. It is safe to rerun for the same Admin email, refuses to promote a Member, and refuses a second Admin with a different email.
- Once an Admin exists, Admins can manage roles for other accounts from `/admin/users`. They cannot change their own role, and the last active Admin cannot be demoted.

## Frontend differences

The shared responsive layout displays an Admin or Member role badge and uses role-specific dashboard and task navigation labels. The dashboard copy and task-list links are also role-specific. Both roles use the same TaskFlow task features and consistent visual design.

## Task access rules

| Action | Admin | Member |
| --- | --- | --- |
| Register publicly | Registration creates Member; Admin cannot be selected | Registration creates Member |
| Create task | Yes | Yes |
| List tasks | Tasks created by this Admin only | Tasks created by or assigned to this Member |
| View task details | Own created tasks only | Own created or assigned tasks |
| Edit title or notes | Own created tasks only | Own created tasks only |
| Change status | Own created tasks; may reopen | Own created tasks; assigned tasks may be marked complete only |
| Delete task | Own created tasks only | Own created tasks only |
| Add or remove assignees | Own created tasks only | Own created tasks only |
| Manage shared tags | Yes | No |
| Select existing task tags | Own tasks | Tasks the Member is allowed to create or edit |
| View all users | Yes | No |
| Change another user's role | Yes | No |
| Change own role | No | No |
| Demote the last active Admin | No | No |

**Admin does not have global task-management access.** An Admin cannot view, update, delete, or manage assignees on a task created by another user. A Member can view tasks assigned to them and confirm them as complete, but assignment alone does not grant permission to edit task details or notes, reopen a completed task, delete the task, or manage assignees. If the Member is also the creator, creator permissions apply.

These rules are enforced server-side. Task-list queries are scoped to the creator, with assigned tasks added for Members. Edit, delete, and assignee operations resolve through the authenticated user's `tasksCreated()` relationship and are authorized by `TaskPolicy`. The status endpoint permits an assigned Member to set completion to true, but rejects reopening an assigned task. Task details show the user who most recently confirmed completion only to Admin; one assignee's confirmation completes the task, and all assignees do not need to confirm. Blade visibility is not the authorization boundary.
