@extends('layouts.app')

@section('title', 'Create customer')
@section('page-title', 'Create customer')
@section('page-subtitle', 'Sales')

@section('content')
    <form method="POST" action="{{ route('tenant.customers.store') }}" class="vstack gap-3">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name') }}" required autofocus class="fs-4 fw-medium" />
                    <div class="row">
                        <div class="col-sm-6">
                            <x-input label="Email" name="email" type="email" value="{{ old('email') }}" />
                        </div>
                        <div class="col-sm-6">
                            <x-input label="Phone" name="phone" value="{{ old('phone') }}" />
                        </div>
                    </div>
                    <x-textarea label="Address" name="address" rows="3">{{ old('address') }}</x-textarea>
                </x-card>
            </div>
            <div class="col-lg-4">
                <x-card>
                    <x-textarea label="Notes" name="notes" rows="5">{{ old('notes') }}</x-textarea>
                </x-card>
            </div>
        </div>
        <div class="d-flex gap-2">
            <x-button>Create</x-button>
            <x-button href="{{ route('tenant.customers.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
