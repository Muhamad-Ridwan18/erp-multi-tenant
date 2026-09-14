@extends('layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers')
@section('page-subtitle', 'Partners')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink-950">Customers</h1>
            <p class="mt-1 text-sm text-ink-500">Companies and people you sell to.</p>
        </div>
        @can('sales.customers.create')
            <x-button href="{{ route('tenant.customers.create') }}">New customer</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Email', 'Phone', '']">
        @forelse ($customers as $customer)
            <tr>
                <td class="px-4 py-3 font-medium text-ink-900">{{ $customer->name }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $customer->email ?: '—' }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $customer->phone ?: '—' }}</td>
                <td class="px-4 py-3 text-right">
                    @can('sales.customers.update')
                        <x-button href="{{ route('tenant.customers.edit', $customer) }}" variant="ghost">Edit</x-button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-ink-500">No customers yet.</td></tr>
        @endforelse
    </x-table>
@endsection
