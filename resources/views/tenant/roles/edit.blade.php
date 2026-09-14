@extends('layouts.app')

@section('title', 'Edit role')
@section('page-title', 'Edit role')
@section('page-subtitle', $role->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Edit role: {{ $role->name }}</h1>
            <p class="mt-1 text-sm text-ink-500">Only permissions from modules in the tenant plan are listed.</p>
        </div>
        @unless ($role->is_system)
            <form method="POST" action="{{ route('tenant.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete role</x-button>
            </form>
        @endunless
    </div>

    <form method="POST" action="{{ route('tenant.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card class="max-w-lg">
            <x-input
                label="Role name"
                name="name"
                value="{{ old('name', $role->name) }}"
                :disabled="$role->is_system"
                help="{{ $role->is_system ? 'System role names cannot be changed.' : null }}"
            />
        </x-card>

        <x-card>
            <h2 class="mb-4 font-medium text-ink-900">Assigned users</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @forelse ($tenantUsers as $tenantUser)
                    <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm hover:bg-ink-50">
                        <input
                            type="checkbox"
                            name="users[]"
                            value="{{ $tenantUser->id }}"
                            @checked(in_array($tenantUser->id, old('users', $selectedUsers), true))
                        >
                        <span>
                            <span class="font-medium text-ink-900">{{ $tenantUser->name }}</span>
                            <span class="block text-xs text-ink-500">{{ $tenantUser->email }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-ink-500">No users in this tenant.</p>
                @endforelse
            </div>
        </x-card>

        @foreach ($permissionsByModule as $module => $permissions)
            <x-card>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="font-medium capitalize text-ink-900">{{ $module }}</h2>
                    <button
                        type="button"
                        class="text-xs font-medium text-ink-600 hover:text-ink-900"
                        data-select-module="{{ $module }}"
                        data-checked="true"
                    >
                        Select all
                    </button>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($permissions as $permission)
                        <label class="flex items-start gap-2 rounded-lg border border-line px-3 py-2 text-sm hover:bg-ink-50">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->id }}"
                                data-module="{{ $module }}"
                                @checked(in_array($permission->id, old('permissions', $selected), true))
                                class="mt-0.5"
                            >
                            <span>
                                <span class="font-mono text-xs text-ink-800">{{ $permission->name }}</span>
                                <span class="block text-xs text-ink-500">{{ $permission->description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-card>
        @endforeach

        <div class="flex items-center gap-3">
            <x-button>Save role</x-button>
            <x-button href="{{ route('tenant.roles.index') }}" variant="ghost">Back</x-button>
        </div>
    </form>
@endsection
