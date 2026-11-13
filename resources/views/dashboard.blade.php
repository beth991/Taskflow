@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="mx-auto max-w-4xl rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-7 sm:p-10">
        @if (auth()->user()->isAdmin())
            <p class="text-sm font-medium text-blue-400">Admin workspace</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Welcome, {{ auth()->user()->name }}</h1>
            <p class="mt-4 max-w-2xl leading-7 text-gray-400">Review and manage all team tasks, track their status, and assign teammates from your workspace.</p>
        @else
            <p class="text-sm font-medium text-blue-400">Member workspace</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Welcome, {{ auth()->user()->name }}</h1>
            <p class="mt-4 max-w-2xl leading-7 text-gray-400">Create tasks, keep your work moving, and assign teammates when you need to collaborate.</p>
        @endif

        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ route('tasks.index') }}" class="rounded-lg border border-[#3A3A3A] px-4 py-3 font-semibold text-gray-200 transition hover:border-gray-500 hover:bg-[#111111]">
                {{ auth()->user()->isAdmin() ? 'Open task workspace' : 'View my tasks' }}
            </a>
            <a href="{{ route('tasks.create') }}" class="rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400">Create a task</a>
        </div>
    </section>
@endsection
