<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RemoveTaskAssigneeRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
