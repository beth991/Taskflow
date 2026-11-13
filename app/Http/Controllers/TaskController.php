<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tasks\RemoveTaskAssigneeRequest;
use App\Http\Requests\Tasks\StoreTaskAssigneesRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\TaskIndexRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(TaskIndexRequest $request): View
    {
        Gate::authorize('viewAny', Task::class);

        $filter = $request->validated('filter', 'all');
        $tasks = $request->user()->tasksCreated()->with('assignees:id,name')->latest();

        if ($filter === 'completed') {
            $tasks->where('is_completed', true);
        } elseif ($filter === 'incomplete') {
            $tasks->where('is_completed', false);
        }

        return view('tasks.index', [
            'tasks' => $tasks->paginate(10)->withQueryString(),
            'filter' => $filter,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Task::class);

        return view('tasks.create', ['task' => new Task]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        $request->user()->tasksCreated()->create([
            'title' => $request->validated('title'),
            'is_completed' => false,
        ]);

        return redirect()->route('tasks.index')->with('status', 'Task created successfully.');
    }

    public function edit(string $task): View
    {
        $task = $this->ownedTask($task, 'update');
        $task->load('assignees:id,name');
        $availableUsers = User::query()
            ->whereNotIn('id', $task->assignees->modelKeys())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tasks.edit', compact('task', 'availableUsers'));
    }

    public function update(UpdateTaskRequest $request, string $task): RedirectResponse
    {
        $task = $this->ownedTask($task, 'update');
        $task->update(['title' => $request->validated('title')]);

        return redirect()->route('tasks.index')->with('status', 'Task updated successfully.');
    }

    public function destroy(string $task): RedirectResponse
    {
        $task = $this->ownedTask($task, 'delete');
        $task->delete();

        return redirect()->route('tasks.index')->with('status', 'Task deleted successfully.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, string $task): RedirectResponse
    {
        $task = $this->ownedTask($task, 'updateStatus');
        $task->update($request->validated());

        return redirect()->route('tasks.index')->with('status', 'Task status updated.');
    }

    public function storeAssignees(StoreTaskAssigneesRequest $request, string $task): RedirectResponse
    {
        $task = $this->ownedTask($task, 'manageAssignees');
        $task->assignees()->syncWithoutDetaching($request->validated('user_ids'));

        return redirect()->route('tasks.edit', $task)->with('status', 'Assignees added successfully.');
    }

    public function destroyAssignee(RemoveTaskAssigneeRequest $request, string $task): RedirectResponse
    {
        $task = $this->ownedTask($task, 'manageAssignees');
        $task->assignees()->detach($request->validated('user_id'));

        return redirect()->route('tasks.edit', $task)->with('status', 'Assignee removed successfully.');
    }

    private function ownedTask(string $id, string $ability): Task
    {
        $task = auth()->user()->tasksCreated()->findOrFail($id);

        Gate::authorize($ability, $task);

        return $task;
    }
}
