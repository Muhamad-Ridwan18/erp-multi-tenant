@extends('layouts.app')

@section('title', 'Replenishment')
@section('page-title', 'Replenishment rules')
@section('page-subtitle', 'Inventory')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-table :headers="['Product', 'On hand', 'Min', 'Max', 'Need', '']" title="Order points">
                @forelse ($orderPoints as $point)
                    @php
                        $onHand = $point->onHand();
                        $need = $point->suggestedQty();
                    @endphp
                    <tr class="{{ $point->isBelowMin() ? 'table-warning' : '' }}">
                        <td class="fw-medium">{{ $point->product?->name }}</td>
                        <td>{{ $onHand }}</td>
                        <td>{{ $point->min_qty }}</td>
                        <td>{{ $point->max_qty }}</td>
                        <td>{{ $need }}</td>
                        <td class="text-end">
                            @if ($need > 0)
                                @can('inventory.order_points.manage')
                                    <form method="POST" action="{{ route('tenant.order-points.replenish', $point) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-primary">Create MO</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No replenishment rules yet.</td></tr>
                @endforelse
            </x-table>
        </div>
        @can('inventory.order_points.manage')
            <div class="col-lg-4">
                <form method="POST" action="{{ route('tenant.order-points.store') }}">
                    @csrf
                    <x-card>
                        <h2 class="h3 mb-3">Add rule</h2>
                        <x-select label="Product" name="product_id" placeholder="Search…" required>
                            <option value="">Select…</option>
                            @foreach (\App\Models\Product::query()->where('is_active', true)->orderBy('name')->get() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </x-select>
                        <x-input label="Min qty" name="min_qty" type="number" min="0" value="{{ old('min_qty', 0) }}" required />
                        <x-input label="Max qty" name="max_qty" type="number" min="0" value="{{ old('max_qty', 0) }}" />
                        <x-button>Save rule</x-button>
                    </x-card>
                </form>
            </div>
        @endcan
    </div>
@endsection
