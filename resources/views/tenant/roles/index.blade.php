@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')
@section('page-subtitle', 'Custom roles for this tenant')

@section('page-actions')
    @can('settings.roles.manage')
        <a href="{{ route('tenant.roles.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New role
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Name', 'Permissions', 'Users', '']" title="Roles">
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
                        <a href="{{ route('tenant.roles.edit', $role) }}" class="btn btn-ghost-primary btn-sm">Edit</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-secondary py-4">No roles yet.</td>
            </tr>
        @endforelse
    </x-table>
@endsection
