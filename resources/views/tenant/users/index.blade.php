@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Tenant workspace users')

@section('page-actions')
    @can('settings.users.manage')
        <a href="{{ route('tenant.users.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New user
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Name', 'Email', 'Roles', '']" title="Users">
        @forelse ($users as $row)
            <tr>
                <td class="fw-medium">{{ $row->name }}</td>
                <td class="text-secondary">{{ $row->email }}</td>
                <td class="text-secondary">{{ $row->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td class="text-end">
                    @can('settings.users.manage')
                        <a href="{{ route('tenant.users.edit', $row) }}" class="btn btn-ghost-primary btn-sm">Edit</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary py-4">No users yet.</td></tr>
        @endforelse
    </x-table>
@endsection
