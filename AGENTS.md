# TaskFlow — Team Task Management

## Project Stack

- Laravel 13
- PHP 8.4
- Blade
- Tailwind CSS
- Vanilla JavaScript
- MySQL
- GitHub

Follow Laravel's established conventions and use the project's existing tooling. Do not add dependencies without a clear technical reason and user approval.

## Product Scope

TaskFlow is a team task management application. The first release is limited to the required features below. Do not add priorities, deadlines, tags, complex statistics charts, or other unrequested features.

### Required Features

- User registration and login using Laravel Authentication.
- Separate Admin and Member dashboards with different navigation, while keeping the visual design consistent.
- Task listing with filters for completed and incomplete tasks.
- Create, view, edit, and delete tasks.
- Task title is required and limited to 100 characters.
- Add and remove task assignees.
- Many-to-many relationship between `User` and `Task` through `task_user`.

### Access Rules

**Admin**

- Can view all tasks and task details.
- Can create tasks.
- Can edit, delete, change status, and manage assignees only for tasks the Admin created.

**Member**

- Can view tasks they created and tasks assigned to them.
- Can create tasks.
- Can edit and delete only tasks they created.
- Can manage assignees only for tasks they created.

Enforce these rules on the server for every relevant action. Hiding links or buttons in Blade is not authorization.

### Registration and Admin Provisioning

- Newly registered users must automatically receive the Member role.
- Never accept a role from public registration input or allow users to assign their own role.
- Create or promote Admin accounts only through a deliberate, restricted, and secure administrative mechanism. Do not expose Admin creation through public registration.

## Design System

- Dark theme with background `#0A0A0A`.
- Sidebar `#111111`.
- Cards `#1A1A1A`.
- Borders `#2A2A2A`.
- Electric blue accent `#3B82F6`.
- Primary text is white.
- Support desktop, tablet, and mobile layouts.
- Keep Admin and Member experiences visually consistent while providing their respective dashboard content and navigation.
- Use accessible semantic HTML, visible focus states, and sufficient contrast.

## Data Model

The core database tables are:

- `users`
- `tasks`
- `task_user`

Represent task assignment as a many-to-many relationship. Follow Laravel naming and migration conventions, define appropriate foreign keys and indexes, and use the project's MySQL configuration. Do not run migrations unless the user explicitly requests it.

## Security Standards

- Use Laravel Authentication for registration and login.
- Validate input with dedicated Form Request classes where appropriate.
- Enforce authorization with Laravel Policies and apply them to every protected operation.
- Keep CSRF protection enabled for state-changing web requests.
- Use Eloquent or parameterized query builder operations; never interpolate untrusted input into SQL.
- Escape output in Blade by default. Do not render untrusted content as raw HTML.
- Protect mass assignment with explicit `$fillable` or `$guarded` rules and validated data.
- Hash passwords using Laravel's supported hashing facilities; never store or log plaintext passwords.
- Never expose `.env` contents, credentials, application keys, or other secrets in responses, logs, views, or Git.
- Do not weaken authentication, authorization, session, or other security configuration to make a feature work.

## Code Organization and Quality

- Follow official Laravel conventions and the existing project structure.
- Keep responsibilities separated across Controllers, Models, Form Requests, Policies, and Blade Views.
- Avoid duplicated logic; extract shared behavior when it improves clarity.
- Keep controllers focused and use named routes and route model binding where suitable.
- Keep frontend behavior in Vanilla JavaScript; do not introduce a frontend framework.
- Use Tailwind CSS and existing project asset tooling for styling.
- Do not add dependencies without a clear reason and explicit user approval.
- Preserve existing project behavior unless the requested feature requires a change.

## Testing

Add or update tests for relevant behavior. Cover at least:

- Registration and login.
- Admin and Member authorization boundaries.
- Task create, read, update, and delete behavior.
- Adding and removing assignees.
- Completed and incomplete task filters.
- Required title validation and the 100-character maximum.

Use the test database and project testing conventions. Do not run migrations against a configured non-test database. Report which tests were run and their result; do not claim tests passed unless they were run.

## Git and GitHub

- Use Conventional Commits.
- Organize work into small commits grouped by completed and tested features.
- Before proposing a commit, inspect `git status` and `git diff`.
- Never add `.env`, credentials, or other secrets to the repository.
- Do not commit or push without the user's explicit approval.
- Do not combine all work into one large commit.

## Working Rules

- Work on one stage at a time. Do not move to the next stage without the user's approval.
- Do not delete important files without the user's approval.
- Do not change Windows security configuration.
- Do not install additional packages without the user's approval.
- Do not run database migrations unless the user explicitly requests them.
- Respect the scope and explicit constraints in the current user request. If the user limits changes to specific files, do not modify other files.
