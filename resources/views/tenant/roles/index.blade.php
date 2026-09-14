@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Roles</h1>
            <p class="text-sm text-slate-500">Custom roles for this tenant. Permissions limited by plan modules.</p>
        </div>
        @can('settings.roles.manage')
            <a href="{{ route('tenant.roles.create') }}" class="rounded-lg bg-slate-900 text-white text-sm px-4 py-2">New role</a>
        @endcan
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Permissions</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium">
                            {{ $role->name }}
                            @if ($role->is_system)
                                <span class="text-xs text-slate-400">system</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('settings.roles.manage')
                                <a href="{{ route('tenant.roles.edit', $role) }}" class="text-blue-700 underline">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
