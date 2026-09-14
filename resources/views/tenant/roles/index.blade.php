@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')
@section('page-subtitle', 'Custom roles for this tenant')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Permissions are limited by the modules in your plan.</p>
        @can('settings.roles.manage')
            <x-button href="{{ route('tenant.roles.create') }}">New role</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Permissions', 'Users', '']">
        @forelse ($roles as $role)
            <tr>
                <td class="fw-medium">
                    {{ $role->name }}
                    @if ($role->is_system)
                        <x-badge class="ms-2">system</x-badge>
                    @endif
                </td>
                <td class="text-secondary">{{ $role->permissions_count }}</td>
                <td class="text-secondary">{{ $role->users_count }}</td>
                <td class="text-end">
                    @can('settings.roles.manage')
                        <x-button href="{{ route('tenant.roles.edit', $role) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-secondary">No roles yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection
