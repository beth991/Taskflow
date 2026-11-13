# TaskFlow Testing

## Run tests

Run the full suite with:

```sh
php artisan test
```

Feature tests use the SQLite in-memory connection configured in `phpunit.xml`, so they do not require or modify the local MySQL database. Database-backed tests use Laravel's `RefreshDatabase` trait against that isolated test connection. Running the suite applies migrations only to the in-memory SQLite database.

## Current feature coverage

`tests/Feature/AuthenticationTest.php` covers:

- Successful registration and automatic sign-in.
- Password hashing and ignoring a submitted role value.
- Passwords at 72 bytes register and log in; longer passwords fail validation.
- Registration validation failures.
- Successful login and invalid credentials.
- Login and registration rate limits.
- Session ID regeneration on registration and login.
- Logout.
- Authentication required for the dashboard.

`tests/Feature/TaskManagementTest.php` covers:

- Creating tasks, assigning the creator from the authenticated user, and defaulting status to incomplete.
- Required title validation and accepted 99/100-character titles; 101 characters are rejected at request time.
- Listing only the current user's tasks and rejecting access, update, or delete attempts for another user's task.
- Updating titles without changing the creator or completion status, and deleting a task with its pivot rows.
- Toggling status while ignoring extra fields, and filtering all/completed/incomplete tasks.
- Rejecting invalid filter values and requiring authentication on task routes.

`tests/Feature/TaskAssigneeManagementTest.php` covers:

- Adding one or multiple assignees and preserving pivot timestamps.
- Removing an assignee.
- Rejecting unknown user IDs and duplicate IDs in one request without changing assignments.
- Preventing duplicate pivot rows when an existing assignee is submitted again.
- Returning 404 and preserving assignments when another user attempts to add or remove assignees.
- Displaying assignees on the task list and edit page.

Role-based authorization tests remain planned for a later milestone.

`tests/Feature/TaskModelTest.php` covers task creation, creator and assignee relationships, multiple tasks/assignees, pivot timestamps, duplicate prevention, foreign-key integrity and delete behavior, the incomplete default, and the 100-character title declaration. SQLite does not enforce `VARCHAR(n)` lengths, so the title-limit test verifies the migration's `VARCHAR(100)` declaration while also storing a 100-character title. MySQL column enforcement is not exercised by the suite.

## Database migrations

To apply migrations in a chosen local environment after review, run:

```sh
php artisan migrate
```

This command was not run while implementing Milestone 2. The test suite exercises the migrations in SQLite memory instead.

## Code style

Check PHP formatting with:

```sh
vendor/bin/pint --test
```

After making milestone changes, run the relevant tests, then Pint, and inspect `git diff` and `git status` before requesting commit approval.
