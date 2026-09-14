@extends('layouts.app')

@section('title', 'New purchase order')
@section('page-title', 'New purchase order')
@section('page-subtitle', 'Procurement')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-ink-950">New purchase order</h1>
        <p class="mt-1 text-sm text-ink-500">Vendor header + lines. Price autofills from product; editable per line.</p>
    </div>

    @if ($vendors->isEmpty() || $products->isEmpty())
        <x-alert type="error" class="mb-4">
            Add at least one vendor and one active product before creating a purchase order.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('tenant.purchases.store') }}" class="space-y-6">
        @csrf

        <x-card>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-select label="Vendor" name="vendor_id" required placeholder="Search vendor…">
                    <option value="">Select vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                            {{ $vendor->name }}@if ($vendor->email) — {{ $vendor->email }}@endif
                        </option>
                    @endforeach
                </x-select>
                <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
            </div>
        </x-card>

        <x-document-lines :products="$products" />

        <div class="flex flex-wrap gap-3">
            <x-button :disabled="$vendors->isEmpty() || $products->isEmpty()">Create draft</x-button>
            <x-button href="{{ route('tenant.purchases.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
