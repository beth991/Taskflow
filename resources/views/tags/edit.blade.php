@extends('layouts.app')

@section('title', 'Edit tag')

@section('content')
    <section class="mx-auto max-w-2xl">
        <a href="{{ route('admin.tags.index') }}" class="text-sm text-gray-400 transition hover:text-white">← Back to tags</a>
        <div class="mb-8 mt-6">
            <p class="text-sm font-medium text-blue-400">Admin tools</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Edit tag</h1>
        </div>

        @include('tags._form', [
            'tag' => $tag,
            'action' => route('admin.tags.update', $tag),
            'method' => 'PUT',
            'submitLabel' => 'Save changes',
        ])
    </section>
@endsection
