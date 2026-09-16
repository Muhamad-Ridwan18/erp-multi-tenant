@extends('layouts.app')

@section('title', 'Bills of materials')
@section('page-title', 'Bills of materials')
@section('page-subtitle', 'Manufacturing')

@section('page-actions')
    @can('manufacturing.boms.create')
        <a href="{{ route('tenant.boms.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New BOM
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Code', 'Product', 'Qty', 'Components', 'Work center', 'Status']" title="BOMs">
        @forelse ($boms as $bom)
            <tr>
                <td class="font-monospace small">
                    <a href="{{ route('tenant.boms.show', $bom) }}">{{ $bom->code ?: '#'.$bom->id }}</a>
                </td>
                <td class="fw-medium">{{ $bom->product?->name }}</td>
                <td>{{ $bom->quantity }}</td>
                <td class="text-secondary">{{ $bom->lines->count() }}</td>
                <td class="text-secondary">{{ $bom->workCenter?->name ?: '—' }}</td>
                <td><x-badge :tone="$bom->is_active ? 'success' : 'neutral'">{{ $bom->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">No bills of materials yet.</td></tr>
        @endforelse
    </x-table>
@endsection
