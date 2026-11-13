@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="mx-auto max-w-3xl rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-7 sm:p-10">
        <p class="text-sm font-medium text-blue-400">Your workspace</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-4 max-w-2xl leading-7 text-gray-400">Your account is signed in and ready. Task lists and team tools will be added in the upcoming TaskFlow milestones.</p>
    </section>
@endsection
