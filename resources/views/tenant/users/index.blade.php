@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Tenant workspace users')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Users</h1>
            <p class="mt-1 text-sm text-ink-500">Manage access for this company.</p>
        </div>
        @can('settings.users.manage')
            <x-button href="{{ route('tenant.users.create') }}">New user</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Email', 'Roles', '']">
        @forelse ($users as $row)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $row->name }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $row->email }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $row->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td class="px-4 py-3 text-right">
                    @can('settings.users.manage')
                        <x-button href="{{ route('tenant.users.edit', $row) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-ink-500">No users yet.</td></tr>
        @endforelse
    </x-table>
@endsection
