# TaskFlow Feature Progress

This document records implemented behavior only. Planned work is labeled clearly and is not represented as available functionality.

## Authentication — implemented

- Guest registration with name, unique email, password length, and confirmation validation.
- Registration passwords must be 8–72 bytes to match the default bcrypt input limit.
- Submitted role fields are not accepted when creating an account.
- Passwords are hashed by the `User` model's Laravel `hashed` cast.
- Login uses Laravel's session guard, session ID regeneration, and a route-level attempt limit.
- Logout clears the authenticated session and regenerates its CSRF token.
- The dashboard route requires authentication.

## Planned milestones

- Task and assignee database tables and Eloquent relationships.
- Task listing, status filters, and task create/edit/delete operations.
- Assignee management.
- Admin/Member roles, policies, and role-specific access rules.
- Full role-specific dashboard and navigation.

## Current routes

| Method | URI | Name | Access |
| --- | --- | --- | --- |
| GET | `/register` | `register` | Guest |
| POST | `/register` | `register.store` | Guest, rate limited |
| GET | `/login` | `login` | Guest |
| POST | `/login` | `login.store` | Guest, rate limited |
| GET | `/dashboard` | `dashboard` | Authenticated |
| POST | `/logout` | `logout` | Authenticated |
