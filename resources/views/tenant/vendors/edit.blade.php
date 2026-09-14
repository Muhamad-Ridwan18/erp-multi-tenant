@extends('layouts.app')

@section('title', 'Edit vendor')
@section('page-title', 'Edit vendor')
@section('page-subtitle', $vendor->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <h1 class="text-2xl font-semibold text-ink-950">{{ $vendor->name }}</h1>
        @can('procurement.vendors.delete')
            <form method="POST" action="{{ route('tenant.vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this vendor?')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <div class="max-w-xl">
        <form method="POST" action="{{ route('tenant.vendors.update', $vendor) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-card class="space-y-4">
                <x-input label="Name" name="name" value="{{ old('name', $vendor->name) }}" required autofocus />
                <x-input label="Email" name="email" type="email" value="{{ old('email', $vendor->email) }}" />
                <x-input label="Phone" name="phone" value="{{ old('phone', $vendor->phone) }}" />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Address</label>
                    <textarea name="address" rows="3" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('address', $vendor->address) }}</textarea>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('notes', $vendor->notes) }}</textarea>
                </div>
            </x-card>
            <div class="flex gap-3">
                <x-button>Save</x-button>
                <x-button href="{{ route('tenant.vendors.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>
@endsection
