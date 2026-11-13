@extends('layouts.app')

@section('title', $task->title)

@section('content')
    <section class="mx-auto max-w-3xl">
        <a href="{{ route('tasks.index') }}" class="text-sm text-blue-400 hover:text-blue-300">&larr; Back to tasks</a>

        <article class="mt-5 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-400">Task details</p>
                    <h1 class="mt-2 break-words text-3xl font-semibold tracking-tight">{{ $task->title }}</h1>
                </div>
                <span @class([
                    'rounded-full px-3 py-1.5 text-sm font-medium',
                    'bg-emerald-500/10 text-emerald-300' => $task->is_completed,
                    'bg-amber-500/10 text-amber-300' => ! $task->is_completed,
                ])>
                    {{ $task->is_completed ? 'Completed' : 'Incomplete' }}
                </span>
            </div>

            <dl class="mt-7 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">Created by</dt>
                    <dd class="mt-1 text-gray-200">{{ $task->creator->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="mt-1 text-gray-200"><time datetime="{{ $task->created_at->toIso8601String() }}">{{ $task->created_at->format('M j, Y · H:i') }}</time></dd>
                </div>
                @if (auth()->user()->isAdmin() && $task->is_completed && $task->completedBy)
                    <div>
                        <dt class="text-sm text-gray-500">Completed by</dt>
                        <dd class="mt-1 font-medium text-emerald-300">{{ $task->completedBy->name }}</dd>
                    </div>
                @endif
            </dl>

            @if (filled($task->notes))
                <div class="mt-7 border-t border-[#2A2A2A] pt-6">
                    <h2 class="text-sm font-medium text-gray-400">Notes</h2>
                    <p class="mt-2 whitespace-pre-line break-words leading-7 text-gray-200">{{ $task->notes }}</p>
                </div>
            @endif

            <div class="mt-7 border-t border-[#2A2A2A] pt-6">
                <h2 class="text-sm font-medium text-gray-400">Tags</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($task->tags as $tag)
                        <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1.5 text-sm text-blue-200">{{ $tag->name }}</span>
                    @empty
                        <span class="text-sm text-gray-500">No tags</span>
                    @endforelse
                </div>
            </div>

            <div class="mt-7 border-t border-[#2A2A2A] pt-6">
                <h2 class="text-sm font-medium text-gray-400">Assignees</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($task->assignees as $assignee)
                        <span class="rounded-full border border-[#3A3A3A] px-3 py-1.5 text-sm text-gray-200">
                            {{ $assignee->name }}
                            @if (auth()->user()->isAdmin() && (int) $assignee->getKey() === (int) $task->completed_by)
                                <span class="ml-1 font-medium text-emerald-300">Completed this task</span>
                            @endif
                        </span>
                    @empty
                        <span class="text-sm text-gray-500">No assignees</span>
                    @endforelse
                </div>
            </div>

            @if ((int) $task->user_id === (int) auth()->id())
                <div class="mt-7 flex flex-wrap gap-3 border-t border-[#2A2A2A] pt-6">
                    <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg border border-[#3A3A3A] px-4 py-2 text-sm text-gray-200 transition hover:border-gray-500 hover:text-white">Edit task</a>
                    @if ($task->is_completed)
                        <form action="{{ route('tasks.status.update', $task) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_completed" value="0">
                            <button type="submit" class="rounded-lg border border-[#3A3A3A] px-4 py-2 text-sm text-gray-200 transition hover:border-blue-500 hover:text-white">Mark incomplete</button>
                        </form>
                    @endif
                </div>
            @endif
        </article>
    </section>
@endsection
