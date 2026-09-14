@extends('layouts.app')

@section('title', 'Edit vendor')
@section('page-title', 'Edit vendor')
@section('page-subtitle', $vendor->name)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        @can('procurement.vendors.delete')
            <form method="POST" action="{{ route('tenant.vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this vendor?')" class="ms-auto">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('tenant.vendors.update', $vendor) }}" class="vstack gap-3">
                @csrf
                @method('PUT')
                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name', $vendor->name) }}" required autofocus />
                    <x-input label="Email" name="email" type="email" value="{{ old('email', $vendor->email) }}" />
                    <x-input label="Phone" name="phone" value="{{ old('phone', $vendor->phone) }}" />
                    <x-textarea label="Address" name="address" rows="3">{{ old('address', $vendor->address) }}</x-textarea>
                    <x-textarea label="Notes" name="notes" rows="2">{{ old('notes', $vendor->notes) }}</x-textarea>
                </x-card>
                <div class="d-flex gap-2">
                    <x-button>Save</x-button>
                    <x-button href="{{ route('tenant.vendors.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
