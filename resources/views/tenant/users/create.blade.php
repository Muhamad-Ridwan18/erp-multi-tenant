@extends('layouts.app')

@section('title', 'Create user')
@section('page-title', 'Create user')

@section('content')
    <div class="max-w-xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">Create user</h1>
        <form method="POST" action="{{ route('tenant.users.store') }}" class="space-y-6">
            @csrf
            <x-card class="space-y-4">
                <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus />
                <x-input label="Email" name="email" type="email" value="{{ old('email') }}" required />
                <x-input label="Password" name="password" type="password" required help="Minimum 8 characters." />
            </x-card>
            <x-card>
                <h2 class="mb-3 font-medium text-ink-900">Roles</h2>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', []), false))>
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
            </x-card>
            <div class="flex gap-3">
                <x-button>Create</x-button>
                <x-button href="{{ route('tenant.users.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>
@endsection
