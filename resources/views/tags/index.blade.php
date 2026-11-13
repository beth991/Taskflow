@extends('layouts.app')

@section('title', 'Manage tags')

@section('content')
    <section class="mx-auto max-w-5xl">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-blue-400">Admin tools</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Manage tags</h1>
                <p class="mt-3 text-gray-400">Create shared tags that the team can use on tasks.</p>
            </div>
            <a href="{{ route('admin.tags.create') }}" class="inline-flex justify-center rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400">New tag</a>
        </div>

        @if ($tags->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-[#3A3A3A] bg-[#1A1A1A] px-6 py-14 text-center">
                <h2 class="text-xl font-semibold">No tags yet</h2>
                <p class="mt-3 text-sm text-gray-400">Create a tag so Members can select it when creating or editing their tasks.</p>
                <a href="{{ route('admin.tags.create') }}" class="mt-6 inline-flex rounded-lg bg-blue-500 px-4 py-2.5 font-semibold text-white transition hover:bg-blue-400">Create the first tag</a>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A]">
                <ul class="divide-y divide-[#2A2A2A]">
                    @foreach ($tags as $tag)
                        <li class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="font-medium text-white">{{ $tag->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">Used on {{ $tag->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $tag->tasks_count) }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.tags.edit', $tag) }}" class="rounded-lg border border-[#3A3A3A] px-3 py-2 text-sm text-gray-200 transition hover:border-gray-500 hover:text-white">Edit</a>
                                <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" onsubmit="return confirm('Delete this tag? It will be removed from tasks.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-900 px-3 py-2 text-sm text-red-300 transition hover:border-red-500 hover:bg-red-950/50">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="mt-6">{{ $tags->links() }}</div>
        @endif
    </section>
@endsection
