@extends('layouts.app')

@section('title', 'Work centers')
@section('page-title', 'Work centers')
@section('page-subtitle', 'Manufacturing')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-table :headers="['Code', 'Name', 'Capacity', 'Cost/hr', 'Status']" title="Work centers">
                @forelse ($workCenters as $center)
                    <tr>
                        <td class="font-monospace small">{{ $center->code ?: '—' }}</td>
                        <td class="fw-medium">{{ $center->name }}</td>
                        <td>{{ $center->default_capacity }}</td>
                        <td class="text-secondary">{{ $center->costs_per_hour !== null ? 'Rp '.number_format($center->costs_per_hour, 0, ',', '.') : '—' }}</td>
                        <td><x-badge :tone="$center->is_active ? 'success' : 'neutral'">{{ $center->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No work centers yet.</td></tr>
                @endforelse
            </x-table>
        </div>
        @can('manufacturing.work_centers.manage')
            <div class="col-lg-4">
                <form method="POST" action="{{ route('tenant.work-centers.store') }}">
                    @csrf
                    <x-card>
                        <h2 class="h3 mb-3">New work center</h2>
                        <x-input label="Code" name="code" value="{{ old('code') }}" />
                        <x-input label="Name" name="name" value="{{ old('name') }}" required />
                        <x-input label="Capacity" name="default_capacity" type="number" min="1" value="{{ old('default_capacity', 1) }}" />
                        <x-input label="Cost per hour (Rp)" name="costs_per_hour" type="number" min="0" step="0.01" value="{{ old('costs_per_hour') }}" />
                        <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
                        <x-button>Save</x-button>
                    </x-card>
                </form>
            </div>
        @endcan
    </div>
@endsection
