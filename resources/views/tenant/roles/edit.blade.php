@extends('layouts.app')

@section('title', 'Edit role')
@section('page-title', 'Edit role')
@section('page-subtitle', $role->name)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <p class="mb-0 text-secondary">Only permissions from modules in the tenant plan are listed.</p>
        @unless ($role->is_system)
            <form method="POST" action="{{ route('tenant.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete role</x-button>
            </form>
        @endunless
    </div>

    <form method="POST" action="{{ route('tenant.roles.update', $role) }}" class="vstack gap-3">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-6">
                <x-card>
                    <x-input
                        label="Role name"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        :disabled="$role->is_system"
                        help="{{ $role->is_system ? 'System role names cannot be changed.' : null }}"
                    />
                </x-card>
            </div>
        </div>

        <x-card>
            <h2 class="h3 mb-3">Assigned users</h2>
            <div class="row g-2">
                @forelse ($tenantUsers as $tenantUser)
                    <div class="col-sm-6">
                        <label class="form-check border rounded p-2 mb-0">
                            <input
                                type="checkbox"
                                name="users[]"
                                value="{{ $tenantUser->id }}"
                                class="form-check-input"
                                @checked(in_array($tenantUser->id, old('users', $selectedUsers), true))
                            >
                            <span class="form-check-label">
                                <span class="fw-medium">{{ $tenantUser->name }}</span>
                                <span class="d-block small text-secondary">{{ $tenantUser->email }}</span>
                            </span>
                        </label>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="mb-0 text-secondary">No users in this tenant.</p>
                    </div>
                @endforelse
            </div>
        </x-card>

        @foreach ($permissionsByModule as $module => $permissions)
            <x-card>
                <div class="mb-3 d-flex align-items-center justify-content-between gap-2">
                    <h2 class="h3 mb-0 text-capitalize">{{ $module }}</h2>
                    <button
                        type="button"
                        class="btn btn-link btn-sm"
                        data-select-module="{{ $module }}"
                        data-checked="true"
                    >
                        Select all
                    </button>
                </div>
                <div class="row g-2">
                    @foreach ($permissions as $permission)
                        <div class="col-sm-6">
                            <label class="form-check border rounded p-2 mb-0">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    class="form-check-input"
                                    data-module="{{ $module }}"
                                    @checked(in_array($permission->id, old('permissions', $selected), true))
                                >
                                <span class="form-check-label">
                                    <span class="font-monospace small">{{ $permission->name }}</span>
                                    <span class="d-block small text-secondary">{{ $permission->description }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endforeach

        <div class="d-flex align-items-center gap-2">
            <x-button>Save role</x-button>
            <x-button href="{{ route('tenant.roles.index') }}" variant="ghost">Back</x-button>
        </div>
    </form>
@endsection
