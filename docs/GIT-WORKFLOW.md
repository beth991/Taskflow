# TaskFlow Git Workflow

## Repository

- Main development branch: `main`.
- Remote name: `origin`.
- GitHub repository: `https://github.com/beth991/Taskflow`.

Confirm local settings with:

```sh
git branch --show-current
git remote -v
```

## Check working changes

Before staging or requesting a commit, inspect the branch, short status, and complete diff:

```sh
git status --short
git diff --check
git diff
```

Never stage `.env`, credentials, generated cache files, or unrelated files. Stage only the intended reviewed files, then inspect the index:

```sh
git add <reviewed-files>
git diff --cached --check
git diff --cached --stat
git diff --cached
git status
```

## Commit convention

Use Conventional Commit messages, for example:

```text
feat: add task status filter
docs: document TaskFlow database schema
```

Keep commits focused on complete, reviewed work. Do not create empty commits. Commit only after explicit user approval.

## Push

Check branch and worktree before publishing:

```sh
git status -sb
git log origin/main..HEAD --oneline
```

Push only the approved branch and commits, and only after explicit user approval:

```sh
git push origin main
```

Verify synchronization after pushing:

```sh
git status -sb
git rev-list --left-right --count origin/main...main
```

Do not force-push as part of the standard workflow.
