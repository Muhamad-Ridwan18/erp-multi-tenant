@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Inventory')

@section('page-actions')
    @can('inventory.products.create')
        <a href="{{ route('tenant.products.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New product
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['SKU', 'Name', 'Price', 'Stock', 'Status', '']" title="Products">
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
                        <a href="{{ route('tenant.products.edit', $product) }}" class="btn btn-ghost-primary btn-sm">Edit</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">No products yet.</td></tr>
        @endforelse
    </x-table>
@endsection
