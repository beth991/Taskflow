# Roles and Permissions

TaskFlow supports two account roles: Admin and Member. Roles affect the signed-in frontend and authorization. Admin can manage every task; Member permissions follow the documented development assumptions below.

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
| List tasks | All tasks | Tasks created by or assigned to this Member |
| View task details | All tasks | Own created or assigned tasks |
| Edit title or notes | All tasks | Own created tasks only |
| Change status | All tasks | Own created tasks; assigned tasks may be marked complete only |
| Delete task | All tasks | Own created tasks only |
| Add or remove assignees | All tasks | Own created tasks only |
| Manage shared tags | Yes | No |
| Select existing task tags | Own tasks | Tasks the Member is allowed to create or edit |
| View all users | Yes | No |
| Change another user's role | Yes | No |
| Change own role | No | No |
| Demote the last active Admin | No | No |

**Admin has global task management access**, as required by the technical assignment. Admin may view, edit, delete, change status, and manage assignees on tasks created by any user. The assignment leaves Member task operation details open; TaskFlow's chosen assumption is that Members can view assigned tasks and confirm them complete, but only creators can edit details, reopen, delete, or manage assignees. If the Member is also creator, creator permissions apply.

These rules are enforced server-side. Task-list and detail queries are global for Admin; Member queries are scoped to tasks the Member created or is assigned to. Task mutation queries are global for Admin and creator-scoped for Members, then authorized by `TaskPolicy`. The status endpoint allows Admin to change any task; an assigned Member may set completion to true but cannot reopen an assigned task. Task details show the user who most recently confirmed completion only to Admin; one assignee's confirmation completes the task, and all assignees do not need to confirm. Blade visibility is not the authorization boundary.
