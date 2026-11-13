@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <section class="mx-auto max-w-xl">
        <div class="mb-8">
            <p class="text-sm font-medium text-blue-400">Welcome back</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Log in to TaskFlow</h1>
            <p class="mt-3 text-gray-400">Sign in to continue to your workspace.</p>
        </div>

        <form action="{{ route('login.store') }}" method="POST" class="space-y-5 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-6 sm:p-8">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-200">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" autofocus
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                @error('email') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-200">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                @error('password') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-3 text-sm text-gray-300">
                <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 rounded border-[#3A3A3A] bg-[#111111] accent-blue-500 focus:ring-blue-500">
                Remember me
            </label>

            <button type="submit" class="w-full rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#1A1A1A]">Log in</button>
            <p class="text-center text-sm text-gray-400">New to TaskFlow? <a class="font-medium text-blue-400 hover:text-blue-300" href="{{ route('register') }}">Create an account</a></p>
        </form>
    </section>
@endsection
