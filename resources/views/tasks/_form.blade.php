<form action="{{ $action }}" method="POST" class="space-y-5 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-6 sm:p-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="sync_tags" value="1">

    <div>
        <label for="title" class="mb-2 block text-sm font-medium text-gray-200">Task title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}" required maxlength="100" autofocus
            class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
        <p class="mt-2 text-sm text-gray-500">Required · Maximum 100 characters</p>
        @error('title') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="notes" class="mb-2 block text-sm font-medium text-gray-200">Task notes <span class="font-normal text-gray-500">(optional)</span></label>
        <textarea id="notes" name="notes" rows="4" maxlength="5000"
            class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">{{ old('notes', $task->notes) }}</textarea>
        <p class="mt-2 text-sm text-gray-500">Visible to the task creator and its assignees.</p>
        @error('notes') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
    </div>

    <fieldset>
        <legend class="mb-2 block text-sm font-medium text-gray-200">Tags <span class="font-normal text-gray-500">(optional)</span></legend>
        @if ($availableTags->isEmpty())
            <p class="rounded-lg border border-dashed border-[#3A3A3A] px-4 py-4 text-sm text-gray-500">No tags are available yet. An Admin can add tags from Manage tags.</p>
        @else
            @php
                $selectedTagIds = collect(old('tag_ids', $task->exists ? $task->tags->modelKeys() : []))
                    ->map(fn ($id) => (string) $id)
                    ->all();
            @endphp
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($availableTags as $tag)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-[#2A2A2A] bg-[#111111] px-4 py-3 text-sm text-gray-200 hover:border-blue-500">
                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array((string) $tag->id, $selectedTagIds, true))
                            class="rounded border-[#3A3A3A] bg-[#0A0A0A] text-blue-500 focus:ring-blue-500">
                        <span>{{ $tag->name }}</span>
                    </label>
                @endforeach
            </div>
        @endif
        @error('tag_ids') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
        @error('tag_ids.*') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
    </fieldset>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('tasks.index') }}" class="rounded-lg border border-[#3A3A3A] px-4 py-3 text-center font-medium text-gray-300 transition hover:border-gray-500 hover:text-white">Cancel</a>
        <button type="submit" class="rounded-lg bg-blue-500 px-4 py-3 font-semibold text-white transition hover:bg-blue-400">{{ $submitLabel }}</button>
    </div>
</form>
