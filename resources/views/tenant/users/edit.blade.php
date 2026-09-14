@extends('layouts.app')

@section('title', 'Edit user')
@section('page-title', 'Edit user')
@section('page-subtitle', $userModel->email)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <h1 class="text-2xl font-semibold text-ink-950">Edit user</h1>
        @if ($userModel->id !== auth()->id())
            <form method="POST" action="{{ route('tenant.users.destroy', $userModel) }}" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('tenant.users.update', $userModel) }}" class="max-w-xl space-y-6">
        @csrf
        @method('PUT')
        <x-card class="space-y-4">
            <x-input label="Name" name="name" value="{{ old('name', $userModel->name) }}" required />
            <x-input label="Email" name="email" type="email" value="{{ old('email', $userModel->email) }}" required />
            <x-input label="New password" name="password" type="password" help="Leave blank to keep current password." />
        </x-card>
        <x-card>
            <h2 class="mb-3 font-medium text-ink-900">Roles</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $selectedRoles), false))>
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>
        </x-card>
        <div class="flex gap-3">
            <x-button>Save</x-button>
            <x-button href="{{ route('tenant.users.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
