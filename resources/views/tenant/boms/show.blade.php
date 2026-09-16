@extends('layouts.app')

@section('title', $bom->code ?: 'BOM #'.$bom->id)
@section('page-title', 'Bill of materials')
@section('page-subtitle', $bom->code ?: '#'.$bom->id)

@section('content')
    <div class="mb-3 d-flex flex-wrap justify-content-between gap-2">
        <div>
            <div class="fw-bold fs-4">{{ $bom->product?->name }}</div>
            <div class="text-secondary">Produces {{ $bom->quantity }} · {{ $bom->workCenter?->name ?: 'No work center' }}</div>
        </div>
        <x-button href="{{ route('tenant.boms.index') }}" variant="ghost">Back</x-button>
    </div>

    <x-table :headers="['Component', 'SKU', 'Qty']">
        @foreach ($bom->lines as $line)
            <tr>
                <td class="fw-medium">{{ $line->product?->name }}</td>
                <td class="font-monospace small">{{ $line->product?->sku }}</td>
                <td>{{ $line->quantity }}</td>
            </tr>
        @endforeach
    </x-table>
@endsection
