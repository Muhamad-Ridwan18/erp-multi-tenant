@extends('layouts.app')

@section('title', 'Create customer')
@section('page-title', 'Create customer')
@section('page-subtitle', 'Sales')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">Create customer</h1>
    </div>

    <form method="POST" action="{{ route('tenant.customers.store') }}" class="space-y-6">
        @csrf
        <div class="grid gap-6 lg:grid-cols-3">
            <x-card class="space-y-4 lg:col-span-2">
                <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus class="text-lg font-medium" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input label="Email" name="email" type="email" value="{{ old('email') }}" />
                    <x-input label="Phone" name="phone" value="{{ old('phone') }}" />
                </div>
                <x-textarea label="Address" name="address" rows="3">{{ old('address') }}</x-textarea>
            </x-card>
            <x-card class="space-y-4">
                <x-textarea label="Notes" name="notes" rows="5">{{ old('notes') }}</x-textarea>
            </x-card>
        </div>
        <div class="flex gap-3">
            <x-button>Create</x-button>
            <x-button href="{{ route('tenant.customers.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
