@extends('layouts.app')

@section('title', 'Taxes')
@section('page-title', 'Taxes')
@section('page-subtitle', 'Finance')

@section('content')
    <x-table :headers="['Code', 'Name', 'Rate', 'Applies to', 'Price', 'Group', 'Status']" title="Taxes">
        @forelse ($taxes as $tax)
            <tr>
                <td class="font-monospace small">{{ $tax->code }}</td>
                <td class="fw-medium">{{ $tax->name }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $tax->amount, 2, '.', ''), '0'), '.') }}%</td>
                <td class="text-capitalize text-secondary">{{ $tax->type }}</td>
                <td class="text-capitalize text-secondary">{{ $tax->price_include }}</td>
                <td class="text-secondary">{{ $tax->taxGroup?->name ?? '—' }}</td>
                <td>
                    <x-badge :tone="$tax->is_active ? 'success' : 'neutral'">{{ $tax->is_active ? 'Active' : 'Inactive' }}</x-badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary py-4">No taxes configured.</td></tr>
        @endforelse
    </x-table>
@endsection
