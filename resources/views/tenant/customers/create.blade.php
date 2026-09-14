@extends('layouts.app')

@section('title', 'Create customer')
@section('page-title', 'Create customer')

@section('content')
    <div class="max-w-xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">Create customer</h1>
        <form method="POST" action="{{ route('tenant.customers.store') }}" class="space-y-6">
            @csrf
            <x-card class="space-y-4">
                <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus />
                <x-input label="Email" name="email" type="email" value="{{ old('email') }}" />
                <x-input label="Phone" name="phone" value="{{ old('phone') }}" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Address</label>
                    <textarea name="address" rows="3" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('address') }}</textarea>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                </div>
            </x-card>
            <div class="flex gap-3">
                <x-button>Create</x-button>
                <x-button href="{{ route('tenant.customers.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>
@endsection
