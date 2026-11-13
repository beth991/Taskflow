<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tasks\RemoveTaskAssigneeRequest;
use App\Http\Requests\Tasks\StoreTaskAssigneesRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\TaskIndexRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskStatusRequest;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(TaskIndexRequest $request): View
    {
        Gate::authorize('viewAny', Task::class);

        $filter = $request->validated('filter', 'all');
        $user = $request->user();
        $tasks = Task::query();

        if (! $user->isAdmin()) {
            $tasks->where(function ($query) use ($user): void {
                $query->where('user_id', $user->getKey());

                if ($user->isMember()) {
                    $query->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($user->getKey()));
                }
            });
        }

        $tasks->with(['assignees:id,name', 'creator:id,name', 'completedBy:id,name', 'tags:id,name'])
            ->latest();

        if ($filter === 'completed') {
            $tasks->where('is_completed', true);
        } elseif ($filter === 'incomplete') {
            $tasks->where('is_completed', false);
        }

        return view('tasks.index', [
            'tasks' => $tasks->paginate(10)->withQueryString(),
            'filter' => $filter,
            'user' => $user,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Task::class);

        return view('tasks.create', [
            'task' => new Task,
            'availableTags' => Tag::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(string $task): View
    {
        $task = $this->accessibleTask(auth()->user(), $task);
        Gate::authorize('view', $task);
        $task->load(['assignees:id,name', 'creator:id,name', 'completedBy:id,name', 'tags:id,name']);

        return view('tasks.show', compact('task'));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        $validated = $request->validated();
        DB::transaction(function () use ($request, $validated): void {
            $task = $request->user()->tasksCreated()->create([
                'title' => $validated['title'],
                'notes' => $validated['notes'] ?? null,
                'is_completed' => false,
            ]);
            $task->tags()->sync($validated['tag_ids'] ?? []);
        });

        return redirect()->route('tasks.index')->with('status', 'Task created successfully.');
    }

    public function edit(string $task): View
    {
        $task = $this->ownedTask($task, 'update');
        $task->load('assignees:id,name');
        $task->load('tags:id,name');
        $availableUsers = User::query()
            ->whereNotIn('id', $task->assignees->modelKeys())
            ->orderBy('name')
            ->get(['id', 'name']);
        $availableTags = Tag::query()->orderBy('name')->get(['id', 'name']);

        return view('tasks.edit', compact('task', 'availableUsers', 'availableTags'));
    }

    public function update(UpdateTaskRequest $request, string $task): RedirectResponse
    {
        $task = $this->ownedTask($task, 'update');
        $validated = $request->validated();
        DB::transaction(function () use ($task, $request, $validated): void {
            $task->update($request->safe()->only(['title', 'notes']));

            if (array_key_exists('sync_tags', $validated)) {
                $task->tags()->sync($validated['tag_ids'] ?? []);
            }
        });

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
        $task = $this->accessibleTask($request->user(), $task);
        Gate::authorize('updateStatus', $task);

        $isCreator = (int) $task->user_id === (int) $request->user()->getKey();
        $isCompleted = $request->boolean('is_completed');

        abort_unless($isCreator || ($request->user()->isMember() && $isCompleted), 403);

        $task->is_completed = $isCompleted;
        $task->completed_by = $isCompleted ? $request->user()->getKey() : null;
        $task->save();

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

    private function accessibleTask(User $user, string $id): Task
    {
        $task = Task::query()->whereKey($id);

        if (! $user->isAdmin()) {
            $task->where(function ($query) use ($user): void {
                $query->where('user_id', $user->getKey());

                if ($user->isMember()) {
                    $query->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($user->getKey()));
                }
            });
        }

        return $task->firstOrFail();
    }
}
