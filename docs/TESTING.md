# TaskFlow Testing

## Run tests

Run the full suite with:

```sh
php artisan test
```

Feature tests use the SQLite in-memory connection configured in `phpunit.xml`, so they do not require or modify the local MySQL database. Database-backed tests use Laravel's `RefreshDatabase` trait against that isolated test connection.

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

Task CRUD, status filters, assignee behavior, and role authorization tests are planned alongside their respective implementation milestones.

## Code style

Check PHP formatting with:

```sh
vendor/bin/pint --test
```

After making milestone changes, run the relevant tests, then Pint, and inspect `git diff` and `git status` before requesting commit approval.
