@extends('layouts.app')

@section('title', 'Create role')
@section('page-title', 'Create role')
@section('page-subtitle', 'Add a custom role for this tenant')

@section('content')
    <div class="max-w-lg">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">Create role</h1>

        <x-card>
            <form method="POST" action="{{ route('tenant.roles.store') }}" class="space-y-4">
                @csrf
                <x-input label="Role name" name="name" value="{{ old('name') }}" required autofocus />
                <div class="flex items-center gap-3">
                    <x-button>Create</x-button>
                    <x-button href="{{ route('tenant.roles.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </x-card>
    </div>
@endsection
