<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTaskAssigneesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->user()?->tasksCreated()->findOrFail($this->route('task'));

        Gate::authorize('manageAssignees', $task);

        return true;
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
