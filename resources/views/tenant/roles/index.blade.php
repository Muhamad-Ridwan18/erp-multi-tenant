@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')
@section('page-subtitle', 'Custom roles for this tenant')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Roles</h1>
            <p class="mt-1 text-sm text-ink-500">Permissions are limited by the modules in your plan.</p>
        </div>
        @can('settings.roles.manage')
            <x-button href="{{ route('tenant.roles.create') }}">New role</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Permissions', 'Users', '']">
        @forelse ($roles as $role)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">
                    {{ $role->name }}
                    @if ($role->is_system)
                        <x-badge class="ml-2">system</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-ink-600">{{ $role->permissions_count }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $role->users_count }}</td>
                <td class="px-4 py-3 text-right">
                    @can('settings.roles.manage')
                        <x-button href="{{ route('tenant.roles.edit', $role) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-sm text-ink-500">No roles yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection
