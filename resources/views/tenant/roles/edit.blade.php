@extends('layouts.app')

@section('title', 'Edit role')

@section('content')
    <h1 class="text-2xl font-semibold mb-1">Edit role: {{ $role->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">Only permissions from modules included in the tenant plan are listed.</p>

    <form method="POST" action="{{ route('tenant.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-lg">
            <label class="block text-sm mb-1">Role name</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" @disabled($role->is_system)
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-50">
        </div>

        @foreach ($permissionsByModule as $module => $permissions)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-medium mb-3 capitalize">{{ $module }}</h2>
                <div class="grid sm:grid-cols-2 gap-2">
                    @foreach ($permissions as $permission)
                        <label class="flex items-start gap-2 text-sm rounded-lg border border-slate-100 px-3 py-2 hover:bg-slate-50">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                   @checked(in_array($permission->id, old('permissions', $selected), true))
                                   class="mt-0.5">
                            <span>
                                <span class="font-mono text-xs">{{ $permission->name }}</span>
                                <span class="block text-slate-500 text-xs">{{ $permission->description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button class="rounded-lg bg-slate-900 text-white text-sm px-4 py-2">Save permissions</button>
        <a href="{{ route('tenant.roles.index') }}" class="text-sm text-slate-600 underline ml-3">Back</a>
    </form>
@endsection
