<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TaskFlow') · TaskFlow</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#0A0A0A] text-white antialiased">
    <div class="min-h-screen">
        <header class="border-b border-[#2A2A2A] bg-[#111111]">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-8" aria-label="Main navigation">
                <a class="text-lg font-semibold tracking-tight" href="{{ route('home') }}">Task<span class="text-blue-500">Flow</span></a>
                <div class="flex flex-wrap items-center justify-end gap-3 text-sm">
                    @auth
                        <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-2.5 py-1 text-xs font-medium text-blue-300">
                            {{ auth()->user()->isAdmin() ? 'Admin' : 'Member' }}
                        </span>
                        <a class="text-gray-300 transition hover:text-white" href="{{ route('dashboard') }}">
                            {{ auth()->user()->isAdmin() ? 'Admin dashboard' : 'Member dashboard' }}
                        </a>
                        <a class="text-gray-300 transition hover:text-white" href="{{ route('tasks.index') }}">
                            {{ auth()->user()->isAdmin() ? 'My task workspace' : 'My tasks' }}
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a class="text-gray-300 transition hover:text-white" href="{{ route('admin.users.index') }}">Manage users</a>
                            <a class="text-gray-300 transition hover:text-white" href="{{ route('admin.tags.index') }}">Manage tags</a>
                        @endif
                        <a class="rounded-lg bg-blue-500 px-3 py-2 font-medium text-white transition hover:bg-blue-400" href="{{ route('tasks.create') }}">New task</a>
                        <span class="hidden text-gray-500 sm:inline">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="rounded-lg border border-[#2A2A2A] px-3 py-2 text-gray-200 transition hover:border-gray-500 hover:bg-[#1A1A1A]" type="submit">Log out</button>
                        </form>
                    @else
                        <a class="text-gray-300 transition hover:text-white" href="{{ route('login') }}">Log in</a>
                        <a class="rounded-lg bg-blue-500 px-3 py-2 font-medium text-white transition hover:bg-blue-400" href="{{ route('register') }}">Create account</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
            @if (session('status'))
                <div class="mx-auto mb-6 max-w-xl rounded-xl border border-emerald-900 bg-emerald-950/60 px-4 py-3 text-sm text-emerald-200" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mx-auto mb-6 max-w-xl rounded-xl border border-red-900 bg-red-950/60 px-4 py-3 text-sm text-red-200" role="alert">
                    <p class="font-medium">Please check the information below.</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
