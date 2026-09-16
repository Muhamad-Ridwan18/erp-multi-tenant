@extends('layouts.app')

@section('title', 'Create warehouse')
@section('page-title', 'Create warehouse')
@section('page-subtitle', 'Inventory')

@section('content')
    <p class="mb-3 text-secondary">A STOCK location is created automatically under the new warehouse.</p>

    <form method="POST" action="{{ route('tenant.warehouses.store') }}" class="vstack gap-3">
        @csrf
        <div class="row g-3">
            <div class="col-lg-6">
                <x-card>
                    <x-input label="Code" name="code" value="{{ old('code') }}" required autofocus help="Short unique code, e.g. WH02" />
                    <x-input label="Name" name="name" value="{{ old('name') }}" required />
                    <x-input label="Address" name="address" value="{{ old('address') }}" />
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2">
            <x-button>Create</x-button>
            <x-button href="{{ route('tenant.warehouses.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
