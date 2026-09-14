@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Tenant workspace users')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Manage access for this company.</p>
        @can('settings.users.manage')
            <x-button href="{{ route('tenant.users.create') }}">New user</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Email', 'Roles', '']">
        @forelse ($users as $row)
            <tr>
                <td class="fw-medium">{{ $row->name }}</td>
                <td class="text-secondary">{{ $row->email }}</td>
                <td class="text-secondary">{{ $row->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td class="text-end">
                    @can('settings.users.manage')
                        <x-button href="{{ route('tenant.users.edit', $row) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary">No users yet.</td></tr>
        @endforelse
    </x-table>
@endsection
