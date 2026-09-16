@extends('layouts.app')

@section('title', 'Scraps')
@section('page-title', 'Scraps')
@section('page-subtitle', 'Inventory')

@section('page-actions')
    @can('inventory.scraps.create')
        <a href="{{ route('tenant.scraps.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>
            New scrap
        </a>
    @endcan
@endsection

@section('content')
    <x-table :headers="['Number', 'From', 'Products', 'Done at']" title="Scrap orders">
        @forelse ($scraps as $scrap)
            <tr>
                <td class="font-monospace small">
                    <a href="{{ route('tenant.operations.show', $scrap) }}">{{ $scrap->number }}</a>
                </td>
                <td>{{ $scrap->sourceLocation?->name ?: '—' }}</td>
                <td class="text-secondary">{{ $scrap->moves->map(fn ($m) => ($m->product?->name).' ×'.$m->done_qty)->implode(', ') }}</td>
                <td class="text-secondary">{{ $scrap->done_at?->format('d M Y H:i') ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary py-4">No scraps yet.</td></tr>
        @endforelse
    </x-table>
@endsection
