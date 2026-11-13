<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RemoveTaskAssigneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $query = Task::query();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->getKey());
        }

        $task = $query->findOrFail($this->route('task'));

        return Gate::forUser($user)->allows('manageAssignees', $task);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
