@extends('layouts.app')

@section('title', 'Edit customer')
@section('page-title', 'Edit customer')

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        @can('sales.customers.delete')
            <form method="POST" action="{{ route('tenant.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')" class="ms-auto">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete</x-button>
            </form>
        @endcan
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('tenant.customers.update', $customer) }}" class="vstack gap-3">
                @csrf
                @method('PUT')
                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name', $customer->name) }}" required />
                    <x-input label="Email" name="email" type="email" value="{{ old('email', $customer->email) }}" />
                    <x-input label="Phone" name="phone" value="{{ old('phone', $customer->phone) }}" />
                    <x-textarea label="Address" name="address" rows="3">{{ old('address', $customer->address) }}</x-textarea>
                    <x-textarea label="Notes" name="notes" rows="2">{{ old('notes', $customer->notes) }}</x-textarea>
                </x-card>
                <div class="d-flex gap-2">
                    <x-button>Save</x-button>
                    <x-button href="{{ route('tenant.customers.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
