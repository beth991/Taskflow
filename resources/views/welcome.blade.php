@extends('layouts.app')

@section('title', 'Team task management')

@section('content')
    <section class="mx-auto grid max-w-6xl gap-10 py-8 md:grid-cols-[1.2fr_0.8fr] md:items-center md:py-20">
        <div>
            <p class="inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1.5 text-sm text-blue-300">
                <span class="size-2 rounded-full bg-blue-400"></span>
                Team task management
            </p>
            <h1 class="mt-6 max-w-2xl text-4xl font-semibold tracking-tight sm:text-6xl">Clear work. <span class="text-blue-400">Better flow.</span></h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-gray-400">TaskFlow gives teams a focused place to organize work and keep everyone moving together.</p>
            @guest
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-500 px-5 py-3 font-semibold text-white transition hover:bg-blue-400">Create your account</a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-[#2A2A2A] px-5 py-3 font-semibold text-gray-200 transition hover:bg-[#1A1A1A]">Log in</a>
                </div>
            @else
                <a href="{{ route('dashboard') }}" class="mt-8 inline-flex rounded-lg bg-blue-500 px-5 py-3 font-semibold text-white transition hover:bg-blue-400">Go to your workspace</a>
            @endguest
        </div>

        <div class="rounded-2xl border border-[#2A2A2A] bg-[#111111] p-6 sm:p-8">
            <p class="text-sm font-medium text-gray-400">Built for focused teamwork</p>
            <div class="mt-6 space-y-4">
                <div class="rounded-xl border border-[#2A2A2A] bg-[#1A1A1A] p-4">
                    <div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-blue-500/15 text-blue-300">01</span><p class="font-medium">Create your account</p></div>
                    <p class="mt-3 text-sm leading-6 text-gray-400">Sign in securely to your TaskFlow workspace.</p>
                </div>
                <div class="rounded-xl border border-[#2A2A2A] bg-[#1A1A1A] p-4">
                    <div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-blue-500/15 text-blue-300">02</span><p class="font-medium">Organize team tasks</p></div>
                    <p class="mt-3 text-sm leading-6 text-gray-400">Task management tools are being delivered in the next milestones.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
