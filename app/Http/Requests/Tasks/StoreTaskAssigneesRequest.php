<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTaskAssigneesRequest extends FormRequest
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
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ];
    }
}
