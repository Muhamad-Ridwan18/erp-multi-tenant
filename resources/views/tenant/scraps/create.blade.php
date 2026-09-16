@extends('layouts.app')

@section('title', 'Create scrap')
@section('page-title', 'Create scrap')
@section('page-subtitle', 'Inventory')

@section('content')
    <form method="POST" action="{{ route('tenant.scraps.store') }}" class="vstack gap-3">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <x-card>
                    <x-select label="Source location" name="location_id">
                        <option value="">Main stock</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                {{ $location->name }} ({{ $location->code }})
                            </option>
                        @endforeach
                    </x-select>
                    <x-input label="Notes" name="notes" value="{{ old('notes') }}" />
                </x-card>

                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title">Products to scrap</h3></div>
                    <div class="table-responsive">
                        <table class="table card-table mb-0">
                            <thead><tr><th>Product</th><th style="width:8rem">Qty</th></tr></thead>
                            <tbody>
                                @php $items = old('items', [['product_id' => '', 'quantity' => 1]]); @endphp
                                @foreach ($items as $i => $item)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $i }}][product_id]" class="form-select" required data-tom-select>
                                                <option value="">Select…</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
                                                        {{ $product->name }} (stock {{ $product->stock_qty }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][quantity]" class="form-control" min="1" value="{{ $item['quantity'] ?? 1 }}" required>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <x-card>
                    <x-button class="w-100">Validate scrap</x-button>
                    <a href="{{ route('tenant.scraps.index') }}" class="btn btn-ghost-secondary w-100 mt-2">Cancel</a>
                </x-card>
            </div>
        </div>
    </form>
@endsection
