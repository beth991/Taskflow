@extends('layouts.app')

@section('title', 'Create task')

@section('content')
    <section class="mx-auto max-w-2xl">
        <a href="{{ route('tasks.index') }}" class="text-sm text-gray-400 transition hover:text-white">← Back to tasks</a>
        <div class="mb-8 mt-6">
            <p class="text-sm font-medium text-blue-400">New task</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Create a task</h1>
            <p class="mt-3 text-gray-400">Add a clear title so you can track the work.</p>
        </div>

        @include('tasks._form', [
            'task' => $task,
            'action' => route('tasks.store'),
            'method' => 'POST',
            'submitLabel' => 'Create task',
        ])
    </section>
@endsection
