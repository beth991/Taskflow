@extends('layouts.app')

@section('title', 'Edit task')

@section('content')
    <section class="mx-auto max-w-2xl">
        <a href="{{ route('tasks.index') }}" class="text-sm text-gray-400 transition hover:text-white">← Back to tasks</a>
        <div class="mb-8 mt-6">
            <p class="text-sm font-medium text-blue-400">Update task</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Edit task</h1>
        </div>

        @include('tasks._form', [
            'task' => $task,
            'action' => route('tasks.update', $task),
            'method' => 'PUT',
            'submitLabel' => 'Save changes',
        ])

        <section class="mt-6 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-6 sm:p-8" aria-labelledby="assignees-heading">
            <div>
                <h2 id="assignees-heading" class="text-xl font-semibold">Manage assignees</h2>
                <p class="mt-2 text-sm text-gray-400">Add one or more users to this task, or remove current assignees.</p>
            </div>

            <form action="{{ route('tasks.assignees.store', $task) }}" method="POST" class="mt-5 space-y-3">
                @csrf
                <label for="user_ids" class="block text-sm font-medium text-gray-200">Available users</label>
                <select id="user_ids" name="user_ids[]" multiple size="5" aria-describedby="user_ids-help"
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                    @foreach ($availableUsers as $user)
                        <option value="{{ $user->id }}" @selected(in_array((string) $user->id, (array) old('user_ids', []), true))>{{ $user->name }}</option>
                    @endforeach
                </select>
                <p id="user_ids-help" class="text-sm text-gray-500">Use Ctrl (Windows) or Command (Mac) to select multiple users.</p>
                @error('user_ids') <p class="text-sm text-red-300">{{ $message }}</p> @enderror
                @error('user_ids.*') <p class="text-sm text-red-300">{{ $message }}</p> @enderror
                <button type="submit" @disabled($availableUsers->isEmpty()) class="rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-50">Add selected users</button>
            </form>

            <div class="mt-7 space-y-3">
                <h3 class="text-sm font-semibold text-gray-200">Current assignees</h3>
                @forelse ($task->assignees as $assignee)
                    <div class="flex flex-col gap-3 rounded-xl border border-[#2A2A2A] bg-[#111111] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-white">{{ $assignee->name }}</p>
                        </div>
                        <form action="{{ route('tasks.assignees.destroy', $task) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $assignee->id }}">
                            <button type="submit" class="rounded-lg border border-red-900 px-3 py-2 text-sm text-red-300 transition hover:border-red-500 hover:bg-red-950/50">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-[#3A3A3A] px-4 py-5 text-sm text-gray-500">No assignees yet.</p>
                @endforelse
            </div>
        </section>
    </section>
@endsection
