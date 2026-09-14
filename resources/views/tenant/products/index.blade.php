@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Products</h1>
            <p class="mt-1 text-sm text-ink-500">Catalog and stock on hand.</p>
        </div>
        @can('inventory.products.create')
            <x-button href="{{ route('tenant.products.create') }}">New product</x-button>
        @endcan
    </div>

    <x-table :headers="['SKU', 'Name', 'Price', 'Stock', 'Status', '']">
        @forelse ($products as $product)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $product->sku }}</td>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $product->name }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $product->formattedPrice() }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $product->stock_qty }} {{ $product->unit }}</td>
                <td class="px-4 py-3">
                    <x-badge :tone="$product->is_active ? 'success' : 'neutral'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @can('inventory.products.update')
                        <x-button href="{{ route('tenant.products.edit', $product) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-ink-500">No products yet.</td></tr>
        @endforelse
    </x-table>
@endsection
