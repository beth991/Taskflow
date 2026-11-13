<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task)
            || ($user->isMember() && $task->assignees()->whereKey($user->getKey())->exists());
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $this->owns($user, $task)
            || ($user->isMember() && $task->assignees()->whereKey($user->getKey())->exists());
    }

    public function manageAssignees(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    private function owns(User $user, Task $task): bool
    {
        return (int) $task->user_id === (int) $user->getKey();
    }
}
