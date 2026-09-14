@extends('layouts.app')

@section('title', 'Edit customer')
@section('page-title', 'Edit customer')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <h1 class="text-2xl font-semibold text-ink-950">Edit customer</h1>
        @can('partners.customers.delete')
            <form method="POST" action="{{ route('tenant.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <form method="POST" action="{{ route('tenant.customers.update', $customer) }}" class="max-w-xl space-y-6">
        @csrf
        @method('PUT')
        <x-card class="space-y-4">
            <x-input label="Name" name="name" value="{{ old('name', $customer->name) }}" required />
            <x-input label="Email" name="email" type="email" value="{{ old('email', $customer->email) }}" />
            <x-input label="Phone" name="phone" value="{{ old('phone', $customer->phone) }}" />
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800">Address</label>
                <textarea name="address" rows="3" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('notes', $customer->notes) }}</textarea>
            </div>
        </x-card>
        <div class="flex gap-3">
            <x-button>Save</x-button>
            <x-button href="{{ route('tenant.customers.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
