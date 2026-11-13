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
    </section>
@endsection
