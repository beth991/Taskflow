@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <section class="mx-auto max-w-xl">
        <div class="mb-8">
            <p class="text-sm font-medium text-blue-400">Get started</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Create your account</h1>
            <p class="mt-3 text-gray-400">Set up your TaskFlow account to keep team work organized.</p>
        </div>

        <form action="{{ route('register.store') }}" method="POST" class="space-y-5 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-6 sm:p-8">
            @csrf
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-gray-200">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" autocomplete="name" autofocus
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                @error('name') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-200">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                @error('email') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-200">Password</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                @error('password') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-200">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-[#1A1A1A]">Create account</button>
            <p class="text-center text-sm text-gray-400">Already have an account? <a class="font-medium text-blue-400 hover:text-blue-300" href="{{ route('login') }}">Log in</a></p>
        </form>
    </section>
@endsection
