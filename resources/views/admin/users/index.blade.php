@extends('layouts.app')

@section('title', 'Manage users')

@section('content')
    <section class="mx-auto max-w-5xl">
        <div>
            <p class="text-sm font-medium text-blue-400">Admin tools</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Manage users</h1>
            <p class="mt-3 text-gray-400">View team accounts and update roles. Your own role cannot be changed here, and at least one Admin must remain active.</p>
        </div>

        @if ($users->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-[#3A3A3A] bg-[#1A1A1A] px-6 py-14 text-center">
                <h2 class="text-xl font-semibold">No users found</h2>
                <p class="mt-3 text-sm text-gray-400">There are no user accounts to manage.</p>
            </div>
        @else
            <div class="mt-8 space-y-3">
                @foreach ($users as $user)
                    <article class="flex flex-col gap-5 rounded-2xl border border-[#2A2A2A] bg-[#1A1A1A] p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="min-w-0">
                            <h2 class="break-words font-medium text-white">{{ $user->name }}@if ((int) $user->getKey() === (int) auth()->id()) <span class="ml-2 text-xs text-gray-500">You</span> @endif</h2>
                            <p class="mt-1 break-all text-sm text-gray-400">{{ $user->email }}</p>
                            <span @class([
                                'mt-3 inline-flex rounded-full border px-2.5 py-1 text-xs font-medium',
                                'border-blue-500/30 bg-blue-500/10 text-blue-300' => $user->isAdmin(),
                                'border-[#3A3A3A] text-gray-300' => $user->isMember(),
                            ])>{{ $user->role->value === 'admin' ? 'Admin' : 'Member' }}</span>
                        </div>

                        @if ((int) $user->getKey() === (int) auth()->id())
                            <p class="text-sm text-gray-500">Your own role is protected.</p>
                        @else
                            <form action="{{ route('admin.users.role.update', $user) }}" method="POST" class="flex flex-col gap-2 sm:min-w-64 sm:flex-row sm:items-end">
                                @csrf
                                @method('PATCH')
                                <div class="flex-1">
                                    <label for="role-{{ $user->id }}" class="mb-2 block text-xs font-medium text-gray-400">Change role</label>
                                    <select id="role-{{ $user->id }}" name="role" required
                                        class="w-full rounded-lg border border-[#3A3A3A] bg-[#111111] px-3 py-2.5 text-sm text-white outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ ucfirst($role->value) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="rounded-lg bg-blue-500 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-400">Save role</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
