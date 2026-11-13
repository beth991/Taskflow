<form action="{{ $action }}" method="POST" class="space-y-5 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-6 sm:p-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="mb-2 block text-sm font-medium text-gray-200">Task title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}" required maxlength="100" autofocus
            class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
        <p class="mt-2 text-sm text-gray-500">Required · Maximum 100 characters</p>
        @error('title') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('tasks.index') }}" class="rounded-lg border border-[#3A3A3A] px-4 py-3 text-center font-medium text-gray-300 transition hover:border-gray-500 hover:text-white">Cancel</a>
        <button type="submit" class="rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400">{{ $submitLabel }}</button>
    </div>
</form>
