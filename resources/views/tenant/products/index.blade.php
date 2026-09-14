@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="mb-0 text-secondary">Catalog and stock on hand.</p>
        @can('inventory.products.create')
            <x-button href="{{ route('tenant.products.create') }}">New product</x-button>
        @endcan
    </div>

    <x-table :headers="['SKU', 'Name', 'Price', 'Stock', 'Status', '']">
        @forelse ($products as $product)
            <tr>
                <td class="font-monospace small">{{ $product->sku }}</td>
                <td class="fw-medium">{{ $product->name }}</td>
                <td>{{ $product->formattedPrice() }}</td>
                <td>{{ $product->stock_qty }} {{ $product->unit }}</td>
                <td>
                    <x-badge :tone="$product->is_active ? 'success' : 'neutral'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-badge>
                </td>
                <td class="text-end">
                    @can('inventory.products.update')
                        <x-button href="{{ route('tenant.products.edit', $product) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary">No products yet.</td></tr>
        @endforelse
    </x-table>
@endsection
