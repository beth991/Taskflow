@extends('layouts.app')

@section('title', auth()->user()->isAdmin() ? 'All tasks' : 'My tasks')

@section('content')
    <section class="mx-auto max-w-5xl">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-blue-400">{{ $user->isAdmin() ? 'Admin workspace' : 'Your tasks and assignments' }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $user->isAdmin() ? 'All tasks' : 'My tasks' }}</h1>
                <p class="mt-3 text-gray-400">
                    {{ $user->isAdmin() ? 'Review and manage tasks across the team.' : 'Tasks you created or that were assigned to you. You can manage tasks you created.' }}
                </p>
            </div>
            <a href="{{ route('tasks.create') }}" class="inline-flex justify-center rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400">Create task</a>
        </div>

        <nav class="mt-8 flex flex-wrap gap-2" aria-label="Filter tasks">
            @foreach (['all' => 'All tasks', 'incomplete' => 'Incomplete', 'completed' => 'Completed'] as $value => $label)
                <a href="{{ route('tasks.index', $value === 'all' ? [] : ['filter' => $value]) }}"
                    @class([
                        'rounded-full border px-4 py-2 text-sm font-medium transition',
                        'border-blue-500 bg-blue-500/15 text-blue-300' => $filter === $value,
                        'border-[#2A2A2A] text-gray-400 hover:border-gray-500 hover:text-white' => $filter !== $value,
                    ])
                    @if ($filter === $value) aria-current="page" @endif>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @if ($tasks->isEmpty())
            <div class="mt-6 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] px-6 py-14 text-center">
                <h2 class="text-xl font-semibold">{{ $filter === 'all' ? 'No tasks yet' : 'No matching tasks' }}</h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-400">
                    {{ $filter === 'all' ? 'Create your first task to get started.' : 'Try another status filter or create a task.' }}
                </p>
                @if ($filter === 'all')
                    <a href="{{ route('tasks.create') }}" class="mt-6 inline-flex rounded-lg bg-blue-500 px-4 py-2.5 font-semibold text-white transition hover:bg-blue-400">Create your first task</a>
                @endif
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($tasks as $task)
                    <article class="flex flex-col gap-4 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="min-w-0">
                            <h2 class="break-words text-lg font-medium {{ $task->is_completed ? 'text-gray-400 line-through' : 'text-white' }}"><a href="{{ route('tasks.show', $task) }}" class="hover:text-blue-300">{{ $task->title }}</a></h2>
                            <p class="mt-1 text-sm text-gray-500">
                                @if ((int) $task->user_id === (int) $user->getKey())
                                    Created by you
                                @elseif ($user->isAdmin())
                                    Created by {{ $task->creator->name }}
                                @else
                                    Assigned to you by {{ $task->creator->name }}
                                @endif
                            </p>
                            @if (filled($task->notes))
                                <p class="mt-3 whitespace-pre-line break-words text-sm text-gray-300">{{ $task->notes }}</p>
                            @endif
                            @if ($task->tags->isNotEmpty())
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm" aria-label="Task tags">
                                    <span class="text-gray-500">Tags:</span>
                                    @foreach ($task->tags as $tag)
                                        <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-2.5 py-1 text-xs text-blue-200">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-emerald-500/10 text-emerald-300' => $task->is_completed,
                                    'bg-amber-500/10 text-amber-300' => ! $task->is_completed,
                                ])>
                                    {{ $task->is_completed ? 'Completed' : 'Incomplete' }}
                                </span>
                                <time class="text-gray-500" datetime="{{ $task->created_at->toIso8601String() }}">Created {{ $task->created_at->format('M j, Y · H:i') }}</time>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                <span class="text-gray-500">Assignees:</span>
                                @forelse ($task->assignees as $assignee)
                                    <span class="rounded-full border border-[#3A3A3A] px-2.5 py-1 text-xs text-gray-300">
                                        {{ $assignee->name }}
                                        @if ($user->isAdmin() && $task->is_completed && (int) $assignee->getKey() === (int) $task->completed_by)
                                            <span class="ml-1 font-medium text-emerald-300" aria-label="This member completed the task">Completed</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-gray-500">None</span>
                                @endforelse
                            </div>
                        </div>

                        @if ($user->isAdmin() || (int) $task->user_id === (int) $user->getKey())
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                <form action="{{ route('tasks.status.update', $task) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_completed" value="{{ $task->is_completed ? '0' : '1' }}">
                                    <button type="submit" class="rounded-lg border border-[#3A3A3A] px-3 py-2 text-sm text-gray-200 transition hover:border-blue-500 hover:text-white">
                                        Mark {{ $task->is_completed ? 'incomplete' : 'complete' }}
                                    </button>
                                </form>
                                <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg border border-[#3A3A3A] px-3 py-2 text-sm text-gray-200 transition hover:border-gray-500 hover:text-white">Edit</a>
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-900 px-3 py-2 text-sm text-red-300 transition hover:border-red-500 hover:bg-red-950/50">Delete</button>
                                </form>
                            </div>
                        @elseif ($user->isMember() && ! $task->is_completed)
                            <form action="{{ route('tasks.status.update', $task) }}" method="POST" class="sm:self-center">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_completed" value="1">
                                <button type="submit" class="rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-400">Confirm complete</button>
                            </form>
                        @else
                            <span class="self-start rounded-full border border-[#3A3A3A] px-3 py-2 text-sm text-gray-400 sm:self-center">Assigned task · view only</span>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $tasks->links() }}</div>
        @endif
    </section>
@endsection
